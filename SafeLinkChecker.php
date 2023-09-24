if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
Plugin Name: Safe Link Checker
Description: Automatically convert external links to a safe check URL.
Version: 1.0
Author: Your Name
Author URI: https://yourwebsite.com
Plugin URI: https://yourwebsite.com/safe-link-checker
License: GPL
*/

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
    }
    
    // Settings field callback function
    public function url_callback() {
        $url = get_option('safe_link_checker_url', 'https://example.com/safecheck');
        echo '<input type="text" name="safe_link_checker_url" value="' . esc_attr($url) . '" />';
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
    }

    // Content filter to modify links before display
    public function filter_content($content) {
        $safe_check_url = get_option('safe_link_checker_url', 'https://example.com/safecheck');
        
        // Use regular expressions to find and replace external links
        $pattern = '/<a(.*?)href=["\'](http[s]?:\/\/[^"\']+)["\'](.*?)>/i';
        $replacement = '<a$1href="' . esc_url($safe_check_url) . '?url=$2"$3>';
        $content = preg_replace($pattern, $replacement, $content);
        
        return $content;
    }

    // Hook to deactivate the plugin
    public function deactivate_plugin() {
        // Remove the content filter added by the plugin
        remove_filter('the_content', array($this, 'filter_content'));
    }
}

// Create an instance of the SafeLinkCheckerPlugin class
$safe_link_checker_plugin = new SafeLinkCheckerPlugin();
