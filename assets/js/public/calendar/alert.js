export function confirmationAlert({
    title = 'Are you sure?',
    text = '',
    confirmText = 'Yes',
    cancelText = 'Cancel',
    icon = 'warning',
    onConfirm = () => {},
    onCancel = () => {}
}) {
    Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            onConfirm();
        } else {
            onCancel();
        }
    });
}

export function successAlert({
    title = '',
    text = '',
    icon = 'success',
    iconHtml = null,
    reload = false,
    onClose = () => {}
}) {
    Swal.fire({
        title,
        text,
        icon,
        iconHtml,
        confirmButtonText: 'OK',
    }).then(() => {
        onClose();
        if (reload) {
            window.location.reload();
        }
    });
}

export function errorAlert({
    title = 'Error',
    text = 'Something went wrong.',
    icon = 'error'
}) {
    Swal.fire({
        title,
        text,
        icon,
        confirmButtonText: 'OK',
    });
}
