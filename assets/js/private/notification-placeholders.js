(function () {
    tinymce.PluginManager.add('notification_placeholders', function (editor) {
        const id = editor.id ?? '';

        // Definimos los placeholders según el id
        let placeholders;

        console.log(id);
        if (id === 'body_5') {
            placeholders = ['[OWNER_NAME]', '[OWNER_POSITION]', '[PROPERTY]', '[FROM_DATE]', '[TO_DATE]'];
        } else if (id === 'body_6') {
            placeholders = ['[NAME]', '[EMAIL]', '[PASSWORD]'];
        } else if (id === 'body_7') {
            placeholders = ['[REQUESTOR]', '[RECIPIENT]', '[REQUESTOR_DATES]', '[RECIPIENT_DATES]', '[PROPERTY]'];
        } else {
            placeholders = ['[NAME]', '[PHONE]', '[EMAIL]', '[PROPERTY]'];
        }


        // Convertimos cada placeholder en item del menú
        const menu = placeholders.map(ph => ({
            text: ph,
            onclick: () => editor.insertContent(ph)
        }));

        editor.addButton('notification_placeholders', {
            type: 'menubutton',
            text: 'Placeholders',
            icon: false,
            menu
        });
    });
})();