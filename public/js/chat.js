jQuery(document).ready(function($) {
    var chatMessages = $('#ffai-chat-messages');
    var chatForm = $('#ffai-chat-form');
    var chatInput = $('#ffai-chat-input');

    chatForm.on('submit', function(e) {
        e.preventDefault();
        var message = chatInput.val().trim();
        if (message) {
            appendMessage('You', message);
            chatInput.val('');
            sendMessage(message);
        }
    });

    function appendMessage(sender, message) {
        chatMessages.append('<div class="ffai-chat-message"><strong>' + sender + ':</strong> ' + message + '</div>');
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function sendMessage(message) {
        $.ajax({
            url: ffai_chat_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'ffai_chat_request',
                nonce: ffai_chat_vars.nonce,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    appendMessage('AI', response.data);
                } else {
                    appendMessage('Error', 'Failed to get a response. Please try again.');
                }
            },
            error: function() {
                appendMessage('Error', 'An error occurred. Please try again later.');
            }
        });
    }
});