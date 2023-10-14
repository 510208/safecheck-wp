<?php
/*
Plugin Name: Safe Link Converter
Description: Automatically convert external links to a safe check URL.
Version: 0.4.5
Author: 510208
Author URI: https://pgsoft.lionfree.net
Plugin URI: https://github.com/510208/safecheck-wp
License: GNU General Public License v3
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class SafeLinkCheckerPlugin {

    public function __construct() {
        // Add settings menu
        add_action('admin_menu', array($this, 'add_settings_menu'));

        // Register settings
        add_action('admin_init', array($this, 'register_plugin_settings'));

        // Add content filter
        add_filter('the_content', array($this, 'filter_content'));
        
        // Hook to save settings
        add_action('admin_post_save_safe_link_checker_settings', array($this, 'save_plugin_settings'));
        
        // Hook to deactivate the plugin
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        
        // Display admin notice
        add_action('admin_notices', array($this, 'display_admin_notice'));
    }

    // Add settings menu
    public function add_settings_menu() {
        add_menu_page(
            'Safe Link Checker',
            'Safe Link Checker',
            'manage_options',
            'safe-link-checker-settings',
            array($this, 'settings_page')
        );
    }

    // Create settings page content
    public function settings_page() {
        ?>
        <div class="wrap">
            <h2>Safe Link Checker Settings</h2>
            <p>Welcome to Safe Link Checker. On this page you will be able to configure the functions of this plug-in. Please confirm that you have set up your own security check web page (at <a href="https://github.com/510208/safecheck-wp"> The source code page of this project is provided</a>). This plug-in can only be used after it is built. Please remember to set the API URL in the settings page as your external URL.
                <hr>
                Please remember: after you remove this plug-in, all external connection check URLs set by this plug-in will be invalid. Thank you.
            </p>
            <form method="post" action="options.php">
                <?php
                // 输出 nonce 字段
                wp_nonce_field('safe_link_checker_settings', 'safe_link_checker_settings_nonce');

                settings_fields('safe_link_checker_settings_group');
                do_settings_sections('safe-link-checker-settings');
                submit_button();
                ?>
                <a class="button button-primary" href="https://pgsoft.lionfree.net">造訪作者網站</a>
            </form>
        </div>
        <?php
    }

    // Register settings fields
    public function register_plugin_settings() {
        register_setting(
            'safe_link_checker_settings_group',
            'safe_link_checker_url',
            'sanitize_text_field'
        );

        register_setting(
            'safe_link_checker_settings_group',
            'safe_link_checker_whitelist',
            'sanitize_text_field'
        );

        add_settings_section(
            'safe_link_checker_section',
            'Safe Link Checker Settings',
            array($this, 'settings_section_callback'),
            'safe-link-checker-settings'
        );

        add_settings_field(
            'safe_link_checker_url',
            'API URL',
            array($this, 'url_callback'),
            'safe-link-checker-settings',
            'safe_link_checker_section'
        );

        add_settings_field(
            'safe_link_checker_whitelist',
            'Whitelist URLs (comma-separated)(Alpha)',
            array($this, 'whitelist_callback'),
            'safe-link-checker-settings',
            'safe_link_checker_section'
        );
    }
    
    // Settings field callback function
    public function url_callback() {
        $url = get_option('safe_link_checker_url', 'https://example.com/safecheck');
        echo '<input type="text" name="safe_link_checker_url" value="' . esc_attr($url) . '" />';
    }

    // Whitelist field callback function
    public function whitelist_callback() {
        $whitelist = get_option('safe_link_checker_whitelist', '');
        echo '<textarea name="safe_link_checker_whitelist" rows="5" cols="50">' . esc_attr($whitelist) . '</textarea>';
    }
    
    // Settings section callback function
    public function settings_section_callback() {
        echo 'Configure your Safe Link Checker settings here.';
    }

    public function save_plugin_settings(){
        if(isset($_POST['safe_link_checker_url'])){
            // Verify nonce
            if (isset($_POST['safe_link_checker_settings_nonce']) && wp_verify_nonce($_POST['safe_link_checker_settings_nonce'], 'safe_link_checker_settings')) {
                // Proceed with saving settings
                update_option('safe_link_checker_url', sanitize_text_field($_POST['safe_link_checker_url']));
            }
        }
    
        if(isset($_POST['safe_link_checker_whitelist'])){
            // Verify nonce
            if (isset($_POST['safe_link_checker_settings_nonce']) && wp_verify_nonce($_POST['safe_link_checker_settings_nonce'], 'safe_link_checker_settings')) {
                // Proceed with saving settings
                update_option('safe_link_checker_whitelist', sanitize_text_field($_POST['safe_link_checker_whitelist']));
            }
        }
    }
    

    // Content filter to modify links before display
    public function filter_content($content) {
        $safe_check_url = get_option('safe_link_checker_url', 'https://example.com/safecheck');
        $whitelist = get_option('safe_link_checker_whitelist', '');

        // Use regular expressions to find and replace external links
        $pattern = '/<a(.*?)href=["\'](http[s]?:\/\/[^"\']+)["\'](.*?)>/i';
        $content = preg_replace_callback($pattern, array($this, 'replace_link_callback'), $content);
        
        return $content;
    }

    public function replace_link_callback($matches) {
        $safe_check_url = get_option('safe_link_checker_url', 'https://example.com/safecheck');
        $url = $matches[2];

        // Check if the URL is in the whitelist
        $whitelist = get_option('safe_link_checker_whitelist', '');
        $whitelist_urls = explode(',', $whitelist);

        $parsed_url = wp_parse_url($url);
        $site_url = parse_url(get_site_url());

        if (
            isset($parsed_url['host']) &&
            $parsed_url['host'] === $site_url['host'] &&
            in_array($url, $whitelist_urls)
        ) {
            return $matches[0]; // Do not replace whitelisted internal links
        }

        // Check if the URL is an internal link
        if (
            isset($parsed_url['host']) &&
            $parsed_url['host'] === $site_url['host']
        ) {
            return $matches[0]; // Do not replace internal links
        }

        // Replace external links
        $replacement = '<a' . $matches[1] . 'href="' . esc_url($safe_check_url) . '?url=' . urlencode($url) . '"' . $matches[3] . '>';
        return $replacement;
    }

    // Hook to deactivate the plugin
    public function deactivate_plugin() {
        // Remove the content filter added by the plugin
        remove_filter('the_content', array($this, 'filter_content'));
    }

    public function display_admin_notice() {
        // Check if the option is set
        $notice_shown = get_option('safe_link_checker_notice_shown', false);
    
        if (!$notice_shown) {
            // Localize the message for translation
            $message = sprintf(
                __('感謝您的安裝！此外掛的設定介面位於左側選單中的「Safe Link Checker」。請在設定頁面中設定您的安全檢查API介面的網址。請注意：在您移除此外掛後，此外掛所設定的所有外部連結檢查網址將失效。謝謝。', 'your-text-domain'),
                '<strong>Safe Link Checker</strong>'
            );
            
            // 進行 HTML 和 URL 轉義
            $message = esc_html($message);
            $url = esc_url(admin_url('admin.php?page=safe-link-checker-settings'));
            
            // 顯示通知，並提供可關閉選項
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p><a href="%s" class="button-primary">%s</a></div>',
                $message,
                $url,
                __('前往設定', 'your-text-domain')
            );
    
            // Set the option to prevent the notice from showing again
            update_option('safe_link_checker_notice_shown', true);
        }
    }
    
}

// Create an instance of the SafeLinkCheckerPlugin class
$safe_link_checker_plugin = new SafeLinkCheckerPlugin();

?>