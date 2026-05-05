document.addEventListener('DOMContentLoaded', function () {

    const admin_ajax = document.querySelector('#mojo-admin_ajax').value,
        uri = document.querySelector('#mojo-uri').value;

    let divQuote = document.querySelector('.mojo_sproperty-quote');

    if (divQuote) {
        let form = divQuote.querySelector('form');

        divQuote.querySelector('[type="submit"]').addEventListener('click', (e) => {
            e.preventDefault();

            let dates = form.querySelector('[name="daterange"]'),
                property = form.querySelector('[name="property"]'),
                owner = form.querySelector('[name="owner"]');

            if (!dates.value.trim() || !property.value.trim() || !owner.value.trim()) {
                toast('Please fill in all fields before submitting the quote.');
                return;
            }

            fetch(admin_ajax, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'mojo_send_quote',
                    property: property.value,
                    owner: owner.value,
                    dates: dates.value
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        toast(`Request Sent`, 5000, 'success');
                    } else {
                        toast(`An error occurred while submitting the quote`);
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    toast(`An error occurred while submitting the quote`);
                });

        })
    }

})

function toast(message = '', timer = 5000, icon = 'error') {
    Swal.fire({
        position: "top-end",
        timer: timer,
        timerProgressBar: true,
        backdrop: false,
        text: message,
        icon: icon,
        showCancelButton: false,
        showCloseButton: false,
        showConfirmButton: false,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    }).then((result) => {
        if (result.dismiss === Swal.DismissReason.timer || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
            return;
        }
    })
}