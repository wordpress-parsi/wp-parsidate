<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

if ( empty( $args['link'] ) ) {
  echo wp_kses_post( $args['title'] );
} else {
  echo '<a href="' . esc_url_raw( $args['link'] ) . '" class="wa-feed-link" target="_blank">' . wp_kses_post( $args['title'] ) . '</a>';
}
