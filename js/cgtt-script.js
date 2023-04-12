jQuery(document).ready(function($) {
    // Handle button click event
    $('.cgtt-block-editor-plugin-button').on('click', function() {
        var post_id = CGTT_BlockEditorPluginAjax.post_id; // Retrieve the Post ID from localized script
        var data = {
            'action': 'CGTT_block_editor_plugin_get_article_title',
            'post_id': post_id
        };
        
        // Send AJAX request to retrieve article title
        $.post(CGTT_BlockEditorPluginAjax.ajaxurl, data, function(response) {
            // Replace line breaks with <br> tags
            response = response.replace(/\n/g, '<br>');

            // Display article title below the button
            $('.cgtt-block-editor-plugin-article-title').html(response).show();

            // Enable the button
            var button = document.querySelector('.cgtt-block-editor-plugin-button');
            button.disabled = false;
            button.classList.remove('.cgtt-block-editor-plugin-button-disabled');
        });
    });
});




document.addEventListener('DOMContentLoaded', function() {
    var button = document.querySelector('.cgtt-block-editor-plugin-button');
    button.addEventListener('click', function() {
        // Disable the button
        button.disabled = true;
        // Add a class to style the button as grayed out
        button.classList.add('.cgtt-block-editor-plugin-button-disabled');
        
        // Your code to handle the button click event, e.g. retrieve article title and display it
        
        // After processing is complete, you can re-enable the button if needed
        // button.disabled = false;
        // button.classList.remove('cgtt-block-editor-plugin-button-disabled');
    });
});
