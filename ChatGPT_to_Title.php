<?php
/*
Plugin Name: ChatGPT to Title
Plugin URI: https://github.com/mono96/ChatGPT_to_Title
Description: Use chat-gpt to learn about suggestions for improving article titles.
Version: 1.0
Author: Nobuhito Ohigashi
Author URI: https://mono96.jp
License: GPL2
*/

// Update the API key and model in the database
function update_chatgpt_title_settings($input) {
    if (isset($input['chatgpt_title_api_key_mono96'])) {
        update_option('chatgpt_title_api_key_mono96', $input['chatgpt_title_api_key_mono96']);
    }
    if (isset($input['chatgpt_title_model_mono96'])) {
        update_option('chatgpt_title_model_mono96', $input['chatgpt_title_model_mono96']);
    }
}
add_action('admin_init', function() {
    register_setting('chatgpt_title_settings', 'chatgpt_title_api_key_mono96');
    register_setting('chatgpt_title_settings', 'chatgpt_title_model_mono96');
});

// Create a menu in the administration settings
function chatgpt_title_plugin_menu() {
    add_options_page('ChatGPT to Title Settings', 'ChatGPT to Title', 'manage_options', 'chatgpt-title-settings', 'chatgpt_title_plugin_settings_page');
}
add_action('admin_menu', 'chatgpt_title_plugin_menu');

// Settings page callback function
function chatgpt_title_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>ChatGPT to Title Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('chatgpt_title_settings'); ?>
            <?php do_settings_sections('chatgpt_title_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td><input type="text" name="chatgpt_title_api_key_mono96" value="<?php echo esc_attr(get_option('chatgpt_title_api_key_mono96')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Model</th>
                    <td>
                        <label><input type="radio" name="chatgpt_title_model_mono96" value="gpt-3.5-turbo" <?php checked(get_option('chatgpt_title_model_mono96'), 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</label><br>
                        <label><input type="radio" name="chatgpt_title_model_mono96" value="gpt-4" <?php checked(get_option('chatgpt_title_model_mono96'), 'gpt-4'); ?>>gpt-4</label>
                    </td>
                </tr>
            </table>
            <p>gpt-4 モデルを利用するには、<a href="https://openai.com/waitlist/gpt-4-api">GPT-4 API waitlist</a>に登録とinvitedが必要です。</p>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


// Add meta box to block editor sidebar
function my_block_editor_plugin_add_custom_meta_box() {
    add_meta_box(
        'my_block_editor_plugin_meta_box',
        'ChatGPT to Title',
        'my_block_editor_plugin_meta_box_callback',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'my_block_editor_plugin_add_custom_meta_box');

// Meta box callback function
function my_block_editor_plugin_meta_box_callback($post) {
    echo '<div class="my-block-editor-plugin-sidebar-panel">';
    echo '<div style="display:flex;align-items: baseline;"><h3>ChatGPT to Title</h3>';
    echo '<p style="padding-left:20px;" class="chatgpt_model_type">model : ' . get_option('chatgpt_title_model_mono96') .'</p></div>';
    echo '<button type="button" class="my-block-editor-plugin-button button">タイトルを考える</button>';
    echo '<p class="my-block-editor-plugin-article-title" style="display:none;"></p>';
    echo '</div>';
    
    // Enqueue JavaScript to handle button click event
    wp_enqueue_script('my-block-editor-plugin-script', plugin_dir_url(__FILE__) . 'js/my-block-editor-plugin-script.js', array('jquery'), '1.0', true);
    
    // Pass the AJAX URL and Post ID to the JavaScript file
    wp_localize_script('my-block-editor-plugin-script', 'myBlockEditorPluginAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'post_id' => $post->ID
    ));
}

// AJAX callback function to get article title
function my_block_editor_plugin_get_article_title() {
    $post_id = $_POST['post_id'];
    $post = get_post($post_id);
    $article_title = $post->post_title;
    // Output the JavaScript code to disable the button
    if ($article_title) {
    $article_idea=chatgpt_wp_title_idea($article_title);
    } else {
    $article_idea="タイトルが空白です。";
    }

    
    wp_send_json($article_idea);
}


function chatgpt_wp_title_idea($title) {

$result = array();
// APIキー
$apiKey = get_option('chatgpt_title_api_key_mono96');
// model
$chatgpt_model = get_option('chatgpt_title_model_mono96');

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
    "content" =>"ブログのタイトル「" .  $title . "」をより注目や興味を集めやすく、絵文字を使わずに、とても魅力的で、SNSで誰もが無視できない強い印象でバズりやすいタイトルにするにはどうすればいいでしょうか？具体的な改善タイトルを日本語で5つ提案してください。各々のタイトル案は「」で囲ってください。" 
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
add_action('wp_ajax_my_block_editor_plugin_get_article_title', 'my_block_editor_plugin_get_article_title');

// Enqueue JavaScript file
function my_block_editor_plugin_enqueue_scripts() {
    wp_enqueue_script('my-block-editor-plugin-script', plugin_dir_url(__FILE__) . 'js/my-block-editor-plugin-script.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'my_block_editor_plugin_enqueue_scripts');
