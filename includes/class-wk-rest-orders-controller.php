<?php
/**
 * REST API Orders Controller
 *
 * Handles requests to /orders.
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
 * REST API Orders Controller Class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Orders_Controller
 */
class WK_REST_Orders_Controller extends WC_REST_Orders_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wk/v1';

    public function batch_items_permissions_check( $request ) {
       /* if ( ! wc_rest_check_post_permissions( $this->post_type, 'batch' ) ) {
            return new WP_Error( 'woocommerce_rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
        }*/

        return true;
    }

    /**
     * Add the schema from additional fields to an schema array.
     *
     * The type of object is inferred from the passed schema.
     *
     * @param array $schema Schema array.
     */
    protected function add_additional_fields_schema( $schema ) {
        $schema = parent::add_additional_fields_schema($schema);
        $schema['order_note'] =  array(
            'description' => __( 'Note left by admin.', 'woocommerce' ),
            'type'        => 'string',
            'context'     => array( 'view', 'edit' ),
        );

        return $schema;
    }

    /**
     * Update a single post.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function update_item( $request ) {
        $object = $this->get_object( (int) $request['id'] );

        if ( ! $object || 0 === $object->get_id() ) {
            return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 400 ) );
        }

        $object = $this->save_object( $request, false );

        if ( is_wp_error( $object ) ) {
            return $object;
        }

        if (isset($request['order_note']) && $request['order_note']){
            // update order note
            $order = new WC_Order($object->get_id());
            $order->add_order_note($request['order_note']);
        }
        $this->update_additional_fields_for_object( $object, $request );

        /**
         * Fires after a single object is created or updated via the REST API.
         *
         * @param WC_Data         $object    Inserted object.
         * @param WP_REST_Request $request   Request object.
         * @param boolean         $creating  True when creating object, false when updating.
         */
        do_action( "woocommerce_rest_insert_{$this->post_type}_object", $object, $request, false );

        $request->set_param( 'context', 'edit' );
        $response = $this->prepare_object_for_response( $object, $request );
        return rest_ensure_response( $response );
    }
}
