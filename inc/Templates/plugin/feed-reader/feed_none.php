<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

echo '<div class="wppd-feed-none">' . wp_kses_post( $args['desc'] ) . '</div>';
