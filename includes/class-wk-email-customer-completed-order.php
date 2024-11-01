<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WK_Email_Customer_Completed_Order', false ) ) :

/**
 * Customer Completed Order Email.
 *
 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
 *
 * @class       WK_Email_Customer_Completed_Order
 * @version     2.0.0
 * @package     WooCommerce/Classes/Emails
 * @author      MinHcom
 * @extends     WC_Email_Customer_Completed_Order
 */

require_once ABSPATH.'/wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-settings-api.php';
require_once ABSPATH.'/wp-content/plugins/woocommerce/includes/emails/class-wc-email.php';
require_once ABSPATH.'/wp-content/plugins/woocommerce/includes/emails/class-wc-email-customer-completed-order.php';
class WK_Email_Customer_Completed_Order extends WC_Email_Customer_Completed_Order {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Call parent constuctor
		parent::__construct();

		// Triggers for this email
		remove_all_actions( 'woocommerce_order_status_completed_notification' );
		add_action( 'woocommerce_order_status_completed_notification', array( $this, 'trigger' ), 10, 2 );
		error_log('Override Woocommerce Customer Order Completed Email');
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		error_log('Send In Override Woocommerce Customer Order Completed Email');
		$this->sending = true;

		$lastNote = '';
		if(is_a( $this->object, 'WC_Order' )){
			$lastNote = $this->get_last_order_notes($this->object->get_id());
		}

		error_log('Last note : '.$lastNote);
		if ( 'plain' === $this->get_email_type() ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) );
			$email_content = str_replace('Shipping:', 'Shipping:\r\n'.$lastNote, $email_content);
		} else {
			$email_content = $this->get_content_html();

			$strPattern = 'Order #'.$this->object->get_id().'</h2>';
			$email_content = str_replace($strPattern, $strPattern.$lastNote.'<br/>', $email_content);
		}

		return wordwrap( $email_content, 70 );
	}

	function get_last_order_notes( $order_id){
		global $wpdb;

		$table_perfixed = $wpdb->prefix . 'comments';
		$results = $wpdb->get_results("SELECT *
			FROM $table_perfixed
			WHERE  `comment_post_ID` = $order_id
			AND  `comment_type` LIKE  'order_note'
			ORDER BY comment_date DESC;
		");
		$lastNote = '';
		foreach($results as $note){
			if ($note->comment_content && (strpos($note->comment_content, 'Order status changed') === false)){
				$lastNote = $note->comment_content;
				break;
			}
		}
		return $lastNote;
	}
}

endif;