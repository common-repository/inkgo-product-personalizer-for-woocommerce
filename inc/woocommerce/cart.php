<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/* Saved info of customize when add to cart */
add_action( 'woocommerce_add_to_cart', 'inkgo_save_custom_design', 1, 5 );
if ( !function_exists( 'inkgo_save_custom_design' ) ) {
	function inkgo_save_custom_design($cart_item_key, $product_id = null, $quantity= null, $variation_id= null, $variation= null)
	{
		if( isset($_POST['inkgo_design_info']) )
		{
			$inkgo_campaign_id 			= get_post_meta( $product_id, 'inkgo_campaign_id', true );
			$design 					= array();

			$inkgo_token_id 			= WC()->session->get('inkgo_token_id', '');
			if($inkgo_token_id == '')
			{
				if(isset($_SESSION['inkgo_token_id']) )
				{
					$inkgo_token_id 	= $_SESSION['inkgo_token_id'];
				}
				elseif(isset($_POST['inkgo_token_id']) )
				{
					$inkgo_token_id 	= sanitize_key($_POST['inkgo_token_id']);
				}
				else
				{
					$inkgo_token_id 		= 'none';
				}
			}
			if( isset($_POST['inkgo_design_thumb']) )
			{
				$design['thumbs'] 	= array();
				$imgs				= array_map( 'sanitize_text_field', $_POST['inkgo_design_thumb'] );
				$dir 				= wp_upload_dir();
				$path 				= $dir['path'];
				foreach($imgs as $id => $img)
				{
					if( $img != '')
					{
						$temp 		= explode(';base64,', $img);
						$buffer		= base64_decode($temp[1]);
						$filename 	= 'inkgo-'.$inkgo_campaign_id.'-'.$inkgo_token_id.'-'.$id.'.jpeg';
						$file 		= $path .'/'. $filename;

						$savefile 	= fopen($file, 'w');
						fwrite($savefile, $buffer);
						fclose($savefile);

						$design['thumbs'][$id] = $dir['url'] .'/'. $filename;
					}
				}
			}
				
			$design['campaign_id'] 		= $inkgo_campaign_id;
			$design['inkgo_token_id'] 	= $inkgo_token_id;
			$design['customize'] 		= sanitize_text_field( $_POST['inkgo_design_info'] );

			$cart 		= WC()->cart->cart_contents;
			foreach( $cart as $cart_item_id => $cart_item ) 
			{
				if($cart_item_key == $cart_item_id)
				{
					$cart_item['_inkgo_design'] = $design;
					WC()->cart->cart_contents[$cart_item_id] = $cart_item;
				}
			}
			WC()->cart->set_session();
			//WC()->session->set( $cart_item_key.'_inkgo_design', $design );
		}
		elseif( isset($_POST['inkgo_custom']) )
		{
			$design 	= $_POST['inkgo_custom'];
			$cart 		= WC()->cart->cart_contents;
			foreach( $cart as $cart_item_id => $cart_item ) 
			{
				if($cart_item_key == $cart_item_id)
				{
					$cart_item['inkgo_custom'] = $design;
					WC()->cart->cart_contents[$cart_item_id] = $cart_item;
				}
			}
			WC()->cart->set_session();
		}
	}
}

if ( !function_exists( 'inkgo_cart_unique_key' ) ) {
	function inkgo_cart_unique_key($cart_item_data, $product_id)
	{
		if( isset($_POST['inkgo_design_info']) && $_POST['inkgo_design_info'] != '' )
		{
			$cart_item_data['unique_key'] = md5( microtime().rand() );
		}
		elseif( isset($_POST['inkgo_custom']) && count($_POST['inkgo_custom']) )
		{
			$cart_item_data['unique_key'] = md5( json_encode($_POST['inkgo_custom']) );
		}
		return $cart_item_data;
	}
}
add_filter( 'woocommerce_add_cart_item_data','inkgo_cart_unique_key', 10, 2 );

add_filter( 'woocommerce_get_cart_item_from_session', function ( $cartItemData, $cartItemSessionData, $cart_item_key ) {
    if ( isset( $cartItemSessionData['inkgo_custom'] ) ) 
    {
        $cartItemData['inkgo_custom'] = $cartItemSessionData['inkgo_custom'];
    }
    if ( isset( $cartItemSessionData['_inkgo_design'] ) ) 
    {
        $cartItemData['_inkgo_design'] = $cartItemSessionData['_inkgo_design'];
    }

    return $cartItemData;
}, 30, 3 );

/* save design of each item in page checkout */
add_action( 'woocommerce_add_order_item_meta', 'inkgo_save_item_design', 1, 3 );
if ( !function_exists( 'inkgo_save_item_design' ) ) {
	function inkgo_save_item_design($item_id, $cart_item, $cart_item_key)
	{
		$inkgo_fulfillment 		= get_post_meta( $cart_item['variation_id'], 'inkgo_fulfillment', true );
		if($inkgo_fulfillment)
		{
			wc_add_order_item_meta( $item_id, "inkgo_fulfillment",  $inkgo_fulfillment);
		}

		if( isset($cart_item['inkgo_custom']) )
		{
			$design  = $cart_item['inkgo_custom'];
			wc_add_order_item_meta( $item_id, "inkgo_custom",  $design);
		}
		else if( isset($cart_item['_inkgo_design']) )
		{
			$design  = $cart_item['_inkgo_design'];
			wc_add_order_item_meta( $item_id, "inkgo_custom",  $design);
		}
		else if( WC()->session->__isset( $cart_item_key.'_inkgo_custom' ) )
		{
			$design 				= WC()->session->get( $cart_item_key.'_inkgo_custom');
			wc_add_order_item_meta( $item_id, "inkgo_custom",  $design);
		}
		else if( WC()->session->__isset( $cart_item_key.'_inkgo_design' ) )
		{
			$design 				= WC()->session->get( $cart_item_key.'_inkgo_design');
			$design['item_id']		= $item_id;

			if(isset($design['inkgo_token_id']))
			{
				wc_add_order_item_meta( $item_id, "inkgo_custom_id",  $design['inkgo_token_id']);
			}
			wc_add_order_item_meta( $item_id, "inkgo_custom_designer",  $design);

			//$settings 	= inkgo_get_settings();
			//inkgo_api_post('order/'.$settings['api_key'], $design);
		}
	}
}

/**
 * Show upsell
 */
add_action( 'woocommerce_before_cart', 'inkgo_upsell_before_cart', 10 );
if ( !function_exists( 'inkgo_upsell_before_cart' ) )
{
	function inkgo_upsell_before_cart()
	{
		echo '<div class="inkgo-upsell-before"></div>';
	}
}

/**
 * Show after page cart
 */
add_action( 'woocommerce_after_cart', 'inkgo_upsell_after_cart', 10);
if ( !function_exists( 'inkgo_upsell_after_cart' ) )
{
	function inkgo_upsell_after_cart()
	{
		echo '<div class="inkgo-upsell-after"></div>';
	}
}

/**
 * Show upsell page product detail
 */
add_action( 'woocommerce_after_single_product_summary', 'inkgo_upsell_single_product', 30);
if ( !function_exists( 'inkgo_upsell_single_product' ) )
{
	function inkgo_upsell_single_product()
	{
		echo '<div class="inkgo-upsell-product-detail"></div>';
	}
}

/*
* show thumb of product customize in page cart and order
 */
if ( !function_exists( 'inkgo_cart_item_thumbnail' ) ) 
{
	function inkgo_cart_item_thumbnail($thumb, $cart_item, $cart_item_key)
	{
		if( isset($cart_item['inkgo_custom']) )
		{
			$data = $cart_item['inkgo_custom'];
			if( isset($data['_inkgo_design_thumb']) )
			{
				$images 	= json_decode( str_replace('\"', '"', $data['_inkgo_design_thumb']), true );
				if( isset($images[0]) )
				{
					$thumb = '<img src="'.esc_url($images[0]['url']).'" alt="" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail">';
				}
			}
		}
		else if( isset($cart_item['_inkgo_design']) || WC()->session->__isset( $cart_item_key.'_inkgo_design' ) )
		{
			if ( isset($cart_item['_inkgo_design']) )
				$data = $cart_item['_inkgo_design'];
			else 
				$data = WC()->session->get( $cart_item_key.'_inkgo_design');
			if( isset($data['thumbs']) && count($data['thumbs']) > 0 )
			{
				$thumb 	= '';
				foreach($data['thumbs'] as $src)
				{
					$thumb 	.= '<img src="'.esc_url($src).'" alt="" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail">';
				}
			}
			elseif( isset($cart_item['variation_id']) &&  isset($data['campaign_id']) && $data['campaign_id'] != '' )
			{
				$inkgo_thumbs = get_post_meta( $cart_item['variation_id'], 'inkgo_variation_img', true );
				if($inkgo_thumbs)
				{
					$thumbs = json_decode($inkgo_thumbs[0], true);
					$thumb = '<img src="'.esc_url($thumbs['src']).'" alt="" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail">';
				}
			}
		}
		elseif(isset($cart_item['variation_id']) && $cart_item['variation_id'] > 0)
		{
			$inkgo_thumbs = get_post_meta( $cart_item['variation_id'], 'inkgo_variation_img', true );
			if($inkgo_thumbs)
			{
				$thumbs = json_decode($inkgo_thumbs[0], true);
				$thumb = '<img src="'.esc_url($thumbs['src']).'" alt="" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail">';
			}
		}
		return $thumb;
	}
}
add_filter( 'woocommerce_cart_item_thumbnail', 'inkgo_cart_item_thumbnail', 999, 3);

/**
 * Add link edit design in page cart, checkout
 */
if ( !function_exists( 'inkgo_show_link_edit_design' ) ) 
{
	function inkgo_show_link_edit_design($cart_item, $cart_item_key)
	{
		if( isset($cart_item['inkgo_custom']) && is_checkout() == false )
		{
			$design 	= $cart_item['inkgo_custom'];
			$product_id = $cart_item['product_id'];
			$url 		= get_permalink($product_id);
			$link 		= add_query_arg( array('custom_id'=>$design['_inkgo_custom_id']), $url );
			$html 		= '<br /><a href="'.$link.'" data-id="'.$design['_inkgo_custom_id'].'" class="inkgo-edit-design">Edit Design</a>';
			echo $html;
		}
	}
	add_action( 'woocommerce_after_cart_item_name', 'inkgo_show_link_edit_design', 10, 2);
}


/* create file output of order */
add_action( 'wp_ajax_inkgo_ajax_output', 'inkgo_ajax_output');
add_action( 'wp_ajax_nopriv_inkgo_ajax_output', 'inkgo_ajax_output');
if ( !function_exists( 'inkgo_ajax_output' ) ) {
	function inkgo_ajax_output()
	{
		if(isset($_GET['order_id']))
		{
			$order_id 		= absint($_GET['order_id']);
			$order 			= new WC_Order( $order_id );
			if(count($order))
			{
				$order_items 	= $order->get_items();
				$urls 			= array();
				foreach ($order_items as $item_id => $item_data)
				{
					$output = wc_get_order_item_meta( $item_id, 'inkgo_file_output', true );
					if(!$output)
					{
						$urls 	= inkgo_get_file_output($item_data, $order_id, $item_id);
						if(count($urls)){
							wc_add_order_item_meta( $item_id, 'inkgo_file_output', $urls);
						}
					}
				}
			}
		}
		echo 'ok';
		exit;
	}
}

/* get file output with each item_id of order */
if ( !function_exists( 'inkgo_get_file_output' ) ) {
	function inkgo_get_file_output($item, $order_id, $item_id)
	{
		$urls 				= array();
		$inkgo_campaign_id 	= get_post_meta( $item['product_id'], 'inkgo_campaign_id', true );
		$products 			= get_post_meta( $item['product_id'], 'inkgo_campaign_mockups', true );
		$inkgo_product_id 	= get_post_meta( $item['variation_id'], 'inkgo_product_id', true );

		$custom 			= wc_get_order_item_meta( $item_id, "inkgo_custom", true ); // old version
		if( isset($custom['_inkgo_custom_id']) )
		{
			foreach($custom['design'] as $view_id => $file_id)
			{
				$url = 'https://download.inkgo.io/order/index/'.$_SERVER['HTTP_HOST'].'/'.$custom['_inkgo_custom_id'].'/'.$view_id;
				$urls[$view_id] = array(
					'view' => $view_id,
					'name' => $view_id,
					'url' => $url,
				);
			}
			return $urls;
		}

		if($inkgo_campaign_id && $products && $inkgo_product_id && isset($products[$inkgo_product_id]))
		{
			$designer 			= wc_get_order_item_meta( $item_id, "inkgo_custom_designer", true ); // old version

			if(empty($data['inkgo_token_id']))
			{
				$data['inkgo_token_id'] = 'none';

				$inkgo_custom_id = wc_get_order_item_meta( $item_id, "inkgo_custom_id", true );
				if($inkgo_custom_id)
				{
					$data['inkgo_token_id'] = $inkgo_custom_id;
				}
			}
			else
			{
				//wc_delete_order_item_meta($item_id, 'inkgo_custom_designer');
			}
			$sku 			= get_post_meta( $item['variation_id'], '_sku', true );
			if($sku != '') $sku = '/'.$sku;
			foreach($products[$inkgo_product_id] as $view => $name)
			{
				$url = INKGO_SELLER_URI.'/download/index/'.$inkgo_campaign_id.'/'.$data['inkgo_token_id'].'/'.$inkgo_product_id.'/'.$view.'/'.$order_id.'/'.$item_id.$sku;
				$urls[$view] = array(
					'view' => $view,
					'inkgo_product_id' => $inkgo_product_id,
					'name' => $name,
					'url' => $url,
				);
				//$response = wp_remote_get( esc_url_raw($url) );
			}
		}
		return $urls;
	}
}


/**
 * Show thumb of custom design in page checkout
 */
if ( !function_exists( 'inkgo_checkout_show_thumb' ) ) 
{
	add_filter( 'woocommerce_checkout_cart_item_quantity', 'inkgo_checkout_show_thumb', 10, 3 );
	function inkgo_checkout_show_thumb($html, $cart_item, $cart_item_key)
	{
		if( isset($cart_item['inkgo_custom']) && is_checkout() )
		{
			$design 	= $cart_item['inkgo_custom'];
			if( isset($design['_inkgo_design_thumb']) )
			{
				$thumb 	= json_decode( str_replace('\"', '"', $design['_inkgo_design_thumb']), true);
				if( isset($thumb[0]) && isset($thumb[0]['url']) )
				{
					$html = $html.'<br /><img src="'.$thumb[0]['url'].'" class="inkgo-cart-thumb" data-id="'.$design['_inkgo_custom_id'].'" width="150">';
				}
			}
		}

		return $html;
	}
}

/**
 * get total of items in cart
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'inkgo_ajax_cart_items', 90, 1 );
if ( !function_exists( 'inkgo_ajax_cart_items' ) )
{
	function inkgo_ajax_cart_items($data)
	{
		$data['total_items'] 	= WC()->cart->get_cart_contents_count();
		$data['total'] 			= WC()->cart->get_cart_contents_total();
		$data['subtotal'] 		= WC()->cart->get_subtotal();
		
		return $data;
	}
}


/* 
custom html display in page my order, thank you and email
*/
if ( !function_exists( 'inkgo_myorder_show_custom_field' ) )
{
	add_filter( 'woocommerce_display_item_meta', 'inkgo_myorder_show_custom_field', 90, 2 );
	function inkgo_myorder_show_custom_field($html, $item)
	{
		$custom  = wc_get_order_item_meta( $item->get_id(), "inkgo_custom", true );
		if( isset($custom['_inkgo_design_thumb']) )
		{
			$thumb 	= json_decode( str_replace('\"', '"', $custom['_inkgo_design_thumb']), true);
			if( isset($thumb[0]) && isset($thumb[0]['url']) )
			{
				$html = $html.'<br /><img class="inkgo-cart-thumb" data-id="'.$custom['_inkgo_custom_id'].'" src="'.$thumb[0]['url'].'" width="150">';
			}
		}
		return $html;
	}
}

/**
 * hidden custom json of order item meta in page my order & email
 */
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'inkgo_custom_order_item_meta', 10, 1 );
if ( !function_exists( 'inkgo_custom_order_item_meta' ) )
{
	function inkgo_custom_order_item_meta($items_meta)
	{
		if( is_array($items_meta) && count($items_meta) )
		{
			foreach($items_meta as $key => $item_data)
			{
				if($item_data->key == 'inkgo_custom')
				{
					unset($items_meta[$key]);
				}
			}
		}
		return $items_meta;
	}
}

/**
 * Display the custom data on cart and checkout page
 */
if ( !function_exists( 'inkgo_view_custom_field' ) )
{
	add_filter( 'woocommerce_get_item_data', 'inkgo_view_custom_field', 90, 2 );
	function inkgo_view_custom_field( $cart_data, $cart_item ) 
	{
        if ( isset( $cart_item['inkgo_custom'] ) ) 
		{
			$data 	= $cart_item['inkgo_custom'];
	    }
		elseif( WC()->session->__isset( $cart_item['key'].'_inkgo_custom' ) )
		{
			$data = WC()->session->get( $cart_item['key'].'_inkgo_custom');
		}
		if(isset($data))
		{
			if( isset($data['properties']) )
			{
				foreach($data['properties'] as $attr)
				{
					$cart_data[] = array(
			            'name'      => $attr['name'],
			            'value'     => $attr['value'],
			            'display'   => $attr['value']
			        );
				}
			}
		}
	    return $cart_data;
	}
}

/**
 * Set session variable on page load if the query string has coupon_code variable.
 */
if ( !function_exists( 'inkgo_get_custom_coupon_code' ) )
{
	function inkgo_get_custom_coupon_code()
	{
	    if( isset( $_GET[ 'coupon_code' ] ) )
	    {
	        // Ensure that customer session is started
	        if( !WC()->session->has_session() ){
	            WC()->session->set_customer_session_cookie(true);
	        }
	      
	        // Check and register coupon code in a custom session variable
	        $coupon_code = WC()->session->get( 'coupon_code' );
	        if( empty( $coupon_code ) && isset( $_GET[ 'coupon_code' ] ) ) {
	            $coupon_code = esc_attr( $_GET[ 'coupon_code' ] );
	            WC()->session->set( 'coupon_code', $coupon_code ); // Set the coupon code in session
	        }
	    }
	}
}
add_action( 'init', 'inkgo_get_custom_coupon_code' );

/**
 * Apply Coupon code to the cart if the session has coupon_code variable. 
 */
if ( !function_exists( 'inkgo_apply_discount_to_cart' ) )
{
	function inkgo_apply_discount_to_cart()
	{
	    // Set coupon code
	    if( isset( $_GET[ 'coupon_code' ] ) )
	    $coupon_code = WC()->session->get( 'coupon_code' );
	    if ( ! empty( $coupon_code ) && ! WC()->cart->has_discount( $coupon_code ) )
	    {
	        WC()->cart->add_discount( $coupon_code ); // apply the coupon discount
	        WC()->session->__unset( 'coupon_code' ); // remove coupon code from session
	    }
	}
}
add_action( 'woocommerce_before_checkout_form', 'inkgo_apply_discount_to_cart', 10, 10 );

/* inkgo and abandonment checkout */
if ( !function_exists( 'inkgo_abandonment_cart' ) )
{
	function inkgo_abandonment_cart($data)
	{
		if(isset($data['checkout_url']))
		{
			$url 		= $data['checkout_url'];
			$parts 		= parse_url($url);
			parse_str($parts['query'], $query);
			$index 		= base64_decode($query['wcf_ac_token']);
			$temp 		= explode('=', $index);

			if( isset($temp[1]) )
			{
				$session_id = $temp[1];
				global $wpdb;
				$cart_abandonment_table = $wpdb->prefix . CARTFLOWS_CA_CART_ABANDONMENT_TABLE;
				$result                 = $wpdb->get_row(
		            $wpdb->prepare('SELECT * FROM `' . $cart_abandonment_table . '` WHERE session_id = %s', $session_id )
				);
				
				if(isset($result->id))
				{
					$line_items 		= [];
					$note_attributes 	= [];
					$cart_contents 		= unserialize($result->cart_contents);
					$i 					= 0;
					foreach($cart_contents as $key => $cart)
					{
						$product_id   	= $cart['product_id'];
						$product      	= wc_get_product( $product_id );
						$product_name 	= $product ? $product->get_formatted_name() : '';
						$image_url 		= get_the_post_thumbnail_url( $product_id );

						$line_items[$i]  			= $cart;
						$line_items[$i]['title'] 	= $product_name;
						$line_items[$i]['image'] 	= $image_url;
						$line_items[$i]['price'] 	= $cart['line_total'];
						$line_items[$i]['properties'] = [];
						if( isset($cart['inkgo_custom']) )
						{
							$custom 	= $cart['inkgo_custom'];
							if(isset($custom['_inkgo_custom_id']))
							{
								$line_items[$i]['properties'][] = [
									'name' 	=> '_inkgo_custom_id',
									'value' => $custom['_inkgo_custom_id']
								];
								if( isset($custom['customize']) )
								{
									$note_attributes[] = [
										'name' 	=> $custom['_inkgo_custom_id'],
										'value' => json_decode( str_replace('\"', '"', $custom['customize']), true ),
									];
								}
							}

							if(isset($custom['_inkgo_design_thumb']))
							{
								$line_items[$i]['properties'][] = [
									'name' 	=> '_inkgo_design_thumb',
									'value' => json_decode( str_replace('\"', '"', $custom['_inkgo_design_thumb']), true )
								];
							}

							if(isset($custom['design']))
							{
								$line_items[$i]['properties'][] = [
									'name' 	=> 'design',
									'value' => $custom['design']
								];
							}
							unset($cart['inkgo_custom']);
							unset($line_items[$i]['inkgo_custom']);
						}
						$line_items[$i]['variant_title'] = '';
						if( isset($cart['variation']) && count($cart['variation']) )
						{
							foreach($cart['variation'] as $attr => $name)
							{
								if($line_items[$i]['variant_title'] == '')
									$line_items[$i]['variant_title'] = $name;
								else
									$line_items[$i]['variant_title'] .= ' / ' . $name;
							}
						}
						$i++;
					}

					$time 	= date('Y-m-d H:i:s', strtotime($result->time));
					$time 	= str_replace(' ', 'T', $time);	
					$GMT 	= wp_timezone_string();
					$time 	= $time.$GMT;
					$data['inkgo_checkout'] 	= [
						'id' 						=> $session_id,
						'checkout_id' 				=> $session_id,
						'abandoned_checkout_url' 	=> $data['checkout_url'],
						'created_at' 				=> $time,
						'currency' 					=> get_option('woocommerce_currency'),
						'customer' 					=> [
							'first_name' 	=> $data['first_name'],
							'last_name' 	=> $data['last_name'],
							'email' 		=> $data['email'],
							'phone' 		=> $data['phone_number'],
							'first_name' 	=> $data['first_name'],
						],
						'email' 					=> $data['email'],
						'line_items' 				=> $line_items,
						'note_attributes' 			=> $note_attributes,
						'phone' 					=> $data['phone_number'],
						'shipping_address' 			=> unserialize($result->other_fields),
						'status' 					=> 'open',
						'total_price' 				=> $data['cart_total'],
						'billing_address' 			=> unserialize($result->other_field),
					];
				}
			}
		}
		return $data;
	}
}
add_filter( 'woo_ca_webhook_trigger_details', 'inkgo_abandonment_cart', 10, 1 );
?>