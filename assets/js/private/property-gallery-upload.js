jQuery(document).ready(function($) {
    let gallery_frame;

    // Abrir la galería de medios
    $('#upload_gallery_button').on('click', function(e) {
        e.preventDefault();

        if (gallery_frame) {
            gallery_frame.open();
            return;
        }

        gallery_frame = wp.media({
            title: 'Select images for the gallery',
            button: {
                text: 'Use these images'
            },
            multiple: true
        });

        gallery_frame.on('select', function() {
            const selection = gallery_frame.state().get('selection');

            // Obtener imágenes actuales
            let current_urls = JSON.parse($('#property_gallery').val() || '[]');
            let new_urls = [];

            selection.each(function(attachment) {
                const url = attachment.attributes.url;

                // Evitar duplicados
                if (!current_urls.includes(url)) {
                    current_urls.push(url);
                    new_urls.push(url);
                }
            });

            // Agregar al preview solo las nuevas
            new_urls.forEach(function(url) {
                $('#gallery_preview').append(`
                    <div class="gallery-item" style="position:relative;">
                        <span class="remove-image">&times;</span>
                        <img src="${url}" style="width:80px;height:auto;border-radius:4px;" />
                    </div>
                `);
            });

            $('#property_gallery').val(JSON.stringify(current_urls));
        });

        gallery_frame.open();
    });

    // Eliminar imagen individual
    $('#gallery_preview').on('click', '.remove-image', function() {
        const parentDiv = $(this).closest('.gallery-item');
        const imgUrl = parentDiv.find('img').attr('src');

        // Remover del input hidden
        let urls = JSON.parse($('#property_gallery').val() || '[]');
        urls = urls.filter(url => url !== imgUrl);
        $('#property_gallery').val(JSON.stringify(urls));

        // Remover del DOM
        parentDiv.remove();
    });
});