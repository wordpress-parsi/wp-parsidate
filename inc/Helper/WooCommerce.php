<?php

namespace WPParsidate\Helper;

class WooCommerce {
  /**
   * Check WC custom order tables are enabled or not
   *
   * @return bool
   */
  public static function hposEnabled(): bool {
    return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
  }

  /**
   * Check value is postal code
   *
   * @param mixed $postalCode
   * @param bool $checkSum
   *
   * @return bool
   */
  public static function isPostalCode( $postalCode, $checkSum = false ): bool {
    // Convert to english
    $postalCode = Number::toEnglish( $postalCode );

    // Remove space and special character
    $cleanedCode = preg_replace( '/[-\s]/', '', $postalCode );
    if ( ! preg_match( "/^\d{10}$/", $cleanedCode ) ) {
      return false;
    }

    // Postal code not start with zero
    if ( $cleanedCode[0] === '0' ) {
      return false;
    }

    // Checksum Control
    if ( $checkSum ) {
      $checkDigit = (int) $cleanedCode[9];
      $sum        = 0;
      for ( $i = 0; $i < 9; $i ++ ) {
        $sum += (int) $cleanedCode[ $i ] * ( 10 - $i );
      }
      $remainder            = $sum % 11;
      $calculatedCheckDigit = ( $remainder < 2 ) ? $remainder : 11 - $remainder;

      return $checkDigit === $calculatedCheckDigit;
    }

    return true;
  }

  /**
   * Get WC order statuses
   *
   * @return array
   */
  public static function getOrderStatuses(): array {
    $statuses = wc_get_order_statuses();

    return array_combine(
      array_map( static fn( $k ) => str_replace( 'wc-', '', $k ), array_keys( $statuses ) ),
      array_values( $statuses )
    );
  }
}
