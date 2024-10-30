<div class="wrap">
<?php if ($settings['api_key']) { ?>
        <form method="post" action="<?php echo admin_url('admin.php?page=inkgo_settings'); ?>">
            <h1>Connect InkGo</h1>
            <table class="form-table" style="display:none;">
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="inkgo_key">InkGo API key</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <input type="password" name="inkgo[api_key]" class="input-text regular-input" value="" style="width: 400px;">
                        </fieldset>
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field( 'inkgo_settings', 'inkgo_nonce_field' ); ?>
            <input type="hidden" name="inkgo_settings_hidden" value="Y">
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Re-Connect">
            </p>
        </form>
<?php } else { ?>
    <h2 class="nav-tab-wrapper">
        <a href="admin.php?page=inkgo" class="nav-tab nav-tab-active">Connect</a>
    </h2>
    <div id="inkgo-connect">
        <h1><?php esc_html_e('Connect to InkGo', 'inkgo'); ?></h1>
         <img src="<?php echo esc_url(INKGO_PLUGIN_URI.'/assets/images/connect.svg'); ?>" alt="inkgo">
        <?php if ( ! empty( $issues ) ) { ?>
            <p><?php esc_html_e('To connect your store to InkGo, fix the following errors:', 'inkgo'); ?></p>
            <div class="inkgo-warning">
                <ul>
                    <?php
                    foreach ( $issues as $issue ) {
                    echo '<li>' . wp_kses_post( $issue ) . '</li>';
                    }
                    ?>
                </ul>
            </div>
        <?php
            $url = '#';
        } else { ?>
            <p class="connect-description">Please connect your store to InkGo before add product. <a href="https://help.inkgo.io/base/install-inkgo-on-woocommerce/" target="_blank">Read document</a></p><?php
            
            $url = InkGo_Common::get_inkgo_seller_uri() . '/integration/connect?type=woocommerce&shop_name='.get_bloginfo().'&website=' . urlencode(trailingslashit(get_home_url())). '&return_url=' . urlencode(get_admin_url(null, 'admin.php?page=inkgo'));
        }
        echo '<a href="' . esc_url($url) . '" class="button button-primary inkgo-connect-button ' . (!empty($issues) ? 'disabled' : '') . '">' . esc_html__('Connect', 'inkgo') . '</a>';
        ?>
        <img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ) ?>" class="loader hidden" width="20px" height="20px" alt="loader"/>
    </div>

     <script type="text/javascript">
            jQuery(document).ready(function () {
                InkGo_Connect.init('<?php echo esc_url( admin_url( 'admin-ajax.php?action=ajax_inkgo_check_connect_status' ) ); ?>');
            });
        </script>
<?php } ?>
</div>