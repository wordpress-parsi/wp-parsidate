<?php

namespace WPParsidate\Admin;

use WPParsidate\Addons\Addons;
use WPParsidate\Helper\Assets;
use WPParsidate\Helper\Cache;
use WPParsidate\Helper\JSON;
use WPParsidate\Helper\Notice;

defined( 'ABSPATH' ) || exit;

class AdminAbout {
  public const tab = 'about';
  public const icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
  <path stroke="#3c3c3c" stroke-linecap="round" stroke-width="1.5" d="M12 17v-6"/>
  <circle cx="1" cy="1" r="1" fill="#3c3c3c" transform="matrix(1 0 0 -1 11 9)"/>
  <path stroke="#3c3c3c" stroke-width="1.5" d="M2 12c0-4.714 0-7.071 1.464-8.536C4.93 2 7.286 2 12 2c4.714 0 7.071 0 8.535 1.464C22 4.93 22 7.286 22 12c0 4.714 0 7.071-1.465 8.535C19.072 22 16.714 22 12 22s-7.071 0-8.536-1.465C2 19.072 2 16.714 2 12Z"/>
</svg>';

  public function __construct() {
    add_action( 'wp_parsidate_' . self::tab . '_tab_header', [ $this, 'header' ] );
    add_action( 'wp_parsidate_' . self::tab . '_tab_content', [ $this, 'content' ] );
    add_filter( 'wp_parsidate_menus', [ $this, 'addMenu' ], 11 );
    add_action( 'wp_parsidate_admin_init', [ $this, 'notice' ] );
  }

  public function content(): void {
    $teamMembers        = self::getTeamMembers();
    $githubContributors = self::getGithubContributors();
    ?>
    <p>افزونه شمسی ساز پارسی دیت یکی از ابزارهای کاربردی و مفید برای کاربران وردپرس فارسی است که امکان شمسی کردن تاریخ
      وردپرس را برای کاربرانش فراهم می‌کند. این افزونه در سال ۱۳۹۲ توسط تیم توسعه وردپرس پارسی ایجاد شد و با هدف
      ساده‌سازی فرآیند نمایش تاریخ‌ها در وب‌سایت‌های فارسی زبان به کار می‌رود.</p>
    <p>این افزونه در طی سال‌ها مورد استفاده محبوب‌ترین سایت‌های وردپرسی فارسی زبان بوده و افراد بسیاری در توسعه این
      افزونه مشارکت داشته‌اند. پارسی‌دیت در تمام این مدت به صورت رایگان توسعه داده شده و در اختیار کاربران بوده و توسط
      تیم وردپرس پارسی پشتیبانی شده است.</p>

    <div class="plugin-features">
      <div class="feature-item">
        <strong>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24">
            <g stroke="#3c3c3c" stroke-width="1.5">
              <path
                d="M2 12c0-3.771 0-5.657 1.172-6.828C4.343 4 6.229 4 10 4h4c3.771 0 5.657 0 6.828 1.172C22 6.343 22 8.229 22 12v2c0 3.771 0 5.657-1.172 6.828C19.657 22 17.771 22 14 22h-4c-3.771 0-5.657 0-6.828-1.172C2 19.657 2 17.771 2 14v-2Z"/>
              <path stroke-linecap="round" d="M7 4V2.5M17 4V2.5"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="m9 14.5 1.5-1.5v4"/>
              <path stroke-linecap="round" d="M13 16v-2a1 1 0 1 1 2 0v2a1 1 0 1 1-2 0ZM2.5 9h19"/>
            </g>
          </svg>
          تاریخ پارسی
        </strong>
        <div>شمسی‌سازی تاریخ وردپرس و ووکامرس</div>
      </div>
      <div class="feature-item">
        <strong>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24">
            <g stroke="#3c3c3c" stroke-width="1.5">
              <path
                d="M2 12c0-3.771 0-5.657 1.172-6.828C4.343 4 6.229 4 10 4h4c3.771 0 5.657 0 6.828 1.172C22 6.343 22 8.229 22 12v2c0 3.771 0 5.657-1.172 6.828C19.657 22 17.771 22 14 22h-4c-3.771 0-5.657 0-6.828-1.172C2 19.657 2 17.771 2 14v-2Z"/>
              <path stroke-linecap="round" d="M7 4V2.5M17 4V2.5"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="m9 14.5 1.5-1.5v4"/>
              <path stroke-linecap="round" d="M13 16v-2a1 1 0 1 1 2 0v2a1 1 0 1 1-2 0ZM2.5 9h19"/>
            </g>
          </svg>
          انتخابگر تاریخ
        </strong>
        <div>انتخاب‌کننده تاریخ شمسی ویرایشگر کلاسیک و گوتنبرگ</div>
      </div>
      <div class="feature-item">
        <strong>
          <?php
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo Addons::icon;
          ?>
          هماهنگی با افزونه‌ها
        </strong>
        <div>هماهنگی با افزونه‌های کاربردی مانند ووکامرس، دانلود آسان دیجیتال، ACF، رنک‌مث، المنتور.</div>
      </div>
      <div class="feature-item">
        <strong>
          <?php
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo AdminConvert::icon;
          ?>
          اصلاح حروف
        </strong>
        <div>اصلاح اعداد و حروف غیر فارسی</div>
      </div>
    </div>

    <div class="social-networks">
      <strong>ما را در شبکه‌های اجتماعی دنبال کنید</strong>
      <div>
        <a href="https://www.instagram.com/irwpmeetup" title="Instagram" target="_blank">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 -.5 25 25">
            <path stroke="#3c3c3c" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M17.5.324H7.486A6.676 6.676 0 0 0 .81 7v10.014a6.676 6.676 0 0 0 6.676 6.676H17.5a6.675 6.675 0 0 0 6.676-6.676V7A6.675 6.675 0 0 0 17.5.324Z"
                  clip-rule="evenodd"/>
            <path stroke="#3c3c3c" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M12.493 17.014a5.008 5.008 0 1 1 .002-10.016 5.008 5.008 0 0 1-.002 10.016Z" clip-rule="evenodd"/>
            <rect width="3.338" height="3.338" x="25.869" y="15.021" fill="#3c3c3c" rx="1"
                  transform="rotate(-90 17.674 15.195)"/>
            <rect width="1.669" height="1.669" x="26.704" y="14.186" stroke="#3c3c3c" stroke-linecap="round" rx=".5"
                  transform="rotate(-90 18.509 14.36)"/>
          </svg>
        </a>

        <a href="https://www.linkedin.com/company/wp-parsi" title="Linkedin" target="_blank">
          <svg version="1.1" id="Icons" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32.00 32.00" width="24"
               height="24" fill="#000000">
            <g id="SVGRepo_bgCarrier" stroke-width="0"/>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>
            <g id="SVGRepo_iconCarrier">
              <style type="text/css"> .st0 {
                  fill: none;
                  stroke: #3c3c3c;
                  stroke-width: 2;
                  stroke-linecap: round;
                  stroke-linejoin: round;
                  stroke-miterlimit: 10;
                }

                .st1 {
                  fill: none;
                  stroke: #3c3c3c;
                  stroke-width: 2;
                }

                .st2 {
                  fill: none;
                  stroke: #3c3c3c;
                  stroke-width: 2;
                  stroke-miterlimit: 10;
                } </style>
              <path class="st0"
                    d="M23,31H9c-4.4,0-8-3.6-8-8V9c0-4.4,3.6-8,8-8h14c4.4,0,8,3.6,8,8v14C31,27.4,27.4,31,23,31z"/>
              <rect x="7" y="13" class="st0" width="4" height="12" style="stroke-width: 1.5px;"/>
              <path class="st0"
                    d="M20.5,13c-0.9,0-1.8,0.3-2.5,0.8V13h-4v12h2h2v-6.5c0-0.8,0.7-1.5,1.5-1.5s1.5,0.7,1.5,1.5V25h4v-7.5 C25,15,23,13,20.5,13z"
                    style="stroke-width: 1.5px;"/>
              <circle class="st0" cx="9" cy="8" r="2" style="stroke-width: 1.5px;"/>
            </g>
          </svg>
        </a>

        <a href="https://x.com/wpparsi" title="Twitter / X" target="_blank">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
            <path stroke="#3c3c3c" stroke-linecap="round" stroke-linejoin="round"
                  d="M16.715 1h-9.43A6.285 6.285 0 0 0 1 7.286v9.428A6.285 6.285 0 0 0 7.285 23h9.43A6.284 6.284 0 0 0 23 16.714V7.286A6.285 6.285 0 0 0 16.715 1Z"
                  clip-rule="evenodd" style="stroke-width:1.5px"/>
            <g transform="translate(-4.466 3.553) scale(.71846)">
              <path
                d="M27.585 4.519h2.454l-5.36 6.142 6.306 8.358h-4.937l-3.867-5.07-4.425 5.07h-2.455l5.733-6.57-6.049-7.93h5.063l3.495 4.633 4.043-4.633h-.001Zm-.86 13.028h1.36L19.308 5.914H17.85l8.875 11.633Z"
                style="stroke-width:1px;stroke:#3c3c3c"/>
            </g>
          </svg>
        </a>

        <a href="https://t.me/wp_Community" title="Telegram" target="_blank">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
            <path stroke="#3c3c3c" stroke-linecap="round" stroke-linejoin="round"
                  d="M16.715 1h-9.43A6.285 6.285 0 0 0 1 7.286v9.428A6.285 6.285 0 0 0 7.285 23h9.43A6.284 6.284 0 0 0 23 16.714V7.286A6.285 6.285 0 0 0 16.715 1Z"
                  clip-rule="evenodd" style="stroke-width:1.5px"/>
            <path stroke="#3c3c3c" stroke-width=".97296"
                  d="M6.764 11.312s5.307-2.171 7.148-2.936c.706-.306 3.099-1.285 3.099-1.285s1.104-.428 1.012.612c-.03.428-.276 1.927-.522 3.548-.368 2.294-.766 4.802-.766 4.802s-.062.703-.583.825c-.522.123-1.38-.428-1.534-.55-.123-.092-2.301-1.468-3.099-2.141-.214-.183-.46-.55.03-.979a116.947 116.947 0 0 0 3.222-3.058c.368-.367.736-1.224-.797-.184-2.179 1.499-4.326 2.906-4.326 2.906s-.49.306-1.411.03c-.92-.275-1.994-.642-1.994-.642s-.737-.459.521-.948Z"/>
          </svg>
        </a>
        <a href="https://github.com/wordpress-parsi/wp-parsidate" title="Github" target="_blank">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
            <path stroke="#3c3c3c" stroke-linecap="round" stroke-linejoin="round"
                  d="M16.715 1h-9.43A6.285 6.285 0 0 0 1 7.286v9.428A6.285 6.285 0 0 0 7.285 23h9.43A6.284 6.284 0 0 0 23 16.714V7.286A6.285 6.285 0 0 0 16.715 1Z"
                  clip-rule="evenodd" style="stroke-width:1.5px"/>
            <path fill="#3c3c3c"
                  d="M10.303 16.965a.577.577 0 1 0-.346-1.1l.346 1.1Zm-4.21-3.416-.557-.146-.293 1.116.558.146.293-1.116Zm8.075 5.75v.578h1.154V19.3h-1.154Zm.072-5.552-.061-.574a.577.577 0 0 0-.345.983l.406-.41Zm3.967-4.356h.577V9.39l-.577.002Zm-.961-2.315-.538-.209a.577.577 0 0 0 .129.617l.409-.408Zm-.058-2.327.527-.235a.577.577 0 0 0-.369-.32l-.158.555Zm-2.507.914-.145.558c.156.04.322.014.458-.074l-.313-.484Zm-4.487 0-.313.484a.577.577 0 0 0 .459.074l-.146-.558Zm-2.507-.914-.158-.555a.577.577 0 0 0-.368.32l.526.235ZM7.63 7.076l.408.408a.577.577 0 0 0 .13-.617l-.538.21ZM6.668 9.41h.577v-.002l-.577.002Zm3.967 4.337.407.409a.577.577 0 0 0-.339-.982l-.068.573Zm-.505 1.514h.577a.58.58 0 0 0-.002-.042l-.575.042ZM9.553 19.3v.577h1.154V19.3H9.553Zm.404-3.435c-.696.219-1.138.195-1.43.098-.29-.096-.514-.289-.726-.566a5.54 5.54 0 0 1-.311-.463c-.1-.16-.212-.349-.325-.516-.22-.325-.545-.73-1.071-.869L5.8 14.665c.091.025.212.11.41.4.094.14.184.292.297.475.108.175.233.37.377.56.292.38.69.764 1.28.959.589.194 1.289.174 2.138-.094l-.346-1.1Zm5.365-.676c0-.366-.016-.7-.12-1.013-.11-.332-.302-.587-.555-.838l-.813.818c.179.178.24.284.273.385.04.121.061.293.061.648h1.154Zm-1.02-.869c1.042-.111 2.179-.374 3.054-1.132.9-.78 1.428-1.995 1.428-3.797H17.63c0 1.569-.45 2.423-1.03 2.925-.604.523-1.45.753-2.421.857l.123 1.147Zm4.482-4.93a3.87 3.87 0 0 0-1.13-2.721l-.817.815c.506.507.79 1.193.793 1.909l1.154-.004Zm-1-2.105c.172-.443.254-.917.242-1.392l-1.153.028c.008.323-.048.645-.165.946l1.075.418Zm.242-1.392a3.599 3.599 0 0 0-.311-1.38l-1.054.471c.132.296.204.614.212.937l1.153-.028Zm-.838-1.144.157-.555h-.003l-.005-.002a.533.533 0 0 0-.035-.009 1.455 1.455 0 0 0-.245-.027 2.506 2.506 0 0 0-.587.062c-.491.105-1.182.366-2.102.96l.626.97c.83-.537 1.392-.732 1.717-.802a1.374 1.374 0 0 1 .353-.034l-.009-.002a.32.32 0 0 1-.01-.002l-.008-.002-.004-.001h-.003l.158-.556Zm-2.361.356a9.472 9.472 0 0 0-4.778 0l.29 1.116a8.318 8.318 0 0 1 4.197 0l.29-1.116Zm-4.32.073c-.92-.594-1.611-.855-2.102-.96a2.504 2.504 0 0 0-.588-.062 1.452 1.452 0 0 0-.28.036l-.004.001h-.002l-.001.001.157.555.158.555h-.003l-.004.002a.377.377 0 0 1-.018.004l-.009.002h.01a1.373 1.373 0 0 1 .344.035c.324.069.886.264 1.716.8l.626-.969Zm-3.346-.664a3.6 3.6 0 0 0-.312 1.379l1.153.028c.008-.323.08-.641.212-.937l-1.053-.47Zm-.312 1.379a3.6 3.6 0 0 0 .243 1.392l1.075-.418a2.446 2.446 0 0 1-.165-.946L6.85 5.893Zm.372.776a3.87 3.87 0 0 0-.838 1.259l1.067.439c.136-.33.336-.63.588-.883l-.817-.815Zm-.838 1.259a3.87 3.87 0 0 0-.292 1.483l1.154-.003c0-.357.069-.71.205-1.041l-1.067-.44ZM6.091 9.41c0 1.792.529 2.996 1.43 3.77.874.751 2.008 1.016 3.045 1.14l.137-1.146c-.976-.117-1.824-.348-2.43-.869-.58-.497-1.028-1.34-1.028-2.895H6.091Zm4.136 3.928c-.576.575-.724 1.258-.672 1.966l1.15-.085c-.034-.471.061-.788.337-1.063l-.815-.818Zm-.674 1.923V19.3h1.154V15.26H9.553Zm5.77 4.039v-1.731h-1.155v1.73h1.154Zm0-1.731v-2.38h-1.155v2.38h1.154Z"/>
          </svg>
        </a>
      </div>
    </div>

    <!--<strong><?php /*esc_html_e( 'Our Team', 'wp-parsidate' ); */ ?></strong>-->
    <div class="team-members">
      <?php
      foreach ( $teamMembers as $member ) {
        echo '<div class="team-member">';
        echo '<div class="team-member-image" style="background-image: url(' . esc_url_raw( $member['avatar'] ) . ')">';
        echo '</div><div class="team-member-info">';
        echo '<div class="team-member-name">' . esc_html( $member['name'] ) . '</div>';
        echo '<div class="team-member-role">' . esc_html( $member['role'] ) . '</div>';
        echo '</div>';
        if ( ! empty( $member['social'] ) ) {
          echo '<div class="team-member-social">';
          foreach ( $member['social'] as $social => $link ) {
            if ( $icon = $this->getSocialIcon( $social ) ) {
              echo '<a href="' . esc_url_raw( $link ) . '" target="_blank">' .
                   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                   Assets::setSvgDimensions( $icon, 15 ) .
                   '</a>';
            }
          }
          echo '</div>';
        }
        echo '</div>';
      }
      ?>
    </div>
    <?php

    if ( ! empty( $githubContributors ) ) {
      echo '<div class="github-contributors-wrapper"><strong>' . esc_html__( 'GitHub Contributors',
          'wp-parsidate' ) . '</strong>';
      echo '<div class="github-contributors">';
      foreach ( $githubContributors as $contributor ) {
        echo '<a href="' . esc_url_raw( $contributor['url'] ) . '" title="' . esc_html( $contributor['username'] ) . '" target="_blank"><img src="' . esc_url_raw( $contributor['avatar'] ) . '"></a>';
      }
      echo '</div></div>';
    }
  }

  private static function getTeamMembers(): array {
    return array(
      'man4toman'        => array(
        'name'   => esc_html__( 'Morteza Geransayeh', 'wp-parsidate' ),
        'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
        'avatar' => 'https://avatars.githubusercontent.com/u/935397',
        'social' => array(
          'x-twitter' => 'https://x.com/man4toman/',
          'linkedin'  => 'https://www.linkedin.com/in/morteza-geransayeh-27a70829/',
          'github'    => 'https://github.com/man4toman',
        ),
      ),
      'SaeedFard'        => array(
        'name'   => esc_html__( 'Saeed Fard', 'wp-parsidate' ),
        'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
        'avatar' => 'https://avatars.githubusercontent.com/u/8706783',
        'social' => array(
          'x-twitter' => 'https://x.com/saeed_fard',
          'linkedin'  => 'https://www.linkedin.com/in/saeed-fard/',
          'github'    => 'https://github.com/SaeedFard',
        )
      ),
      'hamidrezayazdani' => array(
        'name'   => esc_html__( 'HamidReza Yazdani', 'wp-parsidate' ),
        'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
        'avatar' => 'https://avatars.githubusercontent.com/u/40775953',
        'social' => array(
          'x-twitter' => '#https://twitter.com/yazdani_wp',
          'linkedin'  => 'https://www.linkedin.com/in/hamid-reza-yazdani/',
          'github'    => 'https://github.com/hamidrezayazdani',
        ),
      ),
      'lord-viper'       => array(
        'name'   => esc_html__( 'Mobin Ghasempoor', 'wp-parsidate' ),
        'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
        'avatar' => 'https://avatars.githubusercontent.com/u/5211249',
        'social' => array(
          'x-twitter' => 'https://twitter.com/GhasempoorMobin',
          'linkedin'  => 'https://www.linkedin.com/in/mobin-ghasempoor-07580788/',
          'github'    => 'https://github.com/lord-viper',
        )
      ),
      'mohsengham'       => array(
        'name'   => esc_html__( 'Mohsen Ghiasi', 'wp-parsidate' ),
        'role'   => esc_html__( 'DevOps', 'wp-parsidate' ),
        'avatar' => 'https://avatars.githubusercontent.com/u/652359',
        'social' => array(
          'x-twitter' => 'https://x.com/mohsengham',
          'linkedin'  => 'https://www.linkedin.com/in/mohsen-ghiasi/',
          'github'    => 'https://github.com/mohsengham',
        ),
      ),
      'parsakafi'        => array(
        'name'   => esc_html__( 'Parsa Kafi', 'wp-parsidate' ),
        'role'   => esc_html__( 'Developer', 'wp-parsidate' ),
        'avatar' => 'https://avatars.githubusercontent.com/u/7957513',
        'social' => array(
          'x-twitter' => 'https://x.com/parsakafi',
          'linkedin'  => 'https://www.linkedin.com/in/parsakafi/',
          'github'    => 'https://github.com/parsakafi',
        ),
      )
      // Add more team members as needed
    );
  }

  private static function getGithubContributors(): array {
    $githubContributors = Cache::get( 'github_contributors' );
    if ( is_array( $githubContributors ) && ! empty( $githubContributors ) ) {
      return $githubContributors;
    }

    $teamMembers        = self::getTeamMembers();
    $githubContributors = [];
    $response           = wp_remote_get( 'https://api.github.com/repos/wordpress-parsi/wp-parsidate/contributors' );
    if ( is_array( $response ) && ! is_wp_error( $response ) ) {
      $contributors = JSON::decode( wp_remote_retrieve_body( $response ) );
      foreach ( $contributors as $contributor ) {
        // Skip if the contributor is a team member
        if ( array_key_exists( $contributor->login, $teamMembers ) ) {
          continue;
        }

        $githubContributors[] = array(
          'username' => $contributor->login,
          'url'      => $contributor->html_url,
          'avatar'   => $contributor->avatar_url
        );
      }

      Cache::set( 'github_contributors', $githubContributors, WEEK_IN_SECONDS );
    }

    return $githubContributors;
  }

  private function getSocialIcon( $key ) {
    $icons = array(
      'linkedin'  => '<svg version="1.1" id="Icons" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#000000">
  <g id="SVGRepo_bgCarrier" stroke-width="0"/>
  <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>
  <g id="SVGRepo_iconCarrier" style="" transform="matrix(0.76817, 0, 0, 0.76817, -0.029587, -0.429386)">
    <style type="text/css"> .st0 {
                  fill: none;
                  stroke: #3c3c3c;
                  stroke-width: 2;
                  stroke-linecap: round;
                  stroke-linejoin: round;
                  stroke-miterlimit: 10;
                }

                .st1 {
                  fill: none;
                  stroke: #3c3c3c;
                  stroke-width: 2;
                }

                .st2 {
                  fill: none;
                  stroke: #3c3c3c;
                  stroke-width: 2;
                  stroke-miterlimit: 10;
                } </style>
    <rect x="3" y="12.204" class="st0" width="5.649" height="16.948" style="stroke-width: 1.5px;"/>
    <path class="st0" d="M 22.067 12.204 C 20.796 12.204 19.525 12.628 18.536 13.334 L 18.536 12.204 L 12.886 12.204 L 12.886 29.153 L 15.711 29.153 L 18.536 29.153 L 18.536 19.972 C 18.536 18.843 19.525 17.854 20.654 17.854 C 21.784 17.854 22.773 18.843 22.773 19.972 L 22.773 29.153 L 28.422 29.153 L 28.422 18.56 C 28.422 15.029 25.598 12.204 22.067 12.204 Z" style="stroke-width: 1.5px;"/>
    <circle class="st0" cx="5.825" cy="5.143" r="2.825" style="stroke-width: 1.5px;"/>
  </g>
</svg>',
      'x-twitter' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24" xmlns:bx="https://boxy-svg.com">
  <defs>
    <bx:guide x="10.843" y="12.015" angle="90"/>
    <bx:guide x="12.033" y="7.187" angle="0"/>
  </defs>
  <g id="Twitter-X--Streamline-Bootstrap" transform="matrix(1.227312, 0, 0, 1.227312, -16.149389, -2.401746)" style="">
    <desc>
    Twitter X Streamline Icon: https://streamlinehq.com
  </desc>
    <path d="M 27.585 4.519 L 30.039 4.519 L 24.679 10.661 L 30.985 19.019 L 26.048 19.019 L 22.181 13.949 L 17.756 19.019 L 15.301 19.019 L 21.034 12.449 L 14.985 4.519 L 20.048 4.519 L 23.543 9.152 L 27.586 4.519 L 27.585 4.519 Z M 26.725 17.547 L 28.085 17.547 L 19.308 5.914 L 17.85 5.914 L 26.725 17.547 Z" stroke-width="1" style="stroke-width: 1px; stroke: rgb(60, 60, 60);"/>
  </g>
</svg>',
      'github'    => '<svg width="24" height="24" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
<g id="SVGRepo_bgCarrier" stroke-width="0"/>
<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>
<g id="SVGRepo_iconCarrier"> <path d="M5.65021 12.4769C5.9136 12.3939 6.05986 12.1132 5.9769 11.8498C5.89394 11.5864 5.61317 11.4401 5.34979 11.5231L5.65021 12.4769ZM2.00197 9.51639L1.51836 9.38942L1.26442 10.3566L1.74803 10.4836L2.00197 9.51639ZM9 14.5V15H10V14.5H9ZM9.0625 9.6875L9.00906 9.19036C8.81734 9.21097 8.65455 9.33999 8.5907 9.52194C8.52686 9.70389 8.57333 9.90634 8.71013 10.0422L9.0625 9.6875ZM12.5 5.9125H13L13 5.91089L12.5 5.9125ZM11.6669 3.90625L11.2008 3.72524C11.1291 3.90974 11.1731 4.11914 11.3128 4.2593L11.6669 3.90625ZM11.6169 1.88937L12.0734 1.68549C12.0132 1.55057 11.8963 1.44921 11.7542 1.40861L11.6169 1.88937ZM9.44438 2.68125L9.31826 3.16508C9.45382 3.20042 9.59799 3.17726 9.71566 3.10125L9.44438 2.68125ZM5.55562 2.68125L5.28442 3.1013C5.40208 3.17727 5.54621 3.20041 5.68174 3.16508L5.55562 2.68125ZM3.38313 1.88937L3.24564 1.40865C3.10362 1.44926 2.98682 1.55061 2.92658 1.68549L3.38313 1.88937ZM3.33313 3.90625L3.68715 4.25933C3.82693 4.11917 3.87087 3.90975 3.79921 3.72524L3.33313 3.90625ZM2.5 5.92875H3L3 5.92747L2.5 5.92875ZM5.9375 9.6875L6.29045 10.0417C6.42616 9.9064 6.47265 9.7055 6.41015 9.52439C6.34765 9.34328 6.18715 9.21381 5.99691 9.19104L5.9375 9.6875ZM5.5 11H6C6 10.9878 5.99955 10.9756 5.99866 10.9634L5.5 11ZM5 14.5V15H6V14.5H5ZM5.34979 11.5231C4.74678 11.713 4.3643 11.6918 4.11084 11.6082C3.85886 11.525 3.66567 11.3578 3.4814 11.1175C3.38808 10.9959 3.3021 10.8623 3.21181 10.7161C3.12597 10.5771 3.02879 10.4142 2.93047 10.2692C2.73966 9.9878 2.45815 9.63616 2.00197 9.51639L1.74803 10.4836C1.82748 10.5045 1.93206 10.5786 2.10281 10.8304C2.18527 10.952 2.26274 11.0825 2.36104 11.2417C2.4549 11.3936 2.56254 11.5627 2.68797 11.7262C2.9412 12.0563 3.28599 12.389 3.79744 12.5578C4.30742 12.7261 4.91384 12.7088 5.65021 12.4769L5.34979 11.5231ZM10 10.9375C10 10.6201 9.98611 10.3301 9.89602 10.0596C9.8001 9.77159 9.63428 9.55071 9.41487 9.33276L8.71013 10.0422C8.8651 10.1962 8.91802 10.2878 8.94726 10.3756C8.98232 10.4809 9 10.6299 9 10.9375H10ZM9.11594 10.1846C10.0189 10.0876 11.004 9.85986 11.7626 9.20319C12.5429 8.52773 13 7.47445 13 5.9125H12C12 7.2718 11.6105 8.01226 11.1081 8.44712C10.5841 8.90076 9.8505 9.09992 9.00906 9.19036L9.11594 10.1846ZM13 5.91089C12.9971 5.02667 12.6453 4.17934 12.0209 3.5532L11.3128 4.2593C11.751 4.69877 11.998 5.29349 12 5.91411L13 5.91089ZM12.133 4.08726C12.2823 3.70286 12.3539 3.29267 12.3436 2.88041L11.3439 2.9052C11.3509 3.18532 11.3022 3.46404 11.2008 3.72524L12.133 4.08726ZM12.3436 2.88041C12.3334 2.46816 12.2416 2.06202 12.0734 1.68549L11.1603 2.09326C11.2746 2.34911 11.337 2.62508 11.3439 2.9052L12.3436 2.88041ZM11.6169 1.88937C11.7542 1.40861 11.7538 1.40848 11.7533 1.40834C11.7531 1.4083 11.7527 1.40816 11.7523 1.40807C11.7517 1.40789 11.751 1.40771 11.7504 1.40752C11.749 1.40716 11.7477 1.40678 11.7462 1.4064C11.7434 1.40564 11.7404 1.40485 11.7372 1.40405C11.7309 1.40245 11.7239 1.40078 11.7161 1.39909C11.7007 1.39571 11.6826 1.39225 11.6617 1.38904C11.6199 1.38261 11.5674 1.37725 11.504 1.37556C11.3769 1.37216 11.2079 1.38354 10.9947 1.42908C10.5691 1.52001 9.97001 1.7465 9.17309 2.26125L9.71566 3.10125C10.435 2.63663 10.9221 2.46717 11.2037 2.40701C11.3441 2.37701 11.434 2.37404 11.4773 2.3752C11.4991 2.37578 11.5097 2.37742 11.5097 2.37742C11.5098 2.37743 11.5072 2.37704 11.5021 2.37592C11.4996 2.37537 11.4964 2.37463 11.4927 2.37368C11.4908 2.3732 11.4887 2.37267 11.4865 2.37209C11.4854 2.37179 11.4843 2.37148 11.4831 2.37116C11.4826 2.37099 11.482 2.37083 11.4814 2.37066C11.4811 2.37057 11.4806 2.37044 11.4804 2.3704C11.48 2.37027 11.4795 2.37014 11.6169 1.88937ZM9.57049 2.19742C8.21284 1.84354 6.78716 1.84354 5.42951 2.19742L5.68174 3.16508C6.87399 2.85431 8.12601 2.85431 9.31826 3.16508L9.57049 2.19742ZM5.82683 2.2612C5.02965 1.74649 4.43054 1.52002 4.00504 1.42909C3.79191 1.38354 3.62298 1.37216 3.49591 1.37556C3.4325 1.37725 3.38003 1.38261 3.33822 1.38905C3.31734 1.39226 3.2992 1.39573 3.28376 1.39911C3.27604 1.4008 3.26901 1.40247 3.26266 1.40408C3.25948 1.40488 3.25647 1.40567 3.25364 1.40643C3.25222 1.40681 3.25085 1.40719 3.24951 1.40756C3.24885 1.40774 3.24819 1.40793 3.24755 1.40811C3.24722 1.4082 3.24675 1.40833 3.24659 1.40838C3.24611 1.40851 3.24564 1.40865 3.38313 1.88937C3.52061 2.3701 3.52014 2.37023 3.51968 2.37037C3.51953 2.37041 3.51907 2.37054 3.51876 2.37062C3.51816 2.3708 3.51756 2.37096 3.51698 2.37112C3.51581 2.37145 3.51467 2.37176 3.51357 2.37206C3.51138 2.37265 3.50934 2.37318 3.50745 2.37365C3.50367 2.37461 3.5005 2.37535 3.49795 2.3759C3.49286 2.37702 3.49028 2.37742 3.49029 2.37742C3.4903 2.37741 3.49295 2.37701 3.49829 2.37653C3.50363 2.37605 3.51172 2.37549 3.52262 2.3752C3.56589 2.37405 3.65567 2.377 3.79606 2.40701C4.07758 2.46717 4.56473 2.63664 5.28442 3.1013L5.82683 2.2612ZM2.92658 1.68549C2.75843 2.06202 2.66658 2.46816 2.65636 2.88041L3.65606 2.9052C3.663 2.62508 3.72541 2.34911 3.83967 2.09326L2.92658 1.68549ZM2.65636 2.88041C2.64614 3.29267 2.71775 3.70286 2.86704 4.08726L3.79921 3.72524C3.69777 3.46404 3.64911 3.18532 3.65606 2.9052L2.65636 2.88041ZM2.9791 3.55317C2.66761 3.8655 2.42079 4.23621 2.25278 4.64407L3.1774 5.02495C3.29532 4.7387 3.46854 4.47853 3.68715 4.25933L2.9791 3.55317ZM2.25278 4.64407C2.08477 5.05193 1.99887 5.48892 2 5.93003L3 5.92747C2.99921 5.61789 3.05949 5.31119 3.1774 5.02495L2.25278 4.64407ZM2 5.92875C2 7.48166 2.45806 8.52573 3.23874 9.19657C3.99602 9.84729 4.97941 10.0764 5.87809 10.184L5.99691 9.19104C5.15121 9.08984 4.41586 8.88958 3.89048 8.43812C3.3885 8.00677 3 7.27584 3 5.92875H2ZM5.58455 9.33335C5.08499 9.83121 4.95633 10.4236 5.00134 11.0366L5.99866 10.9634C5.96867 10.5551 6.05126 10.28 6.29045 10.0417L5.58455 9.33335ZM5 11V14.5H6V11H5ZM10 14.5V13H9V14.5H10ZM10 13V10.9375H9V13H10Z" fill="#3c3c3c"/> </g>
</svg>',
    );

    return $icons[ $key ] ?? '';
  }

  public function header(): void {
    AdminSettings::headerSettings( self::tab,
      [
        'title' => esc_html__( 'About Us', 'wp-parsidate' ),
        'desc'  => esc_html__( 'About the WordPress Parsi team and plugin', 'wp-parsidate' )
      ] );
  }

  public function notice(): void {
    if ( get_locale() !== 'fa_IR' ) {
      Notice::add( self::tab, esc_html__( 'The text of this page is in Persian.', 'wp-parsidate' ), 'warning' );
    }
  }

  public function addMenu( $menus ) {
    $menus[ self::tab ] = array(
      'title' => esc_html__( 'About', 'wp-parsidate' ),
      'icon'  => self::icon
    );

    return $menus;
  }
}
