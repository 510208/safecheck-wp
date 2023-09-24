<?php
/*
Plugin Name: Safe Link Checker
Description: Automatically convert external links to a safe check URL.
Version: 0.2
Author: 510208
*/

class SafeLinkChecker {

    public function __construct() {
        // 添加設定選單
        add_action('admin_menu', array($this, 'safe_link_checker_menu'));

        // 注册设置
        add_action('admin_init', array($this, 'safe_link_checker_register_settings'));

        // 添加内容过滤器
        add_filter('the_content', array($this, 'safe_link_checker_filter_content'));
        
        // 钩住保存设置
        add_action('admin_post_save_safe_link_checker_settings', array($this, 'safe_link_checker_save_settings'));
        
        // 钩住停用插件
        register_deactivation_hook(__FILE__, array($this, 'safe_link_checker_deactivate'));
    }

    // 添加設定選單
    public function safe_link_checker_menu() {
        add_menu_page(
            'Safe Link Checker',
            'Safe Link Checker',
            'manage_options',
            'safe-link-checker-settings',
            array($this, 'safe_link_checker_settings_page')
        );
    }

    // 創建設定頁面內容
    public function safe_link_checker_settings_page() {
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

    // 註冊設定欄位
    public function safe_link_checker_register_settings() {
        register_setting(
            'safe_link_checker_settings_group',
            'safe_link_checker_url',
            'sanitize_text_field'
        );

        add_settings_section(
            'safe_link_checker_section',
            'Safe Link Checker Settings',
            array($this, 'safe_link_checker_settings_section_callback'),
            'safe-link-checker-settings'
        );

        add_settings_field(
            'safe_link_checker_url',
            '已取得的API URL系統',
            array($this, 'safe_link_checker_url_callback'),
            'safe-link-checker-settings',
            'safe_link_checker_section'
        );
    }
    
    // 设置字段回调函数
    public function safe_link_checker_url_callback() {
        $url = get_option('safe_link_checker_url', 'https://510208.github.io/safecheck');
        echo '<input type="text" name="safe_link_checker_url" value="' . esc_attr($url) . '" />';
    }
    
    // 设置区域回调函数
    public function safe_link_checker_settings_section_callback() {
        echo 'Configure your Safe Link Checker settings here.';
    }

    // 在保存設定時驗證和保存設定欄位
    public function safe_link_checker_save_settings() {
        if (isset($_POST['safe_link_checker_url'])) {
            update_option('safe_link_checker_url', sanitize_text_field($_POST['safe_link_checker_url']));
        }
    }

    // 連結過濾器勾鈎以在內容顯示之前修改連結
    public function safe_link_checker_filter_content($content) {
        $safe_check_url = get_option('safe_link_checker_url', 'https://510208.github.io/safecheck');
        
        // 使用正則表達式查找並替換外部連結
        $pattern = '/<a(.*?)href=["\'](http[s]?:\/\/[^"\']+)["\'](.*?)>/i';
        $replacement = '<a$1href="' . esc_url($safe_check_url) . '?url=$2"$3>';
        $content = preg_replace($pattern, $replacement, $content);
        
        return $content;
    }

    // 钩住停用插件
    public function safe_link_checker_deactivate() {
        // 移除外挂添加的内容过滤器
        remove_filter('the_content', array($this, 'safe_link_checker_filter_content'));
    }
}

// 创建SafeLinkChecker类的实例
$safe_link_checker = new SafeLinkChecker();
?>