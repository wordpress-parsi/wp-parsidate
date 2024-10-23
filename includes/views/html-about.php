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
		'avatar' => 'https://avatars.githubusercontent.com/u/935397',
		'social' => array(
			'x-twitter' => '#',
			'linkedin'  => '#',
		),
	),
	'jane_smith' => array(
		'name'   => esc_html__( 'Jane Smith', 'wp-parsidate' ),
		'role'   => esc_html__( 'CTO', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/935397',
		'social' => array(
			'x-twitter' => '#',
			'linkedin'  => '#',
		)
	),
	'john_doe2'   => array(
		'name'   => esc_html__( 'John Doe', 'wp-parsidate' ),
		'role'   => esc_html__( 'CEO', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/935397',
		'social' => array(
			'x-twitter' => '#',
			'linkedin'  => '#',
		),
	),
	'jane_smith2' => array(
		'name'   => esc_html__( 'Jane Smith', 'wp-parsidate' ),
		'role'   => esc_html__( 'CTO', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/935397',
		'social' => array(
			'x-twitter' => '#',
			'linkedin'  => '#',
		)
	),
	'john_do3e'   => array(
		'name'   => esc_html__( 'John Doe', 'wp-parsidate' ),
		'role'   => esc_html__( 'CEO', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/935397',
		'social' => array(
			'x-twitter' => '#',
			'linkedin'  => '#',
		),
	),
	'jane_smith3' => array(
		'name'   => esc_html__( 'Jane Smith', 'wp-parsidate' ),
		'role'   => esc_html__( 'CTO', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/935397',
		'social' => array(
			'x-twitter' => '#',
			'linkedin'  => '#',
		)
	)
	// Add more team members as needed
);

$social_accounts = array(
	'instagram' => '#',
	'telegram'  => '#',
	'x-twitter' => '#',
	'linkedin'  => '#',
	'github'    => '#',
);
?>
<section class="about-us wpp-team">
    <h1><?php esc_html_e( 'About Us', 'wp-parsidate' ); ?></h1>
    <p>گسترش محبوبيت و روز افزون سيستم مديريت محتواي وردپرس در ايران و جهان اين امر را ايجاب كرده تا هر روز نياز بيشتري به وجود منابع كامل در اين زمينه احساس شود. گروه وردپرس پارسي در ۱ ارديبهشت ماه ۱۳۹۱ با همكاري افراد فعال و متخصص وردپرس در جهت  پشتيباني و رفع كمبود‌هايي كه در وردپس پارسي مشاهده مي‌شود و براي توسعه و پيشرفت هر چه بيشتر آن در ايران ايجاد گرديده و در اين راستا تلاش ميكند . وردپرس پارسي كاملا كاربر محور بوده و رضايت كاربر در آن مهمتر از هر چيزي است .</p>
    <p> گروه وردپرس پارسی جهت رفع نیازهاو و حمایت و پشتیبانی کاربران، ارائه سرویس ها و خدمات نوین و همچنین توسعه هرچه بیشتر وردپرس در ایران تشکیل یافت. گروه وردپرس پارسی به صورت کاملاً جدا اما همراستا با سایت مرجع وردپرس فارسی فعالیت می کند.</p>
    <div class="follow-us">
        <h3><?php esc_html_e( 'Follow Us', 'wp-parsidate' ); ?></h3>
        <div class="social-links">
			<?php foreach ( $social_accounts as $platform => $url ) : ?>
                <a href="<?php echo esc_url( $url ); ?>" target="_blank"><img src="<?php echo esc_url( WP_PARSI_URL . "assets/svg/$platform-brands-solid.svg" ); ?>" alt="<?php echo esc_attr( ucfirst( $platform ) ); ?>"></a>
			<?php endforeach; ?>
        </div>
    </div>
</section>

<section class="wpp-team">
    <h1><?php esc_html_e( 'Our Team', 'wp-parsidate' ); ?></h1>

	<div class="contributer-list">
		<div class="contributer-list__track">
			<div class="contributer-list__grid">

				<?php foreach ( $team_members as $member ) : ?>
				<div class="contributer-list-item">
					<div div="" class="contributer-list-info">
						<div div="" class="contributer-list-name"><?php echo esc_html( $member['name'] ); ?></div>
						<div div="" class="contributer-list-title"><?php echo esc_html( $member['role'] ); ?></div>

						<ul class="contributer-list-tags">
							<?php foreach ( $member['social'] as $platform => $url ) : ?>
							<li class="contributer-list-tag"><a href="<?php echo esc_url( $url ); ?>"><img src="<?php echo esc_url( WP_PARSI_URL . "assets/svg/$platform-brands-solid.svg" ); ?>" width="15" height="15"></a></li>
							<?php endforeach; ?>
						</ul>
					</div>

					<div div="" class="contributer-list-image">
						<img alt="<?php echo esc_attr( $member['name'] ); ?>" width="300" height="300" src="<?php echo esc_url( $member['avatar'] ); ?>" loading="lazy">
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
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
