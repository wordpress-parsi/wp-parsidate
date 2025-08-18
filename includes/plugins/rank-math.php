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

        /* @method */
        public function convert($datetime, $format = 'c')
        {
            return gregdate($format, eng_number($datetime));
        }

        /* @hook */
        public function convert_date_time($content)
        {
            return $this->convert($content);
        }

        /* @hook */
        public function json_ld($data, $jsonld)
        {
            if (empty($data) || !is_array($data)) {
                return $data;
            }

            foreach ($data as $key => $item) {

                // Fix uploadDate in video Object
                if (isset($item['@type']) && $item['@type'] === 'VideoObject') {
                    if (isset($item['uploadDate']) and !empty($item['uploadDate'])) {
                        $data[$key]['uploadDate'] = $this->convert_date_time($item['uploadDate']);
                    }
                }

                // Fix priceValidUntil Date
                if (isset($item['@type']) && $item['@type'] === 'Product') {
                    if (isset($item['offers']['priceValidUntil']) and !empty($item['offers']['priceValidUntil'])) {
                        $jalali = wpp_date_is($item['offers']['priceValidUntil'], "Y-m-d");
                        if ($jalali['status'] === true and $jalali['type'] == "jalali") {
                            $data[$key]['offers']['priceValidUntil'] = $this->convert($jalali['value'], "Y-m-d");
                        }
                    }
                }

                // Fix ProductGroup / hasVariant / offers
                if (isset($item['@type']) && $item['@type'] === 'ProductGroup') {
                    if (isset($item['hasVariant']) and !empty($item['hasVariant']) and is_array($item['hasVariant'])) {
                        foreach ($item['hasVariant'] as $variantKey => $variant) {

                            // Check priceValidUntil
                            if (isset($variant['offers']['priceValidUntil']) and !empty($variant['offers']['priceValidUntil'])) {
                                $jalali = wpp_date_is($variant['offers']['priceValidUntil'], "Y-m-d");
                                if ($jalali['status'] === true and $jalali['type'] == "jalali") {
                                    $data[$key]['hasVariant'][$variantKey]['offers']['priceValidUntil'] = $this->convert($jalali['value'], "Y-m-d");
                                }
                            }

                            // Check offer Price
                            if (isset($variant['offers']['priceCurrency']) and strtoupper($variant['offers']['priceCurrency']) == "IRT") {
                                $data[$key]['hasVariant'][$variantKey]['offers']['priceCurrency'] = 'IRR';
                                if (!empty($variant['offers']['price']) and (float)$variant['offers']['price'] > 0) {
                                    $data[$key]['hasVariant'][$variantKey]['offers']['price'] = apply_filters("wpp_rank_math_product_variant_price", ($variant['offers']['price'] * 10), $variant);
                                }
                            }
                        }
                    }
                }
            }

            return $data;
        }

        /* @hook */
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