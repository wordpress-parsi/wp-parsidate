<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

if ( empty( $args['items'] ) && isset( $args['none'] ) ) {
  echo $args['none'];

  return;
}

echo '<ul class="wppd-list-links">';
foreach ( $args['items'] as $feedItem ) {
  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  echo '<li>' . $feedItem . '</li>';
}
echo '</ul>';
