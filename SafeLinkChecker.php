<?php
/*
Plugin Name: Safe Link Checker
Description: Automatically convert external links to a safe check URL.
Version: 0.4
Author: Your Name
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
                settings_fields('safe_link_checker_settings_group');
                do_settings_sections('safe-link-checker-settings');
                submit_button();
                ?>
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

    // Validate and save settings fields
    public function save_plugin_settings() {
        if (isset($_POST['safe_link_checker_url'])) {
            update_option('safe_link_checker_url', sanitize_text_field($_POST['safe_link_checker_url']));
        }

        if (isset($_POST['safe_link_checker_whitelist'])) {
            update_option('safe_link_checker_whitelist', sanitize_text_field($_POST['safe_link_checker_whitelist']));
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
}

// Create an instance of the SafeLinkCheckerPlugin class
$safe_link_checker_plugin = new SafeLinkCheckerPlugin();
?>
