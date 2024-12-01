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
	'man4toman'   => array(
		'name'   => esc_html__( 'Morteza Geransayeh', 'wp-parsidate' ),
		'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/935397',
		'social' => array(
			'x-twitter' => 'https://x.com/man4toman/',
			'linkedin'  => 'https://www.linkedin.com/in/morteza-geransayeh-27a70829/',
			'github'    => 'https://github.com/man4toman',
		),
	),
	'saeed_fard' => array(
		'name'   => esc_html__( 'Saeed Fard', 'wp-parsidate' ),
		'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/8706783',
		'social' => array(
			'x-twitter' => 'https://x.com/saeed_fard',
			'linkedin'  => 'https://www.linkedin.com/in/saeed-fard/',
			'github'    => 'https://github.com/SaeedFard',
		)
	),
	'yazdaniwp'   => array(
		'name'   => esc_html__( 'HamidReza Yazdani', 'wp-parsidate' ),
		'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/40775953',
		'social' => array(
			'x-twitter' => '#https://twitter.com/yazdani_wp',
			'linkedin'  => 'https://www.linkedin.com/in/hamid-reza-yazdani/',
			'github'    => 'https://github.com/hamidrezayazdani',
		),
	),
	'lord_viper' => array(
		'name'   => esc_html__( 'Mobin Ghasempoor', 'wp-parsidate' ),
		'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/5211249',
		'social' => array(
			'x-twitter' => 'https://twitter.com/GhasempoorMobin',
			'linkedin'  => 'https://www.linkedin.com/in/mobin-ghasempoor-07580788/',
			'github'    => 'https://github.com/lord-viper',
		)
	),
	'parsakafi'   => array(
		'name'   => esc_html__( 'Parsa Kafi', 'wp-parsidate' ),
		'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
		'avatar' => 'https://avatars.githubusercontent.com/u/7957513',
		'social' => array(
			'x-twitter' => 'https://x.com/parsakafi',
			'linkedin'  => 'https://www.linkedin.com/in/parsakafi/',
			'github'    => 'https://github.com/parsakafi',
		),
	),
	'mohsengham' => array(
		'name'   => esc_html__( 'Mohsen Ghiasi', 'wp-parsidate' ),
		'role'   => esc_html__( 'DevOps', 'wp-parsidate' ),
		'avatar' => 'https://secure.gravatar.com/avatar/64d4e212c9263a72b2fcadbe3951342f?s=300&d=mm&r=g',
		'social' => array(
			'x-twitter' => 'https://x.com/mohsengham',
			'linkedin'  => 'https://www.linkedin.com/in/mohsen-ghiasi/',
		)
	)
	// Add more team members as needed
);

$social_accounts = array(
	'instagram' => 'https://www.instagram.com/irwpmeetup',
	'telegram'  => 'https://t.me/wp_Community',
	'x-twitter' => 'https://x.com/wpparsi',
	'linkedin'  => 'https://www.linkedin.com/company/wp-parsi',
	'github'    => 'https://github.com/wordpress-parsi/wp-parsidate/',
);
?>
<section class="about-us wpp-team">
    <h1><?php esc_html_e( 'About Us', 'wp-parsidate' ); ?></h1>
	<p>افزونه شمسی ساز پارسی دیت یکی از ابزارهای کاربردی و مفید برای کاربران وردپرس فارسی است که امکان شمسی کردن تاریخ وردپرس را برای کاربرانش فراهم می‌کند. این افزونه در سال ۱۳۹۲ توسط تیم توسعه وردپرس پارسی ایجاد شد و با هدف ساده‌سازی فرآیند نمایش تاریخ‌ها در وب‌سایت‌های فارسی زبان به کار می‌رود.</p>
	<p>این افزونه در طی سال‌ها مورد استفاده محبوب‌ترین سایت‌های وردپرسی فارسی زبان بوده و افراد بسیاری در توسعه این افزونه مشارکت داشته‌اند. پارسی‌دیت در تمام این مدت به صورت رایگان توسعه داده شده و در اختیار کاربران بوده و توسط تیم وردپرس پارسی پشتیبانی شده است.</p>
	<p>این افزونه به صورت منظم آپدیت می‌شود و همواره نیازهای کاربران وردپرس فارسی را تحت پوشش قرار می‌دهد.</p>
	<p>برخی از ویژگی‌های مهم افزونه:<br>
	- شمسی‌سازی تاریخ وردپرس و ووکامرس<br>
	- شمسی‌سازی افزونه‌های پرطرفدار وردپرس<br>
	- سازگاری با آخرین نسخه‌های وردپرس و ووکامرس<br>
	- نصب و استفاده آسان و کاربردی<br>
	- اضافه کردن امکانات مورد نیاز برای وب‌سایت‌های فارسی</p>
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

	<div class="contributor-list">
		<div class="contributor-list__track">
			<div class="contributor-list__grid">

				<?php foreach ( $team_members as $member ) : ?>
				<div class="contributor-list-item">
					<div div="" class="contributor-list-info">
						<ul class="contributor-list-tags">
							<?php foreach ( $member['social'] as $platform => $url ) : ?>
							<li class="contributor-list-tag"><a href="<?php echo esc_url( $url ); ?>"><img src="<?php echo esc_url( WP_PARSI_URL . "assets/svg/$platform-brands-solid.svg" ); ?>" width="15" height="15"></a></li>
							<?php endforeach; ?>
						</ul>
					</div>

					<div div="" class="contributor-list-image">
						<img alt="<?php echo esc_attr( $member['name'] ); ?>" width="300" height="300" src="<?php echo esc_url( $member['avatar'] ); ?>" loading="lazy">
					</div>
					
					<div div="" class="contributor-list-name"><?php echo esc_html( $member['name'] ); ?></div>
					<div div="" class="contributor-list-title"><?php echo esc_html( $member['role'] ); ?></div>

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
