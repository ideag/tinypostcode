<?php
/**
 * Plugin Name: tinyPostcode
 * Plugin URI: https://api.aru.lt
 * Description: Lietuviškų adresų autocomplete paslauga
 * Version: 0.2.0
 * Author: Arūnas Liuiza
 * Author URI: httpa://arunas.co
 * Text Domain: tinypostcode
 *
 * Lietuviškų adresų autocomplete paslauga
 *
 * @package TinyPostcode
 */
 add_action( 'init', 'github_plugin_updater_test_init' );
 function github_plugin_updater_test_init() {
 	include_once __DIR__ . '/updater.php';
 	define( 'WP_GITHUB_FORCE_UPDATE', true );
 	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin
 		$config = array(
 			'slug' => plugin_basename( __FILE__ ),
 			'proper_folder_name' => 'tinypostcode',
 			'api_url' => 'https://api.github.com/repos/ideag/tinypostcode',
 			'raw_url' => 'https://raw.github.com/ideag/tinypostcode/master',
 			'github_url' => 'https://github.com/ideag/tinypostcode',
 			'zip_url' => 'https://github.com/ideag/tinypostcode/archive/master.zip',
 			'sslverify' => true,
 			'requires' => '4.6',
 			'tested' => '4.7',
 			'readme' => 'README.md',
 			'access_token' => '',
 		);
 		new WP_GitHub_Updater( $config );
 	}
 }


add_action( 'plugins_loaded',  array( 'TinyPostcode', 'init' ) );
/**
 * Main Plugin class
 */
class TinyPostcode {
  public static $options = array(
    'api_key' => '',
  );
  public static $settings = array();
  public static $plugin_path = '';
  public static function init() {
    self::$plugin_path = plugin_dir_path( __FILE__ );

		// tinyOptions v 0.6.0.
		self::$options = wp_parse_args( get_option( 'tinypostcode_options' ), self::$options );
		add_action( 'plugins_loaded', array( 'TinyPostcode', 'init_options' ), 9999 - 0060 );

    add_action( 'wp_enqueue_scripts',         array( 'TinyPostcode', 'scripts' ) );
    add_action( 'admin_enqueue_scripts',      array( 'TinyPostcode', 'admin_scripts' ) );
    add_action( 'wp_ajax_tpc_limiter',        array( 'TinyPostcode', 'flag' ) );
    add_action( 'wp_ajax_tpc_limiter_nopriv', array( 'TinyPostcode', 'flag' ) );
    add_action( 'admin_notices',              array( 'TinyPostcode', 'notice' ) );
    add_action( 'wp_ajax_tpc_ratelimit_flag', array( 'TinyPostcode', 'remove_flag' ) );
  }
  public static function scripts() {
    wp_register_script( 'pixabay-auto-complete',  plugins_url( 'vendor/auto-complete/auto-complete.js', __FILE__ ) );
    wp_register_script( 'tinypostcode',           plugins_url( 'tinypostcode.js', __FILE__ ), array( 'pixabay-auto-complete', 'jquery' ) );
    wp_enqueue_script( 'tinypostcode' );
    $data = array(
      'data'    => array(
        'site'    => parse_url( get_bloginfo( 'url' ), PHP_URL_HOST ),
        'key'     => self::$options['api_key'],
      ),
      'fields'  => array(
        'input[name="billing_address_1"]' => array(
          'input[name="billing_address_1"]' => '%street% %house%',
          'input[name="billing_city"]'      => '%city%',
          'input[name="billing_state"]'     => '%region%',
          'input[name="billing_postcode"]'  => '%code%',
        ),
        'input[name="shipping_address_1"]' => array(
          'input[name="shipping_address_1"]' => '%street% %house%',
          'input[name="shipping_city"]'      => '%city%',
          'input[name="shipping_state"]'     => '%region%',
          'input[name="shipping_postcode"]'  => '%code%',
        ),
      ),
      'ajaxuri' => admin_url( 'admin-ajax.php' ),
    );
    $data = apply_filters( 'tinypostcode_data', $data );
    wp_localize_script( 'tinypostcode', 'tinypostcode', $data );
    wp_register_style( 'pixabay-auto-complete',   plugins_url( 'vendor/auto-complete/auto-complete.css', __FILE__ ) );
    wp_enqueue_style( 'pixabay-auto-complete' );
  }
  public static function admin_scripts() {
    wp_register_script( 'tinypostcode-admin', plugins_url( 'tinypostcode-admin.js', __FILE__ ), array( 'jquery' ), false, true );
    wp_enqueue_script( 'tinypostcode-admin' );
  }
  public static function flag() {
    $count = get_option( 'tpc_ratelimit_flag', 0 );
    $count++;
    update_option( 'tpc_ratelimit_flag', $count );
    wp_send_json_success( $count );
  }
  public static function remove_flag() {
    update_option( 'tpc_ratelimit_flag', 0 );
    wp_send_json_success( 0 );
  }
  public static function notice() {
    $screen = get_current_screen();
    if ( 'dashboard' !== $screen->base ) {
      return false;
    }
    $count = get_option( 'tpc_ratelimit_flag', 0 ) * 1;
    if ( 10 >= $count ) {
      return false;
    }
    $level = 'warning';
    $text = sprintf( _n( 'tinyPostcode API rate limit was hit %s time.', 'tinyPostcode API rate limit was hit %s times.', $count, 'tinypostcode' ), $count );
    if ( 50 < $count ) {
      $text .= ' ';
      $text .= sprintf(
        __( 'Please consider purchasing a %1$ssubscriotion%2$s.', 'tinypostcode' ),
        "<a href='https://api.aru.lt' target='_blank'>",
        '</a>'
      );
      $level = 'error';
    }
    $notice  = "<div class='notice notice-{$level} is-dismissible' data-action='tpc_ratelimit_flag' data-dismissible>";
    $notice .= "<p>{$text}</p>";
    $notice .= '</div>';
    echo $notice;
  }
  public static function init_options() {
		self::$settings = array(
			'page' => array(
				'title' 			=> __( 'tinyPostcode Settings', 'tinypostcode' ),
				'menu_title'	=> __( 'tinyPostcode', 'tinypostcode' ),
				'slug' 				=> 'tinypostcode-settings',
				'option'			=> 'tinypostcode_options',
				'description'	=> __( 'This plugin enables autocomplete on WooCommerce address fields via a Lithuanian postal addresses database.', 'tinypostcode' ),
			),
			'sections' => array(
				'main' => array(
					'title'				=> __( 'Main Settings	', 'tinypostcode' ),
					'fields'	=> array(
						'api_key' => array(
							'title'	=> __( 'API Key', 'tinypostcode' ),
						),
					),
				),
			),
			'l10n' => array(
				'no_access'			=> __( 'You do not have sufficient permissions to access this page.', 'tinypostcode' ),
				'save_changes'	=> esc_attr( 'Save Changes', 'tinypostcode' ),
			),
		);
		require_once( self::$plugin_path . 'tiny/tiny.options.php' );
		self::$settings = apply_filters( 'tinypostcode_settings', self::$settings );
		self::$settings = new tinyOptions( self::$settings, __CLASS__ );
	}}
