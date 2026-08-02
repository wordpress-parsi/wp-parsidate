<?php

namespace WPParsidate\Helper;

class Posts {
  public static function getTypes( $args = [], $ignore = [ 'attachment', 'page' ] ): array {
    $defaultArgs = array( 'public' => true, 'show_ui' => true );
    $args        = wp_parse_args( $args, $defaultArgs );
    $postTypes   = array();
    $types       = get_post_types( $args, 'objects' );

    /**
     * Filters post types ignore list
     *
     * @param array $ignore Ignore list
     * @param array $args Input arguments
     *
     * @since 6.3
     *
     */
    $ignore = (array) apply_filters( 'wp_parsidate_posts_types_ignore_list', $ignore, $args );

    foreach ( $types as $pt ) {
      if ( in_array( $pt->name, $ignore, true ) ) {
        continue;
      }
      $postTypes[ $pt->name ] = $pt->labels->singular_name;
    }

    return $postTypes;
  }

  public static function getTypeSelect(
    $id, $name, $selected, $args = [],
    $ignore = [ 'attachment', 'page' ]
  ): string {
    $postTypes = self::getTypes( $args, $ignore );

    $select = '<select name="' . $name . '" id="' . $id . '">';
    foreach ( $postTypes as $postType => $label ) {
      $select .= '<option value="' . esc_attr( $postType ) . '" ' . selected( $selected, $postType, false ) . '>' . $label . '</option>';
    }
    $select .= '</select>';

    return $select;
  }
}
