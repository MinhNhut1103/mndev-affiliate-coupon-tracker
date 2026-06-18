jQuery(document).ready(function($) {
    var $input = $('#mndev_affiliate_code');
    if ($input.length === 0) return;

    // Create icon element
    var $icon = $('<span id="mndev_aff_status_icon" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; display: none;"></span>');
    
    // The input is usually inside a wrapper <span class="woocommerce-input-wrapper">
    var $wrapper = $input.parent('.woocommerce-input-wrapper');
    if ($wrapper.length) {
        $wrapper.css('position', 'relative');
        $wrapper.append($icon);
    } else {
        $input.parent().css('position', 'relative');
        $input.after($icon);
    }

    var typingTimer;
    var doneTypingInterval = 500; // debounce 500ms

    $input.on('keyup', function () {
        clearTimeout(typingTimer);
        var code = $(this).val().trim();
        if (code.length > 0) {
            $icon.hide();
            typingTimer = setTimeout(validateAffiliate, doneTypingInterval);
        } else {
            $icon.hide();
        }
    });

    $input.on('keydown', function () {
        clearTimeout(typingTimer);
    });

    function validateAffiliate() {
        var code = $input.val().trim();
        if (!code) return;
        
        $icon.html('<span style="color:#888;">⏳</span>').show();

        $.ajax({
            url: mndev_affiliate_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mndev_validate_affiliate',
                nonce: mndev_affiliate_ajax.nonce,
                code: code
            },
            success: function(response) {
                if (response.success) {
                    $icon.html('<span style="color: green; font-weight: bold;">✔</span>').show();
                } else {
                    $icon.html('<span style="color: red; font-weight: bold;">✖</span>').show();
                }
            }
        });
    }
});
