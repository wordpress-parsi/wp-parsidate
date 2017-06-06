<?php
/**
 * WP-Parsidate Admin Widget
 *
 * @author             Parsa Kafi
 * @package            WP-Parsidate
 * @subpackage         Core/General
 */

function wpp_add_dashboard_widgets() {
	wp_add_dashboard_widget(
		'wpp_planet_widget',
		__( 'WordPress Planet', 'wp-parsidate' ),
		'wpp_planet_widget_function'
	);
}

function wpp_planet_widget_function() {
	$rss = fetch_feed( 'http://wp-planet.ir/feed' );

	$max_items = $rss_items = 0;

	if ( ! is_wp_error( $rss ) ) {
		$max_items = $rss->get_item_quantity( 5 );
		$rss_items = $rss->get_items( 0, $max_items );
	}
	?>
    <div class="rss-widget">
        <ul>
			<?php if ( $max_items == 0 ) {
				echo "<li>" . __( 'No items', 'wp-parsidate' ) . "</li>";
			} else {
				$date_format = get_option( 'date_format' );
				foreach ( $rss_items as $item ) {
					?>
                    <li>
                        <a href="<?php echo esc_url( $item->get_permalink() ); ?>" target="_blank"
                           title="<?php echo esc_html( parsidate( "Y F d H:i:s", $item->get_date( "Y-m-d H:i:s" ) ) ); ?>">
							<?php echo esc_html( $item->get_title() ); ?>‌
                        </a>
                        <span class="rss-date"><?php echo esc_html( parsidate( $date_format, $item->get_date( "Y-m-d H:i:s" ) ) ); ?></span>
                        <div class="rssSummary">
							<?php //echo esc_url( $item->get_description() ); ?>
                        </div>
                    </li>
				<?php }
			} ?>
        </ul>
    </div>
	<?php
}