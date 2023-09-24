<?php
/*
Plugin Name: Safe Link Checker
Description: 自動將外部連結轉換為安全檢查URL。
Version: 0.2
Author: 510208
*/

// 添加設定選單
function safe_link_checker_menu() {
    add_menu_page(
        'Safe Link Checker',
        'Safe Checker',
        'manage_options',
        'safe-link-checker-settings',
        'safe_link_checker_settings_page'
    );
}
add_action('admin_menu', 'safe_link_checker_menu');

// 創建設定頁面內容
function safe_link_checker_settings_page() {
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
function safe_link_checker_register_settings() {
    register_setting(
        'safe_link_checker_settings_group',
        'safe_link_checker_url',
        'sanitize_text_field'
    );

    add_settings_section(
        'safe_link_checker_section',
        '安全連結檢查器設定',
        '',
        'safe-link-checker-settings'
    );

    add_settings_field(
        'safe_link_checker_url',
        '已取得的API URL系統',
        'safe_link_checker_url_callback',
        'safe-link-checker-settings',
        'safe_link_checker_section'
    );
}
add_action('admin_init', 'safe_link_checker_register_settings');

// 設定欄位回調函數
function safe_link_checker_url_callback() {
    $url = get_option('safe_link_checker_url', 'https://510208.github.io/safecheck');
    echo '<input type="text" name="safe_link_checker_url" value="' . esc_attr($url) . '" />';
}

// 在保存設定時驗證和保存設定欄位
function safe_link_checker_save_settings() {
    if (isset($_POST['safe_link_checker_url'])) {
        update_option('safe_link_checker_url', sanitize_text_field($_POST['safe_link_checker_url']));
    }
}
add_action('admin_post_save_safe_link_checker_settings', 'safe_link_checker_save_settings');

// 連結過濾器勾鈎以在內容顯示之前修改連結
function safe_link_checker_filter_content($content) {
    $safe_check_url = get_option('safe_link_checker_url', 'https://510208.github.io/safecheck');
    
    // 使用正則表達式查找並替換外部連結
    $pattern = '/<a(.*?)href=["\'](http[s]?:\/\/[^"\']+)["\'](.*?)>/i';
    $replacement = '<a$1href="' . esc_url($safe_check_url) . '?url=$2"$3>';
    $content = preg_replace($pattern, $replacement, $content);
    
    return $content;
}

// 添加內容過濾器勾鈎
add_filter('the_content', 'safe_link_checker_filter_content');
?>
