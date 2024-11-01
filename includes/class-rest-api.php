<?php

/**
 * Class MC_WK_Rest_API
 */
class MC_WK_Rest_API {
	/**
	 * The single instance of the class
	 *
	 * @var MC_WK_Rest_API
	 */
	protected static $instance = null;

	/**
	 * Main instance
	 *
	 * @return MC_WK_Rest_API
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

		include_once( 'class-wk-rest-product-reviews-controller.php' );
		include_once( 'class-wk-rest-orders-controller.php' );

		add_filter('rest_pre_insert_product_review', array( $this, 'pre_insert_product_review'), 10, 2);
		add_filter('rest_post_insert_product_review', array( $this, 'post_insert_product_review'), 10, 2);
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	public function pre_insert_product_review($prepared_review, $request) {
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

	public function post_insert_product_review($product_review_id, $request) {
		if ($product_review_id){
			if (isset($request['wk_avatar']) && $request['wk_avatar']){
				add_comment_meta($product_review_id, 'wk_avatar', $request['wk_avatar'], true);
			}
			if (isset($request['wk_photo_list']) && $request['wk_photo_list']){
				add_comment_meta($product_review_id, 'wk_photo_list', $request['wk_photo_list'], true);
			}
		}
	}

	public function register_rest_routes() {
		$controller = new WK_REST_Product_Reviews_Controller();
		$controller->register_routes();

		$controller = new WK_REST_Orders_Controller();
		$controller->register_routes();
	}
}