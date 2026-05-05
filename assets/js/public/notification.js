jQuery(document).ready(function ($) {

    const admin_ajax = document.querySelector('#mojo-admin_ajax').value;

    let notificationButtons = document.querySelectorAll('.mojo_notifications-toggle');
    if (notificationButtons) {
        notificationButtons.forEach(n => {
            n.addEventListener('click', (e) => {
                e.preventDefault();
                e.currentTarget.closest('.latest_notifications').classList.toggle('active');
            })
        })
    }

    // -----------------------------------------------------------------------------
    // -----------------------------------------------------------------------------

    const loadMoreBtn = document.querySelector('.latest_notifications-foot button');
    const notifContainer = document.querySelector('.notifications');
    const ownerId = notifContainer?.dataset?.idOwner;

    if (loadMoreBtn && notifContainer && ownerId) {
        loadMoreBtn.addEventListener('click', () => {

            var offset = document.querySelector('[name="offset_notifications"]');
            var offsetValue = parseInt(offset?.value || 5);

            fetch(`${admin_ajax}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'load_more_notifications',
                    owner_id: ownerId.value,
                    offset: offsetValue
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.html) {
                        notifContainer.insertAdjacentHTML('beforeend', data.html);
                        offset.value = offsetValue + 5;

                        // Si vinieron menos de 5 notificaciones, ocultamos el botón
                        if (data.count < 5) {
                            loadMoreBtn.style.display = 'none';
                        }
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                });
        });
    }

    // -----------------------------------------------------------------------------
    // -----------------------------------------------------------------------------

    let download_booking_calendar = document.querySelectorAll('[data-toggle="pdf"]');

    if (download_booking_calendar && download_booking_calendar.length) {
        download_booking_calendar.forEach((btn) => btn.addEventListener('click', (e) => {
            e.preventDefault();

            let currentTarget = e.currentTarget;

            if (!currentTarget.classList.contains('disabled')) {
                currentTarget.classList.add('disabled');
            }

            let property_id = currentTarget.getAttribute('data-id-property') ?? '';
            let calendar_id = currentTarget.getAttribute('data-id-calendar') ?? '';
            let year = currentTarget.getAttribute('data-year') ?? '';
            let isJustMe = currentTarget.classList.contains('just-me');

            if (property_id && calendar_id) {
                const params = {
                    action: 'mojo_download_booking_calendar',
                    property_id: property_id,
                    year: year,
                    calendar_id: calendar_id,
                    scope: isJustMe ? 'just_me' : 'all'
                };

                fetch(`${admin_ajax}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(params)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            console.log(data?.data?.message || 'Error generating PDF.');
                            return;
                        }

                        const url = data.data.url;
                        const suggestedName = `${data.data.property_name}` + ' - Calendar ' + `${year}` + '.pdf';

                        // Forzar descarga del PDF
                        fetch(url)
                            .then(r => {
                                if (!r.ok) throw new Error('Failed to fetch PDF');
                                return r.blob();
                            })
                            .then(blob => {
                                const blobUrl = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = blobUrl;
                                // Usa el nombre sugerido o el del archivo en la URL
                                a.download = suggestedName || url.split('/').pop().split('?')[0];
                                document.body.appendChild(a);
                                a.click();
                                a.remove();
                                URL.revokeObjectURL(blobUrl);
                            })
                            .catch(err => {
                                console.error('Download error:', err);
                                // Fallback: abrir en nueva pestaña si algo falla
                                window.open(url, '_blank');
                            });

                        if (currentTarget.classList.contains('disabled')) {
                            currentTarget.classList.remove('disabled');
                        }
                    })
                    .catch(error => {
                        console.error('Error in the request:', error);
                        if (currentTarget.classList.contains('disabled')) {
                            currentTarget.classList.remove('disabled');
                        }
                    });
            }
        }));
    }

});