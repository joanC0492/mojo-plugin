jQuery(document).ready(function($){
    $('#add_property_operation').on('click', function(e) {
        e.preventDefault();

        const date = $('input[name="operation_date"]').val();
        const title = $('input[name="operation_title"]').val();
        const type = $('[name="operation_type"]').val();
        const description = $('input[name="operation_description"]').val();
        const propertyId = $('#property_operation').data('property-id'); // Agrega esto al HTML

        if (!date || !title || !type) {
            alert('Please complete the required fields.');
            return;
        }

        $.post(ajaxurl, {
            action: 'save_property_operation',
            date: date,
            title: title,
            description: description,
            type: type,
            property_id: propertyId
        }, function(response) {
            if (response.success) {

                const descriptionHtml = description ? `<p>${description}</p>` : '';

                const newRow = `
                    <tr>
                        <td colspan="4">
                            <p><b>${title}</b> - <span><i>${date} (${type})</i></span></p>
                            ${descriptionHtml}
                        </td>
                        <td>
                            <button type="button" class="delete_prop_operation" data-id="${response.data.id}">&#10006;</button>
                        </td>
                    </tr>
                `;

                $('#property_operation tbody').append(newRow);

                $('input[name="operation_date"]').val('');
                $('input[name="operation_title"]').val('');
                // $('[name="operation_type"]').val('');
                $('input[name="operation_description"]').val('');

            } else {

                alert('Error saving to database.');
                return;

            }
        });
    });


    $(document).on('click', '.delete_prop_operation', function(e) {
        e.preventDefault();
    
        if (!confirm('Are you sure you want to delete this operation?')) return;
    
        const button = $(this);
        const row = button.closest('tr');
        const operationId = button.data('id');
    
        if (!operationId) {
            row.remove(); // Si es una fila sin guardar
            return;
        }
    
        $.post(ajaxurl, {
            action: 'delete_property_operation',
            operation_id: operationId
        }, function(response) {
            if (response.success) {
                
                row.remove();
                
            } else {

                alert('Error deleting from database.');
                return;

            }
        });
    });

});