<?php

defined('ABSPATH') or exit('No direct script access allowed');

if (!class_exists('WPP_Rank_Math')) {

    class WPP_Rank_Math
    {
        public function __construct()
        {
            add_filter("rank_math/opengraph/facebook/article_published_time", [$this, 'convert_date_time']);
            add_filter("rank_math/opengraph/facebook/article_modified_time", [$this, 'convert_date_time']);
            add_filter("rank_math/json_ld", [$this, 'json_ld'], 20, 2);
            add_filter('rank_math/snippet/rich_snippet_product_entity', [$this, 'fix_price_currency'], 30);
        }

        public function convert($datetime)
        {
            return gregdate('c', eng_number($datetime));
        }

        public function convert_date_time($content)
        {
            return $this->convert($content);
        }

        public function json_ld($data, $jsonld)
        {
            foreach ($data as $key => $item) {

                // Fix uploadDate in video Object
                if (isset($item['@type']) && $item['@type'] === 'VideoObject') {
                    if (isset($item['uploadDate']) and !empty($item['uploadDate'])) {
                        $data[$key]['uploadDate'] = $this->convert_date_time($item['uploadDate']);
                    }
                }
            }

            return $data;
        }

        public function fix_price_currency($entity)
        {
            if (isset($entity['offers']['priceCurrency']) and strtoupper($entity['offers']['priceCurrency']) == "IRT") {

                $entity['offers']['priceCurrency'] = 'IRR';
                if (!empty($entity['offers']['price']) and (float)$entity['offers']['price'] > 0) {
                    $entity['offers']['price'] = apply_filters("wpp_rank_math_product_price", ($entity['offers']['price'] * 10), $entity);
                }
            }

            return $entity;
        }
    }

    $GLOBALS['WPP_Rank_Math'] = new WPP_Rank_Math();
}