<?php
/**
 * All function of admin settings
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
include(INKGO_PLUGIN_DIR . 'inc/admin/product.php');
include(INKGO_PLUGIN_DIR . 'inc/admin/class-inkgo-common.php');
include(INKGO_PLUGIN_DIR . 'inc/admin/class-inkgo-client.php');


add_action('plugins_loaded', 'inkgo_check_woocommerce_active');
if ( !function_exists( 'inkgo_check_woocommerce_active' ) ) {
    function inkgo_check_woocommerce_active()
    {
        $check  = inkgo_is_woocommerce_active();
        if($check == false)
        {
            add_action( 'admin_notices', 'inkgo_install_woo' );
        }
    }
}

/* 
* show ask install Woocommerce 
*/
if ( !function_exists( 'inkgo_install_woo' ) ) {
    function inkgo_install_woo()
    {
        $class      = 'notice notice-error';
        $message    = __( 'WooCommerce plugin is required, please install plugin WooCommerce before use InkGo plugin', 'inkgo' );

        printf( '<div class="%1$s"><p><b>InkGo Warning:</b> %2$s</p></div>', esc_attr( $class ), esc_attr( $message ) ); 
    }
}

add_action('admin_menu', 'inkgo_admin_menu', 30);
if ( !function_exists( 'inkgo_admin_menu' ) ) {
    function inkgo_admin_menu()
    {
        add_menu_page( esc_attr(__('InkGo', 'inkgo')), esc_attr(__('InkGo', 'inkgo')), 'administrator', 'inkgo', 'inkgo', INKGO_PLUGIN_URI.'/assets/images/inkgo.svg');
        add_submenu_page( 'inkgo', esc_attr(__('Products & Orders', 'inkgo')), esc_attr(__('Products & Orders', 'inkgo')), 'manage_options', 'inkgo', 'products_orders');
        add_submenu_page( 'inkgo', esc_attr(__('InkGo Connect', 'inkgo')), esc_attr(__('InkGo Connect', 'inkgo')), 'administrator', 'inkgo_settings', 'inkgo_settings');
    }
}

if ( !function_exists( 'products_orders' ) )
{
    function products_orders() {
        $settings   = inkgo_get_settings();
        if( isset($settings['api_key']) && $settings['api_key'] != '')
        {
            wp_redirect('https://seller.inkgo.io');
            exit();
        ?>
            <iframe src="<?php echo INKGO_SELLER_URI; ?>?woo=<?php echo urlencode(get_home_url()) ;?>&label=<?php echo urlencode(bloginfo('name')) ;?>" id="inkgo-app"></iframe>
        <?php
        }
        else
        {
            wp_redirect( admin_url('admin.php?page=inkgo_settings') );
        }
    }
}

if ( !function_exists( 'inkgo_settings' ) ) {
function inkgo_settings()
{
    if( isset($_POST['inkgo_nonce_field']) && wp_verify_nonce( $_POST['inkgo_nonce_field'], 'inkgo_settings' ) && is_admin() )
    {
        $settings   = array_map( 'sanitize_text_field', $_POST['inkgo'] );
        update_option( 'inkgo', $settings);
    }

    $settings   = get_option('inkgo');
    $keys       = array('api_key', 'header', 'button', 'label', 'confirm', 'change', 'rotate');
    foreach($keys as $key)
    {
        if(empty($settings[$key]))
        {
            $settings[$key] = '';
        }
    }

    $issues = array();

    $permalinks = get_option( 'permalink_structure', false );

    if ( $permalinks && strlen( $permalinks ) > 0 ) {
        // ok
    } else {
        $message      = __('WooCommerce API will not work unless your permalinks are set up correctly. Go to <a href="%s">Permalinks settings</a> and make sure that they are NOT set to "plain".');
        $settings_url = esc_url(admin_url( 'options-permalink.php' ));
        $issues[]     = sprintf( $message, $settings_url );
    }

    if ( strpos( get_home_url(), 'https:' ) === false ) {
        $issues[] = esc_html("You can not connect to InkGo from http. Please active https (SSL) before connect to InkGo.");
    }

    if ( strpos( get_site_url(), 'localhost' ) ) {
        $issues[] = esc_html("You can not connect to InkGo from localhost. InkGo needs to be able reach your site to establish a connection.");
    }

    if (! InkGo_Common::ping_to_inkgo()) {
        $issues[] = esc_html("Can not connect to InkGo Server. Please check your server network connection.");
    }

    require INKGO_PLUGIN_DIR . 'inc/admin/html/settings.php';
}
}
?>