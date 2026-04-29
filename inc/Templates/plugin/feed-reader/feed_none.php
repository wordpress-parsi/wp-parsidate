<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

echo '<div class="wppd-feed-none">' . $args['desc'] . '</div>';
