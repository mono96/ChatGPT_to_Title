<?php
/*
Plugin Name: ChatGPT to Title
Plugin URI: https://github.com/mono96/ChatGPT_to_Title
Description: Use chat-gpt to learn about suggestions for improving article titles.
Version: 1.1.0
Author: Nobuhito Ohigashi
Author URI: https://mono96.jp
Text Domain: cgtt_chatgpt-to-title
Domain Path: /languages/
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// テキストドメインをロード
load_plugin_textdomain( 'cgtt_chatgpt-to-title', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

// Update the API key and model in the database
function CGTT_update_chatgpt_title_settings($input) {
    if (isset($input['CGTT_chatgpt_title_api_key'])) {
        update_option('CGTT_chatgpt_title_api_key', $input['CGTT_chatgpt_title_api_key']);
    }
    if (isset($input['CGTT_chatgpt_title_model'])) {
        update_option('CGTT_chatgpt_title_model', $input['CGTT_chatgpt_title_model']);
    }
}
add_action('admin_init', function() {
    register_setting('CGTT_chatgpt_title_settings', 'CGTT_chatgpt_title_api_key');
    register_setting('CGTT_chatgpt_title_settings', 'CGTT_chatgpt_title_model');
});

// Create a menu in the administration settings
function CGTT_chatgpt_title_plugin_menu() {
    add_options_page(__('ChatGPT to Title Settings', 'cgtt_chatgpt-to-title'), 'ChatGPT to Title', 'manage_options', 'chatgpt-title-settings', 'CGTT_chatgpt_title_plugin_settings_page');
}
add_action('admin_menu', 'CGTT_chatgpt_title_plugin_menu');

// Settings page callback function
function CGTT_chatgpt_title_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1> <?php _e('ChatGPT to Title Settings', 'cgtt_chatgpt-to-title')  ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('CGTT_chatgpt_title_settings'); ?>
            <?php do_settings_sections('CGTT_chatgpt_title_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td><input type="text" name="CGTT_chatgpt_title_api_key" value="<?php echo esc_attr(get_option('CGTT_chatgpt_title_api_key')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Model</th>
                    <td>
                        <label><input type="radio" name="CGTT_chatgpt_title_model" value="gpt-3.5-turbo" <?php checked(get_option('CGTT_chatgpt_title_model'), 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</label><br>
                        <label><input type="radio" name="CGTT_chatgpt_title_model" value="gpt-4" <?php checked(get_option('CGTT_chatgpt_title_model'), 'gpt-4'); ?>>gpt-4</label>
                    </td>
                </tr>
            </table>
            <p><?php _e('To use the gpt-4 model, you must be registered and INVITED on ', 'cgtt_chatgpt-to-title')  ?><a href="https://openai.com/waitlist/gpt-4-api">GPT-4 API waitlist</a></p>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Delete API key when plugin is deactivated
function CGTT_chatgpt_title_plugin_deactivation_hook() {
    delete_option('CGTT_chatgpt_title_api_key');
    delete_option('CGTT_chatgpt_title_model');
}
register_deactivation_hook(__FILE__, 'CGTT_chatgpt_title_plugin_deactivation_hook');

// Add meta box to block editor sidebar
function CGTT_block_editor_plugin_add_custom_meta_box() {
    add_meta_box(
        'CGTT_block_editor_plugin_meta_box',
        'ChatGPT to Title',
        'CGTT_block_editor_plugin_meta_box_callback',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'CGTT_block_editor_plugin_add_custom_meta_box');

// Meta box callback function
function CGTT_block_editor_plugin_meta_box_callback($post) {
    echo '<div class="cgtt-block-editor-plugin-sidebar-panel">';
    echo '<div style="display:flex;align-items: baseline;"><h3>ChatGPT to Title</h3>';
    echo '<p style="padding-left:20px;" class="chatgpt_model_type">model : ' . get_option('CGTT_chatgpt_title_model') .'</p></div>';
    echo '<button type="button" class="cgtt-block-editor-plugin-button button">' . __('Use ChatGPT', 'cgtt_chatgpt-to-title') . '</button>';
    echo '<p class="cgtt-block-editor-plugin-article-title" style="display:none;"></p>';
    echo '</div>';
    
    // Enqueue JavaScript to handle button click event
    wp_enqueue_script('CGTT-block-editor-plugin-script', plugin_dir_url(__FILE__) . 'js/cgtt-script.js', array('jquery'), '1.0', true);
    
    // Pass the AJAX URL and Post ID to the JavaScript file
    wp_localize_script('CGTT-block-editor-plugin-script', 'CGTT_BlockEditorPluginAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'post_id' => $post->ID
    ));
}

// AJAX callback function to get article title
function CGTT_block_editor_plugin_get_article_title() {
    $post_id = $_POST['post_id'];
    $post = get_post($post_id);
    $article_title = esc_html($post->post_title);
    // Output the JavaScript code to disable the button
    if ($article_title) {
        $article_idea = esc_html(CGTT_chatgpt_wp_title_idea($article_title));
    } else {
        $article_idea="タイトルが空白です。";
    }
    wp_send_json($article_idea);
}


function CGTT_chatgpt_wp_title_idea($title) {
// 言語設定
$locale = get_locale();
// 言語コードに応じて言語名と出力文字数を設定
if ($locale === 'ja') {
    $language = 'Japanese';
    $output_character = '78' ;
} elseif ($locale === 'en_US' || $locale === 'en_GB') {
    $language = 'English';
    $output_character = '48' ;
} else {
    // デフォルトの言語名を設定
    $language = 'English';
    $output_character = '48' ;
}


$result = array();
// APIキー
$apiKey = get_option('CGTT_chatgpt_title_api_key');
// model
$chatgpt_model = get_option('CGTT_chatgpt_title_model');

//openAI APIエンドポイント
$endpoint = 'https://api.openai.com/v1/chat/completions';

$headers = array(
  'Content-Type: application/json',
  'Authorization: Bearer ' . $apiKey
);



// リクエストのペイロード
$data = array(
  'model' => $chatgpt_model ,
  'messages' => [
    [
    "role" => "system",
    "content" => "あなたはプロの編集者です。"
    ],
    [
    "role" => "user",
    "content" =>"The title of the blog ' " .  $title . " ' more attention and interest-grabbing, without emojis, very attractive, buzz-worthy with a strong impression that no one can ignore on social networking sites, and SEO-friendly? Please suggest five specific improved titles in  ". $output_character ." characters " . $language . "." 
    ]
  ]
);

// cURLリクエストを初期化
$ch = curl_init();

// cURLオプションを設定
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// APIにリクエストを送信
$response = curl_exec($ch);

// cURLリクエストを閉じる
curl_close($ch);

// 応答を解析
$result = json_decode($response, true);

// 生成されたテキストを取得
   $chatgpt_title_idea = $result['choices'][0]['message']['content'];
    return $chatgpt_title_idea;
    
    
}
 

// Hook the AJAX action
add_action('wp_ajax_CGTT_block_editor_plugin_get_article_title', 'CGTT_block_editor_plugin_get_article_title');

// Enqueue JavaScript file
function CGTT_block_editor_plugin_enqueue_scripts() {
    wp_enqueue_script('CGTT-block-editor-plugin-script', plugin_dir_url(__FILE__) . 'js/cgtt-script.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'CGTT_block_editor_plugin_enqueue_scripts');
