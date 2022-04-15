<?php

namespace makeandship\dmd\transformers;

require_once dirname(__FILE__) . '/../Util.php';

use makeandship\dmd\Util;

class VTMResultsTransformer
{
    public function transform($elastica_result_set)
    {
        $results = array();

        if ($elastica_result_set) {
            $elastica_results = $elastica_result_set->getResults();

            if ($elastica_results) {
                foreach ($elastica_results as $elastica_result) {
                    $hit    = $elastica_result->getHit();
                    $source = Util::safely_get_attribute($hit, '_source');

                    $source['name']                         = Util::safely_get_attribute($source, 'nm');
                    $source['bnf_chapters_and_subchapters'] = Util::safely_get_attribute($source, 'bnf');
                    $source['id']                           = Util::safely_get_attribute($source, 'vtmid');

                    $results[] = $source;
                }
            }
        }

        return $results;
    }
}