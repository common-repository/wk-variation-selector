<?php

/**
 * Class MC_WK_Variation_Selector_Frontend
 */
class MC_WK_Variation_Selector_Frontend {
	/**
	 * The single instance of the class
	 *
	 * @var MC_WK_Variation_Selector_Frontend
	 */
	protected static $instance = null;

	/**
	 * Main instance
	 *
	 * @return MC_WK_Variation_Selector_Frontend
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'woocommerce_dropdown_variation_attribute_options_html', array( $this, 'get_swatch_html' ), 100, 2 );
		add_filter( 'tawcvs_swatch_html_label', array( $this, 'swatch_html_label' ), 5, 4 );
		add_filter( 'tawcvs_swatch_html_image', array( $this, 'swatch_html_image' ), 5, 4 );
		add_filter( 'comment_text', array($this, 'filter_text'), 10, 2 );
		add_filter('get_avatar_url', array($this,'get_avatar_url'), 1, 3);
		//	add_action('woocommerce_after_add_to_cart_button', array($this,'woocommerce_product_meta_end'), 1);
		//Compatibility mode for X-theme
		//	if ( class_exists( 'TCO_1_0' ) ) {
		//}
	}

	/**
	 * Enqueue scripts and stylesheets
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'tawcvs-frontend', plugins_url( 'assets/css/frontend.css', dirname( __FILE__ ) ), array(), '20170721' );
		//	wp_enqueue_style( 'tawcvs-countdown', plugins_url( 'assets/css/TimeCircles.css', dirname( __FILE__ ) ), array(), '20160614' );
		//	wp_enqueue_script( 'tawcvs-countdown', plugins_url( 'assets/js/TimeCircles.js', dirname( __FILE__ ) ), array( 'jquery' ), '20160611', true );
		wp_enqueue_script( 'tawcvs-frontend', plugins_url( 'assets/js/frontend.js', dirname( __FILE__ ) ), array( 'jquery' ), '20170719', true );
	}

	/**
	 * Filter function to add swatches bellow the default selector
	 *
	 * @param $html
	 * @param $args
	 *
	 * @return string
	 */
	public function get_swatch_html( $html, $args ) {
		$options   = $args['options'];
		$product   = $args['product'];
		$attribute = $args['attribute'];
		if ( empty( $attribute ) ) {
			return $html;
		}else{
			$attribute = strtolower($attribute);
		}
		$colorKeyword = 'color';
		$colorImages = array();
		if (strpos($attribute, $colorKeyword) !== false){
			$variationIds = $product->get_visible_children();
			foreach ($variationIds as $variationId){
				$variation    = new WC_Product_Variation($variationId);
				$attributes = $variation->get_variation_attributes();
				if (key_exists('attribute_'.str_replace(' ', '-', $attribute),  $attributes) && $variation->get_image_id('not-view')){
					$colorImages[$attributes['attribute_'.str_replace(' ', '-', $attribute)]] = $variation->get_image_id('not-view');
				}
			}
		}else{
			$colorKeyword = 'pa_cb5feb1b7314637725a2e7';
			if (strpos($attribute, $colorKeyword) !== false){
				$variationIds = $product->get_visible_children();
				foreach ($variationIds as $variationId){
					$variation    = new WC_Product_Variation($variationId);
					$attributes = $variation->get_variation_attributes();
					if (key_exists('attribute_'.str_replace(' ', '-', $attribute),  $attributes) && $variation->get_image_id('not-view')){
						$colorImages[$attributes['attribute_'.str_replace(' ', '-', $attribute)]] = $variation->get_image_id('not-view');
					}
				}
			}else{
				$attImage = get_post_meta($product->id, 'adsw-attribute-image', true);
				if ($attImage){
					$attImage = str_replace($product->id.'-', 'pa_', $attImage);
					if ($attribute == $attImage){
						$variationIds = $product->get_visible_children();
						foreach ($variationIds as $variationId){
							$variation    = new WC_Product_Variation($variationId);
							$attributes = $variation->get_variation_attributes();
							if (key_exists('attribute_'.str_replace(' ', '-', $attribute),  $attributes) && $variation->get_image_id('not-view')){
								$colorImages[$attributes['attribute_'.str_replace(' ', '-', $attribute)]] = $variation->get_image_id('not-view');
							}
						}
					}
				}
			}
		}

		$swatches  = '';
		foreach ( $options as $option ) {
			if (strpos($attribute, $colorKeyword) !== false) {
				$swatches .= apply_filters( 'tawcvs_swatch_html_image', '', $option, $args, $colorImages );
			}else{
				$attImage = get_post_meta($product->id, 'adsw-attribute-image', true);
				if ($attImage){
					$attImage = str_replace($product->id.'-', 'pa_', $attImage);
					if ($attribute == $attImage){
						$swatches .= apply_filters( 'tawcvs_swatch_html_image', '', $option, $args, $colorImages );
					}else{
						$swatches .= apply_filters( 'tawcvs_swatch_html_label', '', $option, $args );
					}
				}else{
					$swatches .= apply_filters( 'tawcvs_swatch_html_label', '', $option, $args );
				}
			}
		}

		if ( ! empty( $swatches ) ) {
			$class = ' hidden';

			$swatches = '<div class="tawcvs-swatches" data-attribute_name="attribute_' . esc_attr( $attribute ) . '">' . $swatches . '</div>';
			$html     = '<div class="' . esc_attr( $class ) . '">' . $html . '</div>' . $swatches;

		}
		return $html;
	}

	public function filter_text( $comment_text, $comment = null ) {

		$image_list_json = get_comment_meta( $comment->comment_ID, 'wk_photo_list', true );

		if ($image_list_json){
			$comment_text .= "<div class='wk_photo_list'>";
			$image_list = json_decode($image_list_json);
			foreach($image_list as $src){
				$comment_text .= "<a class='tawcvs-swatches' rel='group{$comment->comment_ID}' href='{$src}'><img src='{$src}'/></a>";
			}
			$comment_text .= "</div>";
		}
		return $comment_text;
	}

	function get_avatar_url( $url, $id_or_email, $args ){
		if ( $id_or_email instanceof WP_Comment ){
			$comment = $id_or_email;
			$comment_id = $comment->comment_ID;
			$image_path = get_comment_meta( $comment_id,  'wk_avatar', true );
			if (is_null($image_path)){
				$image_path = $url;
			}

			return $image_path;

		}

	}

	/**
	 * Print HTML of a single swatch
	 *
	 * @param $html
	 * @param $term
	 * @param $attr
	 * @param $args
	 *
	 * @return string
	 */
	public function swatch_html_label( $html, $option, $args ) {
		$selected = sanitize_title( $args['selected'] ) == sanitize_title($option) ? 'selected' : '';
		$name     = esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) );
		$title = trim(explode('-', $name)[0]);

		$html  = sprintf(
			'<span class="swatch swatch-label swatch-%s %s" title="%s" data-value="%s">%s</span>',
			esc_attr( $option ),
			$selected,
			esc_attr( $title ),
			esc_attr( $option ),
			esc_html( $title )
		);

		return $html;
	}

	/**
	 * Print HTML of a single swatch
	 *
	 * @param $html
	 * @param $term
	 * @param $attr
	 * @param $args
	 *
	 * @return string
	 */
	public function swatch_html_image( $html, $option, $args, $colorImages ) {
		$selected = sanitize_title( $args['selected'] ) == sanitize_title($option) ? 'selected' : '';
		$name     = esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) );
		$title = trim(explode('-', $name)[0]);

		if (key_exists($option, $colorImages)){
			$image = $colorImages[$option];
			$image = $image ? wp_get_attachment_image_src( $image ) : '';
		} else{
			$image = '';
		}
		if ($image){
			$html  = sprintf(
				'<span class="swatch swatch-image swatch-%s %s" title="%s" data-value="%s"><img src="%s" alt="%s"></span>',
				esc_attr( $option ),
				$selected,
				esc_attr( $title ),
				esc_attr( $option ),
				( $image[0] ),
				esc_attr( $title )
			);
		}else{
			$html  = sprintf(
				'<span class="swatch swatch-label swatch-%s %s" title="%s" data-value="%s">%s</span>',
				esc_attr( $option ),
				$selected,
				esc_attr( $title ),
				esc_attr( $option ),
				esc_html( $title )
			);
		}

		return $html;
	}

	/*public function woocommerce_product_meta_end(){
		$value = get_option( 'enable_countdown', '' );
		if ($value){
			$html = '<div class="items-count" id="progress_bar"><p>Hurry!!! Only <span class="countdown-day" style="background-color: rgb(255, 255, 255); color: rgb(206, 2, 1);">3</span> left in stock.</p></div><div id="CountDownTimer" style="width: 100%"></div>';
			echo $html;
		}
	}*/
}