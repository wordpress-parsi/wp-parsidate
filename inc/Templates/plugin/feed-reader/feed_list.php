<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

if ( empty( $args['items'] ) && isset( $args['none'] ) ) {
  echo wp_kses_post( $args['none'] );

  return;
}

echo '<ul class="wppd-list-links">';
foreach ( $args['items'] as $wpparsidateFeedItem ) {
  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  echo '<li>' . $wpparsidateFeedItem . '</li>';
}
echo '</ul>';
