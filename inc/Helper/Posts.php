<?php

namespace WPParsidate\Helper;

class Posts {
  public static function getTypeSelect(
    $id, $name, $selected, $args = [],
    $ignore = [ 'attachment', 'page' ]
  ): string {
    $defaultArgs = array( 'public' => true, 'show_ui' => true );
    $args        = wp_parse_args( $args, $defaultArgs );

    $postTypes = get_post_types( $args, 'objects' );

    $select = '<select name="' . $name . '" id="' . $id . '">';
    foreach ( $postTypes as $postType ) {
      if ( in_array( $postType->name, $ignore ) ) {
        continue;
      }

      $select .= '<option value="' . esc_attr( $postType->name ) . '" ' . selected( $selected, $postType->name, false ) . '>' . $postType->labels->singular_name . '</option>';
    }
    $select .= '</select>';

    return $select;
  }
}
