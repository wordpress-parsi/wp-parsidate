<?php

namespace WPParsidate\Helper;

use enshrined\svgSanitize\Sanitizer;

defined( 'ABSPATH' ) || exit;

class Sanitizing {
  /**
   * Sanitize SVG
   *
   * @param  string  $svg  SVG HTML
   *
   * @return string SVG HTML
   */
  public static function svg( string $svg ): string {
    $Sanitizer = new Sanitizer();
    $Sanitizer->removeXMLTag( true );
    $cleanSVG = $Sanitizer->sanitize( $svg );

    return $cleanSVG ?: '';
  }

  /**
   * Clean scalar value
   *
   * @param  mixed  $value
   *
   * @return array|mixed|string
   */
  public static function clean( $value ) {
    if ( is_array( $value ) ) {
      return array_map( array( 'Sanitizing', 'clean' ), $value );
    }

    return is_scalar( $value ) ? sanitize_text_field( $value ) : $value;
  }

  /**
   * JSON to Array
   *
   * @param  string  $value  JSON string
   *
   * @return array
   * @throws \JsonException
   */
  public static function jsonArray( string $value ): array {
    $value = wp_unslash( htmlspecialchars_decode( $value ) );
    $value = str_replace( "'", '"', $value );
    if ( ! JSON::validate( $value ) ) {
      return [];
    }

    return self::array( JSON::decode( $value, true ) );
  }

  /**
   * Object to array
   *
   * @param  mixed  $value
   *
   * @return array
   */
  public static function array( $value ): array {
    return (array) $value;
  }

  /**
   * Convert to bool value
   *
   * @param  mixed  $value
   *
   * @return bool
   */
  public static function bool( $value ): bool {
    $value = $value ?? '';

    return is_bool( $value ) ? $value : ( 'yes' === strtolower( $value ) || 1 === $value || 'true' === strtolower( $value ) || '1' === $value );
  }

  /**
   * Converts a value to non-negative integer.
   *
   * @param  mixed  $value  Data you wish to have converted to a non-negative integer.
   *
   * @return int A non-negative integer.
   */
  public static function absint( $value ): int {
    return absint( $value );
  }

  /**
   * Converts a value to integer
   *
   * @param  mixed  $value  Data you wish to have converted to an integer.
   *
   * @return int An integer
   */
  public static function int( $value ): int {
    return (int) $value;
  }

  /**
   * Converts a value to float
   *
   * @param  mixed  $value  Data you wish to have converted to an float.
   *
   * @return float An float
   */
  public static function float( $value ): float {
    return (float) $value;
  }

  /**
   * Sanitizes a string into a slug, which can be used in URLs or HTML attributes.
   *
   * @param  string  $value  The string to be sanitized.
   *
   * @return string The sanitized string.
   */
  public static function title( string $value ): string {
    return sanitize_title( $value );
  }

  /**
   * Sanitizes a hex color.
   *
   * @param $value
   *
   * @return string
   */
  public static function color( $value ): string {
    return sanitize_hex_color( $value );
  }

  /**
   * Sanitizes a hex color without a hash. Use sanitize_hex_color() when possible.
   *
   * @param  string  $value  The color value to sanitize. Can be with or without a #.
   *
   * @return string|null The sanitized hex color without the hash prefix,
   *                     empty string if input is empty, or null if invalid.
   */
  public static function colorNoHash( string $value ): string {
    return sanitize_hex_color_no_hash( $value );
  }

  /**
   * Sanitizes a string from user input or from the database.
   *
   * @param  string  $value  String to sanitize.
   *
   * @return string Sanitized string.
   */
  public static function text( string $value ): string {
    return sanitize_text_field( $value );
  }

  /**
   * Strips out all characters that are not allowable in an email.
   *
   * @param  string  $value  Email address to filter.
   *
   * @return string Filtered email address.
   */
  public static function email( string $value ): string {
    return sanitize_email( wp_unslash( $value ) );
  }

  /**
   * Sanitizes a filename, replacing whitespace with dashes.
   *
   * @param  string  $value  The filename to be sanitized.
   *
   * @return string The sanitized filename.
   */
  public static function filename( string $value ): string {
    return sanitize_file_name( $value );
  }

  /**
   * Sanitizes a URL for database or redirect usage.
   *
   * @param  string  $value  The URL to be cleaned.
   *
   * @return string The cleaned URL after esc_url() is run with the 'db' context.
   */
  public static function url( string $value ): string {
    return sanitize_url( $value, array( 'http', 'https' ) );
  }

  /**
   * Sanitizes a multiline string from user input or from the database.
   *
   * @param  string  $value  String to sanitize.
   *
   * @return string Sanitized string.
   */
  public static function textarea( string $value ): string {
    return sanitize_textarea_field( wp_unslash( $value ) );
  }

  /**
   * Sanitizes an HTML classname to ensure it only contains valid characters.
   *
   * @param  string  $value  The classname to be sanitized.
   *
   * @return string The sanitized value.
   */
  public static function class( string $value ): string {
    return sanitize_html_class( $value );
  }

  /**
   * Sanitizes a mime type
   *
   * @param  string  $value  Mime type.
   *
   * @return string Sanitized mime type.
   */
  public static function mimeType( string $value ): string {
    return sanitize_mime_type( $value );
  }

  /**
   * Ensures a string is a valid SQL 'order by' clause.
   *
   * @param  string  $value  Order by clause to be validated.
   *
   * @return string|false Returns $orderby if valid, false otherwise.
   */
  public static function sqlOrderBy( string $value ): string {
    return sanitize_sql_orderby( $value );
  }

  /**
   * Sanitizes a username, stripping out unsafe characters.
   *
   * @param  string  $value  The username to be sanitized.
   *
   * @return string The sanitized username, after passing through filters.
   */
  public static function username( string $value ): string {
    return sanitize_user( $value );
  }
}
