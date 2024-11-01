<?php
/**
 * Plugin Name: Wooketing Variation Selector
 * Description: An extension of Wooketing to make a variable products selector be more beauty and friendly with users.
 * Version: 1.1.7
 * Author: MinHcom
 * Requires at least: 4.5
 * Tested up to: 4.7.3
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * The main plugin class
 */
final class MC_WK_Variation_Selector {
	/**
	 * The single instance of the class
	 *
	 * @var MC_WK_Variation_Selector
	 */
	protected static $instance = null;

	/**
	 * Main instance
	 *
	 * @return MC_WK_Variation_Selector
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		require_once 'includes/class-frontend.php';
		require_once 'includes/class-rest-api.php';
		require_once 'includes/class-wk-email-customer-completed-order.php';
	}

	/**
	 * Initialize hooks
	 */
	public function init_hooks() {
		if ( !is_admin() ) {
			add_action( 'init', array( 'MC_WK_Variation_Selector_Frontend', 'instance' ) );
		}
		add_action( 'init', array( 'MC_WK_Rest_API', 'instance' ) );
	}
}

/**
 * Main instance of plugin
 *
 * @return MC_WK_Variation_Selector
 */
function MC_WKVS() {
	return MC_WK_Variation_Selector::instance();
}

/**
 * Display notice in case of WooCommerce plugin is not activated
 */
function mc_wk_variation_selector_wc_notice() {
	?>

	<div class="error">
		<p><?php esc_html_e( 'See eDrop247 Variation Selector is enabled but not effective. It requires WooCommerce in order to work.', 'wcvs' ); ?></p>
	</div>

	<?php
}

/**
 * Construct plugin when plugins loaded in order to make sure WooCommerce API is fully loaded
 * Check if WooCommerce is not activated then show an admin notice
 * or create the main instance of plugin
 */
function mc_wk_variation_selector_constructor() {
	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'mc_wk_variation_selector_wc_notice' );
	} else {
		MC_WKVS();
	}
}

function pre_insert_product_review($prepared_review, $request) {
	if ($prepared_review && is_array($prepared_review)){
		if (isset($request['date_created'])){
			$prepared_review['comment_date'] = $request['date_created'];
		}
		if (isset($request['date_created_gmt'])){
			$prepared_review['comment_date_gmt'] = $request['date_created_gmt'];
		}
	}
	return $prepared_review;
}

add_filter('rest_pre_insert_product_review', 'pre_insert_product_review', 10, 2);
add_action( 'plugins_loaded', 'mc_wk_variation_selector_constructor' );
//add_filter( 'admin_init' , 'register_fields' );
/*
function register_fields() {
	register_setting( 'general', 'enable_countdown', 'esc_attr' );
	add_settings_field('enable_countdown', '<label for="enable_countdown">'.__('Enable Countdown' , 'enable_countdown' ).'</label>' , 'fields_html' , 'general' );
}*/
function fields_html() {
	$value = get_option( 'enable_countdown', '' );
	echo '<input type="checkbox" id="enable_countdown" name="enable_countdown" value="1" '.($value?'checked':'').'/>';
}

add_filter( 'woocommerce_email', 'processmails',99);
function processmails($wc_emails) {
	// error_log('woocommerce_email_classes: '.print_r($wc_emails, true));
	if ($wc_emails && is_object($wc_emails)){
		remove_action('woocommerce_order_status_completed_notification', array($wc_emails->emails['WC_Email_Customer_Completed_Order'], 'trigger'));
		$wc_emails->emails['WC_Email_Customer_Completed_Order'] = new WK_Email_Customer_Completed_Order;
	}
	return $wc_emails;
}

