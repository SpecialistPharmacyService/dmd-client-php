<?php

namespace makeandship\dmd;

require_once dirname(__FILE__) . '/Constants.php';
require_once dirname(__FILE__) . '/SettingsManager.php';
require_once dirname(__FILE__) . '/Util.php';
require_once dirname(__FILE__) . '/transformers/SuggestionTransformer.php';
require_once dirname(__FILE__) . '/transformers/VTMResultsTransformer.php';

use makeandship\dmd\Constants;
use makeandship\dmd\SettingsManager;
use makeandship\dmd\transformers\SuggestionTransformer;
use makeandship\dmd\transformers\VTMResultsTransformer;
use makeandship\dmd\Util;
use \Elastica\Client;
use \Elastica\Request;
use \Elastica\Scroll;

class DmdClient
{
    public function __construct()
    {
        $settings     = $this->get_settings();
        $this->client = new Client($settings);
    }

    private function get_settings()
    {
        return SettingsManager::get_instance()->get_client_settings();
    }

    /**
     * Execute an elastic search query.  Use a <code>QueryBuilder</code> to generate valid queries
     *
     * @param array an elastic search query
     * @return results object
     *
     * @see QueryBuilder
     */
    public function search($args)
    {
        Util::log("Client#search", "enter");
        $results = array();

        $args = Util::apply_filters('prepare_query', $args);

        Util::log("Client#search: args", json_encode($args));

        try {
            $index = $this->get_index();

            $path = $index->getName() . '/_search';

            $response = $this->client->request($path, Request::GET, $args);
            if ($response) {
                $data = $response->getData();

                $transformer = new SuggestionTransformer();
                $results     = $transformer->transform($data, array());
            }

            Util::log("Client#search", "exit");

            return $results;
        } catch (\Exception $ex) {
            error_log($ex);

            Util::do_action('search_exception', $ex);

            Util::log("Client#search: exception", "exit");

            return null;
        }
    }

    /**
     * Get a document using the document id
     *
     * get_document_by_id
     *
     * @param id
     * @return document
     */
    public function get_document_by_id($id)
    {
        Util::log("Client#get_document_by_id", "enter");
        $document = null;

        Util::log("Client#get_document_by_id: id: ", $id);

        try {
            $index = $this->get_index();

            $response = $index->getDocument($id);

            if ($response) {
                $document = $response->getData();
            }

            Util::log("Client#get_document_by_id", "exit");

            return $document;
        } catch (\Exception $ex) {
            Util::log("Client#get_document_by_id: exception: ", $ex->getMessage());

            Util::log("Client#get_document_by_id: exception", "exit");

            return null;
        }
    }

    /**
     * Get a document using the previous document id
     *
     * get_document_by_prev_id
     *
     * @param id
     * @return document
     */
    public function get_document_by_prev_id($id)
    {
        Util::log("Client#get_document_by_prev_id", "enter");
        $document = null;

        Util::log("Client#get_document_by_prev_id: id: ", $id);

        try {
            $index = $this->get_index();

            // or query across terms
            $query = array(
                "query" => array(
                    "bool" => array(
                        "should"               => array(
                            array("term" => array("vtmprevid" => $id)),
                            array("term" => array("vpidprev" => $id)),
                            array("term" => array("vppidprev" => $id)),
                            array("term" => array("apidprev" => $id)),
                            array("term" => array("appidprev" => $id)),
                        ),
                        "minimum_should_match" => "1",
                    ),
                ),
            );
            $response = $this->query($query);

            if ($response) {
                // ResultSet
                $results = $response->getDocuments();
                if ($results && is_array($results) && count($results) > 0) {
                    $result         = $results[0]->toArray();
                    $document       = Util::safely_get_attribute($result, "_source");
                    $document['id'] = Util::safely_get_attribute($result, "_id");
                }
            }

            Util::log("Client#get_document_by_prev_id", "exit");

            return $document;
        } catch (\Exception $ex) {
            Util::log("Client#get_document_by_id: exception: ", $ex->getMessage());

            Util::log("Client#get_document_by_id: exception", "exit");

            return null;
        }

    }

    /**
     * Get a list of all vtms
     */
    public function vtms()
    {
        $query = array(
            "query"   => array(
                "bool" => array(
                    "must" => [
                        array("match" => array(
                            "type" => "VTM",
                        )),
                    ],
                ),
            ),
            "_source" => ["nm", "vtmid", "type", "bnf"],
            "size"    => 500,
        );

        $scroll = $this->scroll($query);

        $results = array();

        if ($scroll) {
            $transformer = new VTMResultsTransformer();
            foreach ($scroll as $scroll_id => $scroll_result_set) {
                $scroll_results = $transformer->transform($scroll_result_set);

                $results = array_merge($results, $scroll_results);
            }
        }

        return $results;
    }

    /**
     * Initiate a search with the ElasticSearch server and return the results. Use Faceting to manipulate URLs.
     * @param string $search A space delimited list of terms to search for
     * @param integer $pageIndex The index that represents the current page
     * @param integer $size The number of results to return per page
     * @param array $facets An object that contains selected facets (typically the query string, ie: $_GET)
     * @param boolean $sortByDate If false, results will be sorted by score (relevancy)
     * @see Faceting
     *
     * @return array The results of the search
     **/
    /*public function search($query, $pageIndex = 0, $size = 10, $facets = array(), $sortByDate = false)
    {
    if (empty($query) || (empty($query['query']) && empty($query['aggs']))) {
    return array(
    'total' => 0,
    'ids' => array(),
    'facets' => array()
    );
    }
    return $this->query($query);
    }*/

    /**
     * @internal
     **/
    private function query($args)
    {
        $query = new \Elastica\Query($args);

        $query = Util::apply_filters('dmd_client_query', $query);

        try {
            $search = new \Elastica\Search($this->client);
            $search->addIndex($this->get_index());

            $search = Util::apply_filters('dmd_client_search', $search, $query);

            return $search->search($query);
        } catch (\Exception $ex) {
            error_log($ex);

            Config::do_action('dmd_client_exception', $ex);

            return null;
        }
    }

    /**
     * @internal
     **/
    private function scroll($args)
    {
        $query = new \Elastica\Query($args);

        $query = Util::apply_filters('dmd_client_query', $query);

        try {
            $search = new \Elastica\Search($this->client);
            $search->addIndex($this->get_index());
            $search->setQuery($query);

            $search = Util::apply_filters('dmd_client_search', $search, $query);

            $scroll = new Scroll($search);

            return $scroll;
        } catch (\Exception $ex) {
            error_log($ex);

            Config::do_action('dmd_client_exception', $ex);

            return null;
        }
    }

    private function get_index()
    {
        $index_name = SettingsManager::get_instance()->get_setting(Constants::ENV_DMD_ES_INDEX);
        Util::log("Client#get_index", "index: " . $index_name);
        return $this->client->getIndex($index_name);
    }
}
