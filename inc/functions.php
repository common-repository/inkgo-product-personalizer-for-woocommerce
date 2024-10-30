<?php
/**
 * Main init file for admin
 *
 * @package inkgo_plugin
 *
 * @copyright 2019 inkgo.io
 * @version 1.0.0
 * @author inkgo
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
require INKGO_PLUGIN_DIR . 'inc/woocommerce/init.php';

/**
 * Load plugin textdomain.
 */
add_action('plugins_loaded', 'inkgo_load_textdomain');

if ( !function_exists( 'inkgo_load_textdomain' ) ) {
	function inkgo_load_textdomain()
	{
	    load_plugin_textdomain('inkgo', false, 'inkgo/languages');
	}
}

/* add inkgo support image webp */
if ( !function_exists( 'inkgo_webp_mimes' ) )
{
    function inkgo_webp_mimes($mime_types)
    {
        $mime_types['webp'] = 'image/webp';
        return $mime_types;
    }
}
add_filter('mime_types', 'inkgo_webp_mimes');

//** * Enable preview / thumbnail for webp image files.*/
if ( !function_exists( 'inkgo_mimes_webp_display' ) )
{
	function inkgo_mimes_webp_display($result, $path) {
	    if ($result === false) {
	        $displayable_image_types = array( IMAGETYPE_WEBP );
	        $info = @getimagesize( $path );

	        if (empty($info)) {
	            $result = false;
	        } elseif (!in_array($info[2], $displayable_image_types)) {
	            $result = false;
	        } else {
	            $result = true;
	        }
	    }

	    return $result;
	}
}
//add_filter('file_is_displayable_image', 'inkgo_mimes_webp_display', 10, 2);



/*
* Check website install pluign or No
 */
if ( !function_exists( 'inkgo_register_session' ) ) {
	function inkgo_register_session()
	{
		if( !session_id() ){
			session_start();
		}

		if(isset($_GET['inkgo_installed']))
		{
			$result 			= array();
			$result['error'] 	= 0;
	        $result['site_name']  = get_bloginfo();
			$permalinks 	= get_option( 'permalink_structure', false );
			if($permalinks != '')
			{
				$result['permalinks'] = 1;
			}
			else
			{
				$result['permalinks'] = 0;
				$result['error'] 	= 1;
			}
			if(function_exists('WC'))
			{
				$result['woo'] = WC()->version;
			}
			$result['inkgo_version'] = INKGO_VERSION;
			wp_send_json($result);
		}
	}
}
add_action('init', 'inkgo_register_session');


/**
 * Load setting of plugin
 */
if ( !function_exists( 'inkgo_get_settings' ) ) {
	function inkgo_get_settings()
	{
	    $settings = get_option('inkgo');

	    return $settings;
	}
}

/* post data to API of inkgo */
if ( !function_exists( 'inkgo_api_post' ) ) {
	function inkgo_api_post($type, $options)
	{
		$url 			= INKGO_API_URI.$type;
		
		$response 		= wp_remote_post($url, array(
			'body' => $options,
		));

		if( !is_wp_error($response) )
		{
			return $response['body'];
		}
		
		return '';
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
    return;
}

// Init REST API routes.
add_action( 'rest_api_init', 'inkgo_register_rest_routes', 20);
add_action( 'wp_ajax_ajax_inkgo_check_connect_status', array( 'InkGo_Common', 'ajax_inkgo_check_connect_status' ) );

if ( !function_exists( 'inkgo_register_rest_routes' ) ) {
	function inkgo_register_rest_routes()
	{
	    require_once INKGO_PLUGIN_DIR .'inc/class-inkgo-rest-api-controller.php';
	    $inkgoRestAPIController = new Inkgo_REST_API_Controller();
	    $inkgoRestAPIController->register_routes();
	}
}
?>
