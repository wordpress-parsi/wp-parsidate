<?php
/**
 * Makes RankMath compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Addons/RankMath
 * @author                  Mehrshad Darzi
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Helper\Number;

class RankMath extends Addon {
  public string $addonID = 'rank_math';

  public function initAction(): void {
    add_filter( "rank_math/opengraph/facebook/article_published_time", [ $this, 'convert_date_time' ] );
    add_filter( "rank_math/opengraph/facebook/article_modified_time", [ $this, 'convert_date_time' ] );
    add_filter( "rank_math/json_ld", [ $this, 'json_ld' ], 20, 2 );
    add_filter( 'rank_math/snippet/rich_snippet_product_entity', [ $this, 'fix_price_currency' ], 30 );
  }

  /* @method */
  public function convert( $datetime, $format = 'c' ) {
    return gregdate( $format, Number::toEnglish( $datetime ) );
  }

  /* @hook */
  public function convert_date_time( $content ) {
    return $this->convert( $content );
  }

  /* @hook */
  public function json_ld( $data, $jsonld ) {
    if ( empty( $data ) || ! is_array( $data ) ) {
      return $data;
    }

    foreach ( $data as $key => $item ) {

      // Fix uploadDate in video Object
      if ( isset( $item['@type'] ) && $item['@type'] === 'VideoObject' ) {
        if ( isset( $item['uploadDate'] ) and ! empty( $item['uploadDate'] ) ) {
          $data[ $key ]['uploadDate'] = $this->convert_date_time( $item['uploadDate'] );
        }
      }

      // Fix priceValidUntil Date
      if ( isset( $item['@type'] ) && $item['@type'] === 'Product' ) {
        if ( isset( $item['offers']['priceValidUntil'] ) and ! empty( $item['offers']['priceValidUntil'] ) ) {
          $jalali = wpp_date_is( $item['offers']['priceValidUntil'], "Y-m-d" );
          if ( $jalali['status'] === true and $jalali['type'] === "jalali" ) {
            $data[ $key ]['offers']['priceValidUntil'] = $this->convert( $jalali['value'], "Y-m-d" );
          }
        }
      }

      // Fix ProductGroup / hasVariant / offers
      if ( isset( $item['@type'] ) && $item['@type'] === 'ProductGroup' ) {
        if ( isset( $item['hasVariant'] ) and ! empty( $item['hasVariant'] ) and is_array( $item['hasVariant'] ) ) {
          foreach ( $item['hasVariant'] as $variantKey => $variant ) {

            // Check priceValidUntil
            if ( isset( $variant['offers']['priceValidUntil'] ) and ! empty( $variant['offers']['priceValidUntil'] ) ) {
              $jalali = wpp_date_is( $variant['offers']['priceValidUntil'], "Y-m-d" );
              if ( $jalali['status'] === true and $jalali['type'] === "jalali" ) {
                $data[ $key ]['hasVariant'][ $variantKey ]['offers']['priceValidUntil'] = $this->convert( $jalali['value'],
                  "Y-m-d" );
              }
            }

            // Check offer Price
            if ( isset( $variant['offers']['priceCurrency'] ) and strtoupper( $variant['offers']['priceCurrency'] ) === "IRT" ) {
              $data[ $key ]['hasVariant'][ $variantKey ]['offers']['priceCurrency'] = 'IRR';
              if ( ! empty( $variant['offers']['price'] ) and (float) $variant['offers']['price'] > 0 ) {
                $data[ $key ]['hasVariant'][ $variantKey ]['offers']['price'] = apply_filters( "wpp_rank_math_product_variant_price",
                  ( $variant['offers']['price'] * 10 ), $variant );
              }
            }
          }
        }
      }
    }

    return $data;
  }

  /* @hook */
  public function fix_price_currency( $entity ) {
    if ( isset( $entity['offers']['priceCurrency'] ) and strtoupper( $entity['offers']['priceCurrency'] ) === "IRT" ) {

      $entity['offers']['priceCurrency'] = 'IRR';
      if ( ! empty( $entity['offers']['price'] ) and (float) $entity['offers']['price'] > 0 ) {
        $entity['offers']['price'] = apply_filters( "wpp_rank_math_product_price",
          ( $entity['offers']['price'] * 10 ), $entity );
      }
    }

    return $entity;
  }

  public function info(): array {
    $svg = '<?xml version="1.0"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 350 350">
 <defs>
  <clipPath id="clip0_3148_2108">
   <rect fill="white" height="350" id="svg_1" width="350"/>
  </clipPath>
  <linearGradient id="paint0_linear_3148_2108" x1="1" x2="0.002853" y1="0" y2="1.002854">
   <stop stop-color="#F560FD"/>
   <stop offset="1" stop-color="#232A9E"/>
  </linearGradient>
 </defs>
 <g class="layer">
  <g clip-path="url(#clip0_3148_2108)" id="svg_2">
   <path d="m297,0.49902l-244,0c-29.2711,0 -53,23.66128 -53,52.84898l0,243.305c0,29.188 23.7289,52.849 53,52.849l244,0c29.271,0 53,-23.661 53,-52.849l0,-243.305c0,-29.1877 -23.729,-52.84898 -53,-52.84898z" fill="url(#paint0_linear_3148_2108)" id="svg_3" transform="matrix(1, 0, 0, 1, 0, 0)"/>
   <path d="m291.667,140.690998l-38.456,1.654l6.688,10.257l-62.742,40.743l-64.902,-26.315l-73.9216,29.945l5.1349,12.334l68.7747,-27.904l66.456,26.942l68.557,-44.495l6.688,10.258l17.723,-33.419z" fill="white" id="svg_4" transform="matrix(1, 0, 0, 1, 0, 0)"/>
   <path d="m84.5846,130.67l47.1364,0l0,25.748l-47.1364,19.105l0,-44.853z" fill="white" id="svg_5"/>
   <path d="m138.112,99.27l47.136,0l0,78.681l-47.136,-19.059l0,-59.622z" fill="white" id="svg_6"/>
   <path d="m191.639,63.7139l47.136,0l0,91.4831l-42.142,27.355l-4.994,-2.011l0,-116.8271z" fill="white" id="svg_7"/>
   <path d="m58.3334,261.169l0,-31.478l13.0351,0c2.7842,0 5.1887,0.501 7.2131,1.379c2.0249,0.878 3.5435,2.132 4.6825,3.888c1.139,1.63 1.6452,3.637 1.6452,6.02c0,2.383 -0.5062,4.264 -1.6452,5.894c-1.139,1.63 -2.6576,2.885 -4.6825,3.762c-2.0244,0.878 -4.4289,1.38 -7.2131,1.38l-9.7447,0l2.6576,-2.634l0,11.789l-5.948,0zm5.8215,-11.162l-2.6577,-2.759l9.4916,0c2.6577,0 4.556,-0.501 5.8215,-1.63c1.2656,-1.129 2.0244,-2.634 2.0244,-4.64c0,-2.007 -0.6323,-3.512 -2.0244,-4.641c-1.2655,-1.128 -3.2904,-1.63 -5.8215,-1.63l-9.4916,0l2.6577,-2.884l0,18.184zm14.9329,11.162l-7.9724,-11.412l6.3277,0l8.099,11.412l-6.4543,0z" fill="white" id="svg_8"/>
   <path d="m97.9444,261.169c-1.7717,0 -3.4169,-0.251 -4.809,-0.878c-1.3921,-0.627 -2.4046,-1.505 -3.1639,-2.508c-0.7593,-1.003 -1.139,-2.383 -1.139,-3.762c0,-1.38 0.3797,-2.509 1.0125,-3.637c0.6327,-1.129 1.7717,-1.881 3.1638,-2.509c1.5187,-0.627 3.417,-1.003 5.9481,-1.003l7.0871,0l0,3.763l-6.5809,0c-1.8983,0 -3.2904,0.25 -3.9232,0.877c-0.6328,0.628 -1.0124,1.38 -1.0124,2.258c0,1.003 0.3796,1.881 1.2655,2.383c0.7594,0.627 1.8983,0.878 3.417,0.878c1.392,0 2.658,-0.377 3.797,-1.004c1.139,-0.627 1.898,-1.505 2.404,-2.759l1.013,3.386c-0.507,1.38 -1.519,2.509 -2.911,3.261c-1.392,0.753 -3.417,1.254 -5.5686,1.254zm7.5936,-0.376l0,-4.891l-0.38,-1.003l0,-8.528c0,-1.631 -0.506,-2.885 -1.519,-3.888c-1.012,-0.878 -2.531,-1.38 -4.5556,-1.38c-1.3921,0 -2.6576,0.251 -4.0497,0.627c-1.2655,0.377 -2.4045,1.004 -3.2904,1.756l-2.278,-4.139c1.2655,-1.003 2.9107,-1.755 4.6825,-2.257c1.7718,-0.502 3.6701,-0.752 5.5684,-0.752c3.5438,0 6.2008,0.877 8.0998,2.508c1.898,1.63 2.911,4.264 2.911,7.65l0,14.046l-5.189,0l0,0.251z" fill="white" id="svg_9"/>
   <path d="m131.575,236.359c1.899,0 3.67,0.376 5.189,1.128c1.519,0.753 2.784,1.882 3.543,3.387c0.886,1.505 1.266,3.511 1.266,5.894l0,14.401l-5.695,0l0,-13.649c0,-2.132 -0.506,-3.762 -1.519,-4.765c-1.012,-1.004 -2.404,-1.631 -4.302,-1.631c-1.393,0 -2.532,0.251 -3.544,0.753c-1.012,0.502 -1.772,1.379 -2.404,2.383c-0.507,1.003 -0.886,2.383 -0.886,4.013l0,12.771l-5.316,0l0,-24.434l5.442,0l0,6.521l-1.012,-2.007c0.886,-1.505 2.025,-2.759 3.67,-3.511c1.645,-0.878 3.543,-1.254 5.568,-1.254z" fill="white" id="svg_10"/>
   <path d="m148.913,261.169l0,-31.542l5.695,0l0,31.542l-5.695,0zm4.683,-5.393l0.126,-7.023l12.656,-11.538l6.834,0l-10.757,10.66l-3.038,2.509l-5.821,5.392zm13.668,5.393l-8.859,-10.785l3.543,-4.515l12.15,15.3l-6.834,0z" fill="white" id="svg_11"/>
   <path d="m178.4,261.169l0,-31.478l4.809,0l13.921,22.95l-2.531,0l13.668,-22.95l4.809,0l0,31.478l-5.568,0l0,-22.825l1.139,0l-11.643,19.188l-2.658,0l-11.769,-19.188l1.392,0l0,22.825l-5.569,0z" fill="white" id="svg_12"/>
   <path d="m228.389,261.169c-1.771,0 -3.417,-0.251 -4.809,-0.878c-1.392,-0.627 -2.404,-1.505 -3.164,-2.508c-0.759,-1.129 -1.139,-2.383 -1.139,-3.762c0,-1.38 0.38,-2.509 1.013,-3.637c0.633,-1.129 1.772,-1.881 3.164,-2.508c1.518,-0.628 3.417,-1.004 5.948,-1.004l7.087,0l0,3.763l-6.581,0c-1.898,0 -3.29,0.25 -3.923,0.877c-0.633,0.628 -1.013,1.38 -1.013,2.258c0,1.003 0.38,1.881 1.266,2.383c0.759,0.627 1.898,0.878 3.417,0.878c1.392,0 2.658,-0.377 3.797,-1.004c1.139,-0.627 1.898,-1.505 2.404,-2.759l1.013,3.386c-0.507,1.38 -1.519,2.509 -2.911,3.261c-1.392,0.753 -3.417,1.254 -5.569,1.254zm7.594,-0.376l0,-4.891l-0.38,-1.003l0,-8.528c0,-1.631 -0.506,-2.885 -1.519,-3.888c-1.012,-0.878 -2.531,-1.38 -4.556,-1.38c-1.392,0 -2.657,0.251 -4.049,0.627c-1.266,0.377 -2.405,1.004 -3.291,1.756l-2.278,-4.139c1.266,-1.003 2.911,-1.755 4.683,-2.257c1.772,-0.502 3.67,-0.752 5.568,-0.752c3.544,0 6.201,0.877 8.1,2.508c1.898,1.63 2.91,4.264 2.91,7.65l0,14.046l-5.188,0l0,0.251z" fill="white" id="svg_13"/>
   <path d="m245.474,241.733l0,-4.515l16.199,0l0,4.515l-16.199,0zm12.276,19.436c-2.658,0 -4.683,-0.627 -6.202,-2.006c-1.392,-1.38 -2.151,-3.387 -2.151,-6.02l0,-21.697l5.695,0l0,21.446c0,1.129 0.253,2.007 0.886,2.634c0.633,0.627 1.392,1.003 2.531,1.003c1.266,0 2.278,-0.376 3.164,-1.003l1.645,4.013c-0.633,0.501 -1.519,1.003 -2.404,1.254c-1.139,0.251 -2.152,0.376 -3.164,0.376z" fill="white" id="svg_14"/>
   <path d="m281.669,236.965c1.898,0 3.67,0.376 5.189,1.128c1.518,0.753 2.784,1.882 3.543,3.386c0.886,1.505 1.266,3.512 1.266,5.895l0,13.795l-5.695,0l0,-13.043c0,-2.132 -0.506,-3.762 -1.519,-4.765c-1.012,-1.004 -2.404,-1.631 -4.303,-1.631c-1.392,0 -2.531,0.251 -3.543,0.753c-1.013,0.501 -1.772,1.379 -2.405,2.383c-0.506,1.003 -0.886,2.382 -0.886,4.013l0,12.165l-5.695,0l0,-31.417l5.695,0l0,13.984l-1.265,-2.006c0.886,-1.505 2.025,-2.759 3.67,-3.512c1.898,-0.752 3.797,-1.128 5.948,-1.128z" fill="white" id="svg_15"/>
  </g>
 </g>
</svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Rank Math', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Rank Math', 'wp-parsidate' ),
      'force_enable'     => false,
      'icon'             => $svg,
      'tags'             => [ esc_html__( 'SEO', 'wp-parsidate' ) ],
      'cat'              => 'seo',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'seo-by-rank-math/rank-math.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/seo-by-rank-math/',
          'class_check'  => 'RankMath'
        )
      ]
    );
  }
}
