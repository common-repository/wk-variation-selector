<?php
/**
 * REST API Product Reviews Controller
 *
 * Handles requests to /products/<product_id>/reviews.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API Product Reviews Controller Class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Reviews_V1_Controller
 */
class WK_REST_Product_Reviews_Controller extends WC_REST_Product_Reviews_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wk/v1';

    /**
     * Check if a given request has access to batch manage product reviews.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function batch_items_permissions_check( $request ) {
        /*if ( ! wc_rest_check_post_permissions( 'product', 'batch' ) ) {
            return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to batch manipulate this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
        }*/
        return true;
    }

    /**
     * Get the Product Review's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema() {
        $schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'product_review',
            'type'       => 'object',
            'properties' => array(
                'id' => array(
                    'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'review' => array(
                    'description' => __( 'The content of the review.', 'woocommerce' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'date_created' => array(
                    'description' => __( "The date the review was created, in the site's timezone.", 'woocommerce' ),
                    'type'        => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                ),
                'date_created_gmt' => array(
                    'description' => __( "The date the review was created, as GMT.", 'woocommerce' ),
                    'type'        => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                ),
                'rating' => array(
                    'description' => __( 'Review rating (0 to 5).', 'woocommerce' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                ),
                'name' => array(
                    'description' => __( 'Reviewer name.', 'woocommerce' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'email' => array(
                    'description' => __( 'Reviewer email.', 'woocommerce' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'wk_avatar' => array(
                    'description' => __( 'Author avatar.', 'woocommerce' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'wk_photo_list' => array(
                    'description' => __( 'Comment images.', 'woocommerce' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'verified' => array(
                    'description' => __( 'Shows if the reviewer bought the product or not.', 'woocommerce' ),
                    'type'        => 'boolean',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
            ),
        );

        return $this->add_additional_fields_schema( $schema );
    }

    /**
     * Create a product review.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function create_item( $request ) {
        $product_id = (int) $request['product_id'];

        if ( 'product' !== get_post_type( $product_id ) ) {
            return new WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
        }

        $prepared_review = $this->prepare_item_for_database( $request );

        /**
         * Filter a product review (comment) before it is inserted via the REST API.
         *
         * Allows modification of the comment right before it is inserted via `wp_insert_comment`.
         *
         * @param array           $prepared_review The prepared comment data for `wp_insert_comment`.
         * @param WP_REST_Request $request          Request used to insert the comment.
         */
        $prepared_review = apply_filters( 'rest_pre_insert_product_review', $prepared_review, $request );

        $product_review_id = wp_insert_comment( $prepared_review );
        if ( ! $product_review_id ) {
            return new WP_Error( 'rest_product_review_failed_create', __( 'Creating product review failed.', 'woocommerce' ), array( 'status' => 500 ) );
        }

        update_comment_meta( $product_review_id, 'rating', ( ! empty( $request['rating'] ) ? $request['rating'] : '0' ) );

        /**
         * Fires after it is inserted via the REST API.
         */
        do_action( 'rest_post_insert_product_review', $product_review_id, $request );

        $product_review = get_comment( $product_review_id );
        $this->update_additional_fields_for_object( $product_review, $request );

        /**
         * Fires after a single item is created or updated via the REST API.
         *
         * @param WP_Comment      $product_review Inserted object.
         * @param WP_REST_Request $request        Request object.
         * @param boolean         $creating       True when creating item, false when updating.
         */
        do_action( "woocommerce_rest_insert_product_review", $product_review, $request, true );

        $request->set_param( 'context', 'edit' );
        $response = $this->prepare_item_for_response( $product_review, $request );
        $response = rest_ensure_response( $response );
        $response->set_status( 201 );
        $base = str_replace( '(?P<product_id>[\d]+)', $product_id, $this->rest_base );
        $response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $product_review_id ) ) );

        return $response;
    }
}
