jQuery(document).ready(function($) {
    var $input = $('#mndev_affiliate_code');
    if ($input.length === 0) return;

    // Create icon element
    var $icon = $('<span id="mndev_aff_status_icon" style="position: absolute; right: 10px; top: 15%; transform: translateY(-50%); font-size: 18px; display: none;"></span>');
    
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
        
        $icon.html('<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px; vertical-align: middle; animation: spin 1s linear infinite;"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>').show();

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
                    $icon.html('<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px; vertical-align: middle;"><circle cx="12" cy="12" r="10" fill="#28a745"/><path d="M8.5 12.5L11 15L15.5 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>').show();
                } else {
                    $icon.html('<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px; vertical-align: middle;"><circle cx="12" cy="12" r="10" fill="#dc3545"/><path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>').show();
                }
            }
        });
    }
});
