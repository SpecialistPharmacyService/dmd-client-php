<?php

namespace makeandship\dmd\transformers;

require_once dirname(__FILE__) . '/../Util.php';

use makeandship\dmd\Util;

class SuggestionTransformer
{
    public function transform($data, $fields)
    {
        $results = array();

        $suggest   = Util::safely_get_attribute($data, 'suggest');
        $medicines = Util::safely_get_attribute($suggest, 'medicine');

        foreach ($medicines as $medicine) {
            $options = Util::safely_get_attribute($medicine, 'options');
            foreach ($options as $option) {
                $id     = strval(Util::safely_get_attribute($option, '_id'));
                $source = Util::safely_get_attribute($option, '_source');
                $name   = Util::safely_get_attribute($source, 'nm');
                $desc   = Util::safely_get_attribute($source, 'desc');
                $type   = Util::safely_get_attribute($source, 'type');

                Util::log("SuggestionTransformer: name", $name);
                Util::log("SuggestionTransformer: desc", $desc);
                Util::log("SuggestionTransformer: type", $type);

                $contexts = Util::safely_get_attribute($option, 'contexts');
                $types    = array();
                foreach ($contexts as $contexts_type => $context) {
                    foreach ($context as $context_type => $value) {
                        if (strpos($value, "_") !== false) {
                            $key         = substr($value, 0, strpos($value, "_"));
                            $types[$key] = $key;
                        } else {
                            $types[$value] = $value;
                        }
                    }
                }

                $medicine_type = implode(" and ", array_keys($types));
                $name_for_type = $type === "AMP" ? $desc : $name;
                $results[$id]  = $name_for_type . ($medicine_type ? " [" . $medicine_type . "]" : "");
            }
        }

        return array(
            'total'   => count($results),
            'results' => $results,
        );
    }
}
