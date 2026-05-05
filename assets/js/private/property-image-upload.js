jQuery(document).ready(function($){
    $('#upload_image_button').on('click', function(e) {
        e.preventDefault();

        var image_frame;
        if(image_frame){
            image_frame.open();
        }

        // Define la media uploader
        image_frame = wp.media({
            title: 'Select or Upload Image',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Use this image' }
        });

        image_frame.on('select', function(){
            var attachment = image_frame.state().get('selection').first().toJSON();
            $('#property_image').val(attachment.url);
            $('#property_image_preview').attr('src', attachment.url).show();
        });

        image_frame.open();
    });
});