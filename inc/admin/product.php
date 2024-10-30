<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/*
Add js, css in page edit product
 */
if ( !function_exists( 'inkgo_add_admin_scripts' ) ) {
function inkgo_add_admin_scripts($hook)
{
	global $post;
    if ( $hook == 'post-new.php' || $hook == 'post.php' )
    {
        if ( 'product' === $post->post_type )
        {
            wp_enqueue_script( 'inkgo_admin-js', INKGO_PLUGIN_URI.'assets/js/admin.js', array(), INKGO_VERSION, true );		
			wp_enqueue_style( 'inkgo_admin-css', INKGO_PLUGIN_URI.'assets/css/admin.css', array(), INKGO_VERSION );	
        }
    }
    elseif ($hook == 'inkgo_page_inkgo_settings')
    {
        wp_enqueue_script( 'inkgo_admin-js', INKGO_PLUGIN_URI.'assets/js/connect.js', array(), INKGO_VERSION, true );
        wp_enqueue_style( 'inkgo_admin-css', INKGO_PLUGIN_URI.'assets/css/admin.css', array(), INKGO_VERSION );
    }
    wp_register_style( 'inkgo_admin_menu_css', '');
    wp_enqueue_style( 'inkgo_admin_menu_css' );

    $custom_css 	= '#toplevel_page_inkgo .wp-menu-image img{padding:5px 0 0 0;width:16px;}';
    if (isset($_GET['page']) && $_GET['page'] == 'inkgo')
    {
    	$custom_css .= '.update-nag, #message, .notice, .updated, #wpfooter { display: none !important; }';
    	$custom_css .= 'iframe#inkgo-app{width: calc(100% + 20px);position: relative;margin-left: -20px;height: calc(100vh - 32px);margin-bottom: -100px;}';
	}
    wp_add_inline_style( 'inkgo_admin_menu_css', $custom_css );
}
}
add_action( 'admin_enqueue_scripts', 'inkgo_add_admin_scripts', 10, 1 );

/* remove cache when save product */
add_action('save_post', 'inkgo_product_custom_save_meta');
if ( !function_exists( 'inkgo_product_custom_save_meta' ) ) {
	function inkgo_product_custom_save_meta($post_id)
	{
		$cache_path 	= WP_CONTENT_DIR . '/inkgo-cache/';
		$file 			= $cache_path.'inkgo_woo_variation_'.$post_id.'.json';
		if(file_exists($file))
		{
			unlink($file);
		}
	}
}

/* show thumb of variation in page edit product */
add_action( 'woocommerce_variation_options', 'inkgo_variation_thumb', 10, 3 );
if ( !function_exists( 'inkgo_variation_thumb' ) ) {
function inkgo_variation_thumb($loop, $variation_data, $variation)
{
	$thumbs = get_post_meta($variation->ID, 'inkgo_variation_img', true);
	if(is_array($thumbs) && count($thumbs))
	{
		$html 	= '<div class="inkgo-thumb">';
		for($i=0; $i<count($thumbs); $i++)
		{
			$image = array();
			if(is_string($thumbs[$i]))
				$image = json_decode($thumbs[$i], true);
			if(isset($image['src']))
				$html .= '<a href="javascript:void(0);" class="inkgo-thumb"><img width="64" src="'.esc_url($image['src']).'" alt=""></a>';
		}
		$html .= '</div>';
		echo $html;
	}
}
}

/* show link download from page order detail */
add_action( 'woocommerce_before_order_itemmeta', 'inkgo_download_oder_item', 99, 3);
if ( !function_exists( 'inkgo_download_oder_item' ) ) {
	function inkgo_download_oder_item($item_id, $item, $product)
	{
		$custom 			= wc_get_order_item_meta( $item_id, "inkgo_custom", true ); // old version

		if( isset($custom['_inkgo_design_thumb']) )
		{
			$thumbs 	= json_decode($custom['_inkgo_design_thumb'], true);
			echo '<div class="inkgo-thumb">';
			for($i=0; $i<count($thumbs); $i++)
			{
				echo '<a href="'.$thumbs[$i]['url'].'" title="'.$thumbs[$i]['name'].'" target="_blank" class="inkgo-thumb"><img width="90" style="border: 1px solid #ddd;margin: 15px;" src="'.esc_url($thumbs[$i]['url']).'" alt=""></a>';
			}
			echo '</div>';
		}
		if( isset($custom['properties']) )
		{
			for($i=0; $i<count($custom['properties']); $i++)
			{
				$attr 	= $custom['properties'][$i];
				echo '<p><b>'.$attr['name'].'</b>: '.$attr['value'].'</p>';
			}
		}

		$url 		= wc_get_order_item_meta( $item_id, 'inkgo_file_output', true );
		if(!$url)
		{
			$url 	= inkgo_get_file_output($item, $item['order_id'], $item_id);
		}
		if( is_array($url) && count($url) > 0 )
		{
			$html = '<p><b>Download</b>: ';
			foreach($url as $view => $data)
			{
				$html .= '<a class="button" href="'.esc_url($data['url']).'" target="_blank">'.$data['name'].'</a> ';
			}
			$html .= '</p>';

			echo $html;
		}
	}
}
?>