<?php

defined('ABSPATH') or exit('No direct script access allowed');

if (!class_exists('WPP_Rank_Math')) {

    class WPP_Rank_Math
    {
        public function __construct()
        {
            add_filter("rank_math/opengraph/facebook/article_published_time", [$this, 'convert_date_time']);
            add_filter("rank_math/opengraph/facebook/article_modified_time", [$this, 'convert_date_time']);
            add_filter("rank_math/schema/videoobject", [$this, 'fix_upload_date'], 30);
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

        public function fix_upload_date($schema)
        {
            if (is_array($schema) && isset($schema['uploadDate'])) {
                $schema['uploadDate'] = $this->convert($schema['uploadDate']);
            }

            return $schema;
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