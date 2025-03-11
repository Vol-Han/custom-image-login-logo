jQuery(document).ready(function($) {
    var mediaUploader;

    $('.cill-upload-button').click(function(e) {
        e.preventDefault();

        // Create the media library window if it does not already exist
        if (!mediaUploader) {
            mediaUploader = wp.media({
                title: cill_localize.choose_logo,
                button: {
                    text: cill_localize.use_this_image
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#cill_logo_url').val(attachment.url);
            });
        }

        mediaUploader.open();
    });

    // Remove the selected image
    $('.cill-remove-button').click(function() {
        $('#cill_logo_url').val('');
    });
});