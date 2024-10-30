<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$inkgo_product = new stdClass();

/* add js file to page product detail */
if ( !function_exists( 'inkgo_frontend_scripts' ) ) {
	function inkgo_frontend_scripts()
	{
		$main_js 	= INKGO_CDN_ASSETS.'inkgo-sdk.js?x-request='.time();
		wp_enqueue_script( 'inkgo_sdk', $main_js, array(), false, true);

		if ( is_product() )
		{
			global $inkgo_product;
			$product_id 			= get_the_ID();
			$inkgo_campaign_id 		= get_post_meta( $product_id, 'inkgo_campaign_id', true );
			$inkgo_campaign_v 		= get_post_meta( $product_id, 'inkgo_campaign_v', true );
			if($inkgo_campaign_v == false) $inkgo_campaign_v = 1;

			$inkgo_product->campaign_v = $inkgo_campaign_v;
			if($inkgo_campaign_id)
			{
				/* add file js */
				$version 		= INKGO_VERSION;
				if($inkgo_product->campaign_v == 2)
				{
					$file_js 	= INKGO_CDN_ASSETS.'inkgo-v2.min.js?x-request='.time();
				}
				else
				{
					$file_js 	= 'https://cdn.inkgo.io/assets/frontend.min.js?x-request='.time();
				}
				wp_enqueue_script( 'inkgo_app-js', $file_js, array('jquery', 'flexslider'), $version, true );

				$file_css 	= INKGO_CDN_ASSETS.'inkgo-app.css?x-request='.time();
				wp_enqueue_style( 'inkgo_app-css', $file_css, array() );

				/* add language js */
				$inkgo_product->id 			= $product_id;
				$inkgo_product->campaign_id = $inkgo_campaign_id;

				$inkgo_product->is_custom 	= get_post_meta( $product_id, 'inkgo_campaign_custom', true );
				$settings 					= inkgo_get_settings();
				$inkgo_product->settings 	= $settings;

				if($inkgo_product->campaign_v == 2)
				{
					wp_add_inline_script('inkgo_app-js', 'var inkgo_mydoamin = "'.$_SERVER['HTTP_HOST'].'"; inkgo_client_product_id = "'.$product_id.'"; var inkgo_cart_page = "'.wc_get_cart_url().'"; var inkgo_ajax_url = "'.admin_url('admin-ajax.php').'";', 'before');
				}
				elseif($inkgo_product->is_custom == 1 && isset($settings['api_key']) && $settings['api_key'] != '')
				{
					wp_add_inline_script('inkgo_app-js', 'var inkgo_mydoamin = "'.$_SERVER['HTTP_HOST'].'"; var inkgo_campaign_custom = 1; var inkgo_lang = {}; var inkgo_dis_mobile = 1; var INKGO_JSON_URL = "'.INKGO_JSON_URI.'"; var inkgo_campaign_id = "'.$inkgo_campaign_id.'"; inkgo_woo_id = "'.$product_id.'"; var inkgo_ajax_url = "'.admin_url('admin-ajax.php').'";', 'before');
				}
				else
				{
					wp_add_inline_script('inkgo_app-js', 'var inkgo_campaign_custom = 0; var INKGO_JSON_URL = "'.INKGO_JSON_URI.'"; var inkgo_campaign_id = "'.$inkgo_campaign_id.'"; inkgo_woo_id = "'.$product_id.'"; var inkgo_ajax_url = "'.admin_url('admin-ajax.php').'"', 'before');
				}
			}
		}
		elseif ( is_checkout() && !empty( is_wc_endpoint_url('order-received') ) )
		{
			/* call to ajax create file output in page completed order */
			if ( ! wp_script_is( 'jquery', 'done' ) ) {
				wp_enqueue_script( 'jquery' );
			}
			$order_id 	= wc_get_order_id_by_order_key( sanitize_title($_GET['key']) );
			$url 		= admin_url('admin-ajax.php').'?action=inkgo_ajax_output&order_id='.$order_id;
	   		wp_add_inline_script( 'jquery-migrate', 'jQuery.get("'.$url.'", function(data) {});');
		}
	}
}
add_action( 'wp_enqueue_scripts', 'inkgo_frontend_scripts');

/* show input design in page product */
if ( !function_exists( 'inkgo_design_fields' ) ) {
	function inkgo_design_fields()
	{
		global $inkgo_product, $product;
		
		if( isset($inkgo_product->campaign_id) && $inkgo_product->campaign_id != '' )
		{
			WC()->session->set('inkgo_campaign_id', $inkgo_product->campaign_id);
			$_SESSION['inkgo_campaign_id'] = $inkgo_product->campaign_id;

			$settings 			= $inkgo_product->settings;
			if($inkgo_product->campaign_v == 2)
			{
				$array 	= array(
					'title' 		=> $product->get_name(),
					'price' 		=> $product->get_price(),
					'description' 	=> $product->get_description(),
					'images' 		=> array(wp_get_attachment_url($product->get_image_id())),
					'options' 		=> $product->get_attributes()
				);
				$json_attr 	= wp_json_encode($array);
				$json 		= function_exists( 'wc_esc_json' ) ? wc_esc_json( $json_attr ) : _wp_specialchars( $json_attr, ENT_QUOTES, 'UTF-8', true );
				echo '<script type="application/json" id="inkgo-product-json" data-json="'.$json.'"></script>';

				echo '<div class="inkgo-product inkgo-hide-mobile" data-version="2" data-id="'.esc_attr($inkgo_product->campaign_id).'"></div>';
				echo '<div class="inkgo-wapper" id="inkgo-app"></div>';
			}
			else
			{
				echo '<div class="inkgo-design inkgo-hide-mobile" data-id="'.esc_attr($inkgo_product->campaign_id).'"><div class="inkgo-design-items"></div></div>';
				echo '<button type="button" name="btn_inkgo_customize" class="inkgo-custom-mobile inkgo-hidden inkgo-show-mobile"></button>';
				echo '<input type="hidden" name="inkgo_design_info" class="inkgo_design_info">';
				echo '<div class="inkgo-thumbs"></div>';
			}
			
			if(empty($inkgo_product->is_has_inkgo_variation) && $product->is_type( 'simple' ) == false)
			{
				$attributes = $product->get_variation_attributes();
				
				if(count($attributes))
				{
					$attrs 				= array();
					$product_id 		= $product->get_id();
					foreach($attributes as $key => $attr)
					{
						$att_meta_key 	= 'inkgo_attribute_' . md5( $key );
						$att_meta_val 	= get_post_meta( $product_id, $att_meta_key, true );

						if($att_meta_val == 'product')
						{
							$attrs[sanitize_title($key)] = array(
								'products' => get_post_meta( $product_id, 'inkgo_attribute_images', true ),
								'name' => $key
							);
						}
						elseif($att_meta_val == 'color')
						{
							$attrs[sanitize_title($key)] = array(
								'colors' => get_post_meta( $product_id, 'inkgo_attribute_colors', true ),
								'name' => $key
							);
						}
						else
						{
							$attrs[sanitize_title($key)] = array(
								'name' => $key,
								'values' => $attr,
							);
						}
					}
					
					if(count($attrs))
					{
						$json_attr = wp_json_encode($attrs);
						$json = function_exists( 'wc_esc_json' ) ? wc_esc_json( $json_attr ) : _wp_specialchars( $json_attr, ENT_QUOTES, 'UTF-8', true );
						echo '<div class="inkgo-attrs-json" data-product_attrs="'.$json.'"></div>';
					}
				}
			}
		}
	}
}
add_action( 'woocommerce_before_add_to_cart_button', 'inkgo_design_fields', 30 );

/* get product info */
if ( !function_exists( 'inkgo_ajax_product' ) )
{
	function inkgo_ajax_product()
	{
		$data 	= [];
		if( isset($_GET['product_id']) )
		{
			$product_id 			= (int) $_GET['product_id'];
			$product 				= wc_get_product( $product_id );
			if($product)
			{
				$data['id'] 			= $product_id;
				$data['title'] 			= $product->get_title();
				$data['description'] 	= wp_strip_all_tags($product->get_description());
				
				$terms = get_the_terms( $product->get_id(), 'product_cat' );
				if( is_array($terms) && count($terms) > 0 )
				{
					$data['categories'] = [];
					foreach($terms as $term)
					{
						$data['categories'][] = [
							'id' => $term->term_id,
							'name' => $term->name,
						];
					}
				}

				$tags = get_the_terms( $product->get_id(), 'product_tag' );
				if( is_array($tags) && count($tags) > 0 )
				{
					$data['tags'] = [];
					foreach($tags as $tag)
					{
						$data['tags'][] = [
							'id' => $tag->term_id,
							'name' => $tag->name,
						];
					}
				}
			}
		}
		echo wp_json_encode($data);
		exit;
	}
}
add_action( 'wp_ajax_inkgo_ajax_product', 'inkgo_ajax_product');
add_action( 'wp_ajax_nopriv_inkgo_ajax_product', 'inkgo_ajax_product');

/* get product variations */
if ( !function_exists( 'inkgo_ajax_product_variations' ) )
{
	function inkgo_ajax_product_variations()
	{
		$data 	= [];
		if( isset($_GET['product_id']) )
		{
			$product_id 	= (int) $_GET['product_id'];
			if($product_id == 0)
			{
				echo '[]';
				exit;
			}

			global $wp_filesystem;
			require_once ( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();

			$cache_path 	= WP_CONTENT_DIR . '/inkgo-cache/';
			$file 			= $cache_path.'inkgo_woo_variation_'.$product_id.'.json';
			if(file_exists($file))
			{
				$json 	= $wp_filesystem->get_contents($file);
				if($json)
				{
					$variations = json_decode(htmlspecialchars_decode($json), true);
				}
			}
			else
			{
				$product 		= wc_get_product( $product_id );
				if($product !== false)
				{
					$variations = $product->get_available_variations();
				}
			}
				
			if( isset($variations) && count($variations) > 0)
			{
				foreach($variations as $row)
				{
					$name 	= '';
					$options = [];
					foreach($row['attributes'] as $key => $val)
					{
						if($name == '') $name = $val;
						else $name = $name .' / ' . $val;
						$options[] = [
							'name' 		=> str_replace('attribute_', '', $key),
							'value' 	=> $val,
						];
					}
					$data[] = [
						'title' 				=> $name,
						'sku' 					=> $row['sku'],
						'options' 				=> $options,
						'id' 					=> $row['variation_id'],
						'price' 				=> $row['display_price'],
						'regular_price' 		=> $row['display_regular_price'],
					];
				}
			}
		}
		echo wp_json_encode($data);
		exit;
	}
}
add_action( 'wp_ajax_inkgo_ajax_variations', 'inkgo_ajax_product_variations');
add_action( 'wp_ajax_nopriv_inkgo_ajax_variations', 'inkgo_ajax_product_variations');

/**
 * search product by title, tags , categories, list ids
 * url: ?action=inkgo_product_search&
 * product_id search product same tag, categories of this product
 * not_ids: find list product not in this list id
 * limit
 * search_by: cat, tag, title
 */
if ( !function_exists( 'inkgo_ajax_product_search' ) ){
	function inkgo_ajax_product_search()
	{
		$data 	= [];
		if( isset($_GET['ids']) )
		{
			$ids 	= explode('-', $_GET['ids']);
			if(count($ids))
			{
				foreach($ids as $id)
				{
					$product_id = (int) $id;
					if($product_id)
					{
						$product 				= wc_get_product( $product_id );
						if($product)
						{
							$data[] 	= [
								'id' 		=> $product_id,
								'name' 		=> $product->get_name(),
								'thumb' 	=> get_the_post_thumbnail_url($product->get_id(), 'thumbnail'),
								'url' 		=> get_permalink($product->get_id()),
								'price' 	=> $product->get_price(),
								'price_html'=> $product->get_price_html(),
							];
						}
					}
				}
			}
		}
		else if( isset($_GET['product_id']) )
		{
			$product_id 	= (int) $_GET['product_id'];
			$limit 			= 8;
			if( isset($_GET['limit']) )
			{
				$limit = (int) $_GET['limit'];
				if($limit < 2) $limit = 2;
				if($limit > 12) $limit = 12;
			}

			$not_in_ids 	= [];
			if( isset($_GET['not_ids']) )
			{
				$not_in_ids = explode('-', $_GET['not_ids']);
				if( !is_array($not_in_ids) ) $not_in_ids = [];
			}
			$not_in_ids[] = $product_id;

			$search_by 	= 'cat';
			if( isset($_GET['search_by']) )
			{
				$search_by 	= $_GET['search_by'];
			}

			// search by categories
			if($search_by == 'cat')
			{
				$terms = get_the_terms( $product_id, 'product_cat' );
				if( is_array($terms) && count($terms) > 0 )
				{
					$ids = [];
					foreach($terms as $term)
					{
						$ids[] = $term->term_id;
					}
				}
				$option 	= [
					'taxonomy' => 'product_cat',
					'field' => 'id',
					'terms' => $ids
				];
			}
			elseif($search_by == 'tag') // search by tags
			{
				$terms = get_the_terms( $product_id, 'product_tag' );
				if( is_array($terms) && count($terms) > 0 )
				{
					$ids = [];
					foreach($terms as $term)
					{
						$ids[] = $term->term_id;
					}
				}
				$option 	= [
					'taxonomy' => 'product_tag',
					'field' => 'id',
					'terms' => $ids
				];
			}
			
			if( isset($option) )
			{
				$query_args = array( 
					'post__not_in' 		=> array( $not_in_ids ), 
					'posts_per_page' 	=> $limit,
					'no_found_rows' 	=> 1, 
					'post_status' 		=> 'publish', 
					'post_type' 		=> 'product',
					'tax_query' 		=> array($option)
				);
	
				$query = new WP_Query($query_args);
				if ( $query->have_posts() )
				{
					while ($query->have_posts())
					{
						$query->the_post();
						$product_id = get_the_ID();
						$product 	= wc_get_product($product_id);
						$data[] 	= [
							'id' 		=> $product_id,
							'name' 		=> $product->get_name(),
							'thumb' 	=> get_the_post_thumbnail_url($product->get_id(), 'thumbnail'),
							'url' 		=> get_permalink($product->get_id()),
							'price' 	=> $product->get_price(),
							'price_html'=> $product->get_price_html(),
						];
					}
				}
				wp_reset_query();
			}
		}
		echo wp_json_encode($data);
		exit; 
	}
}
add_action( 'wp_ajax_inkgo_product_search', 'inkgo_ajax_product_search');
add_action( 'wp_ajax_nopriv_inkgo_product_search', 'inkgo_ajax_product_search');
?>
