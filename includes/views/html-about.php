<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * To not show team members in GitHub Contributors, you must set the array ID equal to the GitHub username
 *
 * Available brand logos:
 *  behance
 *  codepen
 *  facebook
 *  github
 *  google
 *  instagram
 *  linkedin
 *  medium
 *  stack-overflow
 *  telegram
 *  tiktok
 *  whatsapp
 *  wordpress
 *  x-twitter
 *  youtube
 */
$team_members = array(
	'john_doe'   => array(
		'name'   => esc_html__( 'John Doe', 'wp-parsidate' ),
		'role'   => esc_html__( 'CEO', 'wp-parsidate' ),
		'avatar' => WP_PARSI_URL . 'assets/images/avatar.png',
		'social' => array(
			'facebook'  => '#',
			'x-twitter' => '#',
			'linkedin'  => '#',
		),
	),
	'jane_smith' => array(
		'name'   => esc_html__( 'Jane Smith', 'wp-parsidate' ),
		'role'   => esc_html__( 'CTO', 'wp-parsidate' ),
		'avatar' => WP_PARSI_URL . 'assets/images/avatar.png',
		'social' => array(
			'facebook'  => '#',
			'x-twitter' => '#',
			'linkedin'  => '#',
		)
	)
	// Add more team members as needed
);

?>
<section class="wpp-team">
    <h1><?php esc_html_e( 'Our Team', 'wp-parsidate' ); ?></h1>
    <div class="wpp-team-members">
		<?php foreach ( $team_members as $member ) : ?>
            <div class="wpp-team-member">
                <img src="<?php echo esc_url( $member['avatar'] ); ?>" alt="<?php echo esc_attr( $member['name'] ); ?>" class="avatar" loading="lazy">
                <h2><?php echo esc_html( $member['name'] ); ?></h2>
                <h4><?php echo esc_html( $member['role'] ); ?></h4>
                <div class="social-links">
					<?php foreach ( $member['social'] as $platform => $url ) : ?>
                        <a href="<?php echo esc_url( $url ); ?>" target="_blank"><img src="<?php echo esc_url( WP_PARSI_URL . "assets/svg/$platform-brands-solid.svg" ); ?>" alt="<?php echo esc_attr( ucfirst( $platform ) ); ?>"></a>
					<?php endforeach; ?>
                </div>
            </div>
		<?php endforeach; ?>
    </div>
</section>

<section class="wpp-contributors">
    <h2><?php esc_html_e( 'GitHub Contributors', 'wp-parsidate' ); ?></h2>
    <div id="wpp-contributors-list">
		<?php
		$response = wp_remote_get( 'https://api.github.com/repos/wordpress-parsi/wp-parsidate/contributors' );
		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$contributors = json_decode( wp_remote_retrieve_body( $response ) );

			foreach ( $contributors as $contributor ) {
				// Skip if the contributor is a team member
				if ( array_key_exists( $contributor->login, $team_members ) ) {
					continue;
				}

				echo '<div class="wpp-contributor">';
				echo '<a href="' . esc_url( $contributor->html_url ) . '" target="_blank">';
				echo '<img src="' . esc_url( $contributor->avatar_url ) . '" alt="' . esc_attr( $contributor->login ) . '" loading="lazy">';
				echo '<div class="tooltip">' . esc_html( $contributor->login ) . '</div>';
				echo '</a>';
				echo '</div>';
			}
		} else {
			echo '<p>' . esc_html__( 'Unable to fetch contributors.', 'wp-parsidate' ) . '</p>';
		}
		?>
    </div>
</section>