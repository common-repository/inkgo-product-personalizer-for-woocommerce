<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ajax get variations of product */
add_action( 'wp_ajax_inkgo_variations', 'inkgo_product_variations');
add_action( 'wp_ajax_nopriv_inkgo_variations', 'inkgo_product_variations');
if ( !function_exists( 'inkgo_product_variations' ) ) {
	function inkgo_product_variations()
	{
		$result 			= array();
		$result['error'] 	= 0;
		if( isset($_POST['product_id']) )
		{
			$product_id 	= intval($_POST['product_id']);
			$product 		= wc_get_product( $product_id );
			$data 			= $product->get_available_variations();
			$result['data']	= $data;
			$result['error']= 1;
		}
		wp_send_json($result);
	}
}

add_action( 'wp_ajax_inkgo_product_description', 'inkgo_product_description');
add_action( 'wp_ajax_nopriv_inkgo_product_description', 'inkgo_product_description');
if ( !function_exists( 'inkgo_product_description' ) ) {
	function inkgo_product_description()
	{
		$result 			= array();
		$result['error'] 	= 0;
		if( isset($_GET['product_id']) )
		{
			$product_id 	= intval($_GET['product_id']);
			$data 			= get_post_meta( $product_id, 'inkgo_product_description', true );
			$result['data']	= $data;
			$result['error']= 1;
		}
		wp_send_json($result);
	}
}

if ( !function_exists( 'wc_inkgo_ajax_threshold' ) )
{
	add_filter( 'woocommerce_ajax_variation_threshold', 'wc_inkgo_ajax_threshold', 9999);
	function wc_inkgo_ajax_threshold() {
		return 500;
	}
}

if ( !function_exists( 'inkgo_variations_form' ) )
{
	function inkgo_variations_form($return = false)
	{
		global $product, $inkgo_product;
		if(isset($inkgo_product->campaign_id))
		{
			$get_variations = count($product->get_children());

			if($get_variations > 30)
			{
				global $wp_filesystem;

				require_once ( ABSPATH . '/wp-admin/includes/file.php' );
    			WP_Filesystem();

				$cache_path 	= WP_CONTENT_DIR . '/inkgo-cache/';
				$file 			= $cache_path.'inkgo_woo_variation_'.$product->get_id().'.json';
				$data 			= '';
				if(file_exists($file))
				{
					$data 		= $wp_filesystem->get_contents($file);
				}
				else
				{
					if(!file_exists($cache_path))
					{
						mkdir($cache_path, 0755);
					}
					$data 	= htmlspecialchars( wp_json_encode($product->get_available_variations()) );
					$wp_filesystem->put_contents($file, $data, 0644);
				}
				if($return)
				{
					return $data;
				}

				if($data != '')
				{
					echo '<div class="inkgo-product-variations" data-product_variations="'.$data.'"></div>';
					echo "<script>jQuery('.inkgo-product-variations').parents('form').attr('data-product_variations', jQuery('.inkgo-product-variations').attr('data-product_variations'));jQuery('.inkgo-product-variations').remove();</script>";
				}
			}
			if($return)
			{
				return '';
			}
		}
	}
}
add_action( 'woocommerce_before_variations_form', 'inkgo_variations_form');

if ( !function_exists( 'inkgo_wc_dropdown_variation_attribute_options' ) ) {
function inkgo_wc_dropdown_variation_attribute_options( $html, $args )
{
	$product 				= $args['product'];
	$product_id 			= $product->get_id();

	$inkgo_campaign_id 		= get_post_meta( $product_id, 'inkgo_campaign_id', true );
	if(!$inkgo_campaign_id) return $html;

	global $inkgo_product;

	if( count($product->get_children()) < 30 )
	{
		$inkgo_product->is_has_inkgo_variation = 1;
	}

	// Get selected value.
	if ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {
		$selected_key     = 'attribute_' . sanitize_title( $args['attribute'] );
		$args['selected'] = isset( $_REQUEST[ $selected_key ] ) ? wc_clean( wp_unslash( $_REQUEST[ $selected_key ] ) ) : $args['product']->get_variation_default_attribute( $args['attribute'] ); // WPCS: input var ok, CSRF ok, sanitization ok.
	}
	
	$options               = $args['options'];
	$attribute             = $args['attribute'];
	$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
	$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
	$class                 = $args['class'];
	$show_option_none      = (bool) $args['show_option_none'];
	$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : esc_html__( 'Choose an option', 'woocommerce' );
	
	$att_meta_key = 'inkgo_attribute_' . md5( $attribute );
	$att_meta_val = get_post_meta( $product_id, $att_meta_key, true );
	
	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];
	}
	
	$attribute_swatches_args = array();
	
	$attribute_swatches_meta_key = '';
	switch ( $att_meta_val ) {
		case 'product':
			$attribute_swatches_meta_key = 'inkgo_attribute_images';
			break;
		case 'color':
			$attribute_swatches_meta_key = 'inkgo_attribute_colors';
			break;
		default:
			$attribute_swatches_meta_key = '';
			break;
	}
	
	if ( $attribute_swatches_meta_key != '' ) {
		$attribute_swatches_args = get_post_meta( $product_id, $attribute_swatches_meta_key, true );
	}
	
	$attribute_swatches_args_json_encode = ! empty( $attribute_swatches_args ) ? htmlentities2( wp_json_encode( $attribute_swatches_args ) ) : '';
	
	$class .= ' inkgo-swatches';
	
	$html = '<select data-swatches="' . esc_attr( $attribute_swatches_args_json_encode ) . '" data-swatch_type="' . esc_attr( $att_meta_val ) . '" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-title="'.$attribute.'" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
	$html .= '<option data-swatch_val="" value="">' . esc_html( $show_option_none_text ) . '</option>';
	
	if ( ! empty( $options ) ) {
		
		$swatch_index = 0;
		
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms = wc_get_product_terms(
				$product_id,
				$attribute,
				array(
					'fields' => 'all',
				)
			);
			
			foreach ( $terms as $term ) {
				$swatch_val = isset( $attribute_swatches_args[ $swatch_index ] ) ? $attribute_swatches_args[ $swatch_index ] : '';
				if(is_array($swatch_val)) $swatch_val = json_encode($swatch_val);
				if ( in_array( $term->slug, $options, true ) ) {
					$html .= '<option data-swatch_index="' . $swatch_index . '" data-swatch_val="' . esc_attr( $swatch_val ) . '" value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
				}
				
				$swatch_index ++;
			}
		} else {
			foreach ( $options as $option ) {
				$swatch_val = isset( $attribute_swatches_args[ $swatch_index ] ) ? $attribute_swatches_args[ $swatch_index ] : '';
				if(is_array($swatch_val)) $swatch_val = json_encode($swatch_val);
				// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
				$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
				$html     .= '<option data-swatch_index="' . $swatch_index . '" data-swatch_val="' . esc_attr( $swatch_val ) . '" value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
				
				$swatch_index ++;
			}
		}
	}
	
	$html .= '</select>';
	
	return $html;
}
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'inkgo_wc_dropdown_variation_attribute_options', 9999, 2 );

/* add class extra to thumb of product */
if ( !function_exists( 'inkgo_wc_product_thumbs' ) ) {
	function inkgo_wc_product_thumbs( $html, $attachment_id )
	{
		$html = str_replace('<img ', '<img data-inkgo="images" ', $html);

		return $html;
	}
}
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'inkgo_wc_product_thumbs', 999, 2 );

/* add list image to each variation */
if ( !function_exists( 'inkgo_wc_variation_images' ) ) {
	function inkgo_wc_variation_images($data, $product, $variation)
	{
		$variation_imgs = get_post_meta( $variation->get_id(), 'inkgo_variation_img', true );
		if ( ! empty( $variation_imgs ) ) 
		{
			$data['inkgo_variation_images'] = array();
			for($i=0; $i<count($variation_imgs); $i++)
			{
				if(is_string($variation_imgs[$i]))
					$data['inkgo_variation_images'][$i] = json_decode($variation_imgs[$i], true);
				else
					$data['inkgo_variation_images'][$i] = $variation_imgs[$i];
			}
			if( isset($data['image']) && isset($data['inkgo_variation_images']) )
			{
				$data['image']['full_src'] = $data['inkgo_variation_images'][0]['src'];
				$data['image']['gallery_thumbnail_src'] = $data['inkgo_variation_images'][0]['src'];
				$data['image']['src'] = $data['inkgo_variation_images'][0]['src'];
				$data['image']['thumb_src'] = $data['inkgo_variation_images'][0]['src'];
				$data['image']['url'] = $data['inkgo_variation_images'][0]['src'];
			}
		}
		return $data;
	}
}
add_filter( 'woocommerce_available_variation', 'inkgo_wc_variation_images', 10, 3 );

/* change style of product layout if product import from inkgo */
if ( !function_exists( 'inkgo_woo_template' ) ) {
	function inkgo_woo_template( $template, $template_name, $template_path )
	{
		$product_id 			= get_the_ID();
		$inkgo_campaign_id 		= get_post_meta( $product_id, 'inkgo_campaign_id', true );
		if( $inkgo_campaign_id )
		{
			$basename 		= basename( $template );
			if( $basename == 'product-image.php' )
			{
				$template = trailingslashit( INKGO_PLUGIN_DIR ) . 'woocommerce/product-image.php';
			}
			elseif( $basename == 'product-thumbnails.php' )
			{
				$template = trailingslashit( INKGO_PLUGIN_DIR ) . 'woocommerce/product-thumbnails.php';
			}
		}
		return $template;
	}
}
add_filter( 'woocommerce_locate_template', 'inkgo_woo_template', 999, 3 );

?>