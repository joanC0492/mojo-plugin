document.addEventListener('DOMContentLoaded', function () {
    const logoutButton = document.getElementById('logout-button');

    const admin_ajax = document.querySelector('#mojo-admin_ajax').value,
        uri = document.querySelector('#mojo-uri').value;

    if (logoutButton) {
        logoutButton.addEventListener('click', function () {

            Swal.fire({
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                    return;
                }
                if (result.isConfirmed) {

                    fetch(admin_ajax, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'mojo_logout',
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = uri;
                            } else {
                                alert("Failed to logout. Please try again.");
                            }
                        })
                        .catch(error => {
                            console.error('Logout error:', error);
                            alert("An error occurred while logging out.");
                        });

                }
            })

        });
    }
});