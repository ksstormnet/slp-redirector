jQuery(document).ready(function ($) {
    $('.slpr-enable-redirect').change(function () {
        var $row = $(this).closest('tr');
        $row.find('.slpr-url-input, .slpr-save-row').prop('disabled', !this.checked);
    });

    $('.slpr-save-row').click(function () {
        var postType = $(this).data('post-type');
        var url = $(this).closest('tr').find('.slpr-url-input').val();
        var data = {
            action: 'slpr_save_redirect',
            nonce: slpr_ajax_object.nonce,
            post_type: postType,
            url: url
        };

        $.post(slpr_ajax_object.ajax_url, data, function (response) {
            if (response.success) {
                alert('Redirect saved');
            } else {
                alert('Error: ' + response.data);
            }
        }).fail(function () {
            alert('Error saving redirect');
        });
    });
});
