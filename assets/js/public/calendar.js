import {
    validateRentSelection,
    validateExchangeSelection,
    validateExchangeSelectionPart2
} from './calendar/validations.js';

import {
    updateSelectedCounter,
    buildRentParams,
    buildExchangeFromParams,
    buildExchangeToParams,
    confirmDeletePeriod,
    confirmSendExchangeRequest,
    logToPhp
} from './calendar/helpers.js';

import {
    bookPeriod,
    deleteBookedDate,
    rentPeriod,
    saveExchangeFrom,
    removePeriod,
    saveExchangeTo,
    confirmReservation,
    sendRequestToAdmins
} from './calendar/requests.js';

import {
    confirmationAlert, errorAlert, successAlert
} from './calendar/alert.js';

const url = `../shared/calendar-config.js?r=${Date.now()}`;

const {
    addOneDayString,
    subtractOneDayString,
    startValidRange,
    endValidRange,
    initialDate,
    calendarEl,
    countSelectedCells,
    maxDays,
    selectedDays,
    calendarId,
    round,
    qtyShares,
    calendarState,
    minimumNumberOfNights,
    minimumNumberOfNightsInHigh,
    minimumNumberOfNightsIn14Day,
    applyLastDayStyling,
    blockingDates,
    alertError,
    calendarElement,
    bookButton,
    ownerShare,
    ownerPosition,
    ownerId,
    rentButton,
    cancelRentButton,
    cancelExchangeButton,
    exchangeButton,
    resetExchangeButton,
    requestButton,
    // jcc
    buyButton,
    cancelBuyButton,
    selectingDatesInCalendar,
    LS_DATE1_KEY,
    LS_DATE2_KEY,
    LS_TOGGLE_KEY,
    setDateRange,
    formatDMY,
    clearRange,
    daysBetween,
    admin_ajax,
    isAllowed
} = await import(url);

export const ADMIN_AJAX = admin_ajax;

jQuery(document).ready(function ($) {

    var k = 0, j = 0, l = 0, m = 0; // jcc - m = purchase mode
    let firstExchangeBgEvent = null;

    var first_exchange_dates = {}

    // --- Limpiar selección en un reload de la página, mantener el toggle ---
    try {
        const nav = performance.getEntriesByType('navigation')[0];
        const isReload = nav ? (nav.type === 'reload') : (performance.navigation && performance.navigation.type === 1);

        if (isReload) {
            // elimina date1 y date2 del LS (NO tocar el toggle)
            localStorage.removeItem(LS_DATE1_KEY);
            localStorage.removeItem(LS_DATE2_KEY);
            localStorage.setItem(LS_TOGGLE_KEY, 'off');

            // limpia el label si existe
            const el = document.getElementById('date_range');
            if (el) el.innerHTML = '';
        }
    } catch (e) {
        // no romper nada si el navegador no soporta performance API
    }

    const box_body_request = document.querySelector('.box_body_request'),
        completed_status = document.querySelector('.completed_status'),
        uri = document.querySelector('#mojo-uri').value,
        media = document.querySelector('#mojo-media').value;

    // ------------- BLOQUEADOS: construir background events -------------
    // FullCalendar trata el 'end' como EXCLUSIVO: sumamos 1 día
    const blockedBgEvents = BLOCKED_DATES.map(d => ({
        start: d,
        end: addOneDayString(d),
        display: 'background',
        backgroundColor: '#f1f1f1',
        groupId: 'blocked',
        overlap: false,
    }));

    // añade los bloqueados a tu array global de eventos (ya existe)
    events = events.concat(blockedBgEvents);

    calendarElement.calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: initialDate,
        validRange: {
            start: startValidRange,
            end: endValidRange
        },
        headerToolbar: {
            start: 'prev,next title',
            center: '',
            end: '',
        },
        firstDay: 1,
        // selectMinDistance: 4,
        selectable: selectable,
        unselectAuto: false,
        selectMirror: false,
        events: events,
        eventDidMount: applyLastDayStyling,
        select: function (info) {
            selectingDatesInCalendar(info);

            if (k == 1) {
                exchangeSelectedDatesPart1(info);
            }
            if (l == 1) {
                exchangeSelectedDatesPart2(info);
            }
            if (j == 1) {
                rentSelectedDates(info);
            }
            if (m == 1) {
                buySelectedDates(info);
            }
        },
        eventClick: function (info) {

            var eventObj = info.event.extendedProps;

            if (eventObj) {
                deleteBookedDatesByClick(eventObj);
            } else {
                logToPhp('Calendar information does not arrive as expected.', {});
            }
        },
        datesSet: function () {
            const d1 = localStorage.getItem(LS_DATE1_KEY);
            const d2 = localStorage.getItem(LS_DATE2_KEY);
            const tg = localStorage.getItem(LS_TOGGLE_KEY) === 'on' ? 'on' : 'off';

            calendarElement.calendar.unselect();

            if (d1 && d2) {
                // re-pinta el rango completo (end exclusivo)
                const start = d1 <= d2 ? d1 : d2;
                const end = d1 <= d2 ? d2 : d1;
                calendarElement.calendar.select({ start, end: addOneDayString(end), allDay: true });
                setDateRange(start, end);
                return;
            }

            if (d1 && tg === 'on') {
                // hay primer click "pendiente"
                calendarElement.calendar.select({ start: d1, end: addOneDayString(d1), allDay: true });
                setDateRange(d1, d1);
            }
        },
        unselect: function () {
            const el = document.getElementById('date_range');
            if (el) el.textContent = '';
        },
        dateClick: function (info) {
            if (!selectable) {
                return;
            }

            blockingDates(info);
        }
    });
    calendarElement.calendar.render();

    if (bookButton) {
        bookButton.addEventListener('click', (e) => {
            e.preventDefault();

            let fullSelectedDays = parseInt(selectedDays.value) + calendarState.diffNights;
            if (fullSelectedDays > maxDays.value) {
                alertError('You must not select more days than the maximum allocated for this turn.');
                return;
            }

            if (!calendarState.object_result.allowed) {

                logToPhp(calendarState.object_result.message, {
                    code: calendarState.object_result.code ?? null,
                    start: calendarState.strStartDate ?? null,
                    end: calendarState.strEndDate ?? null,
                    calendar_id: calendarId?.value ?? null,
                    owner_id: ownerId?.value ?? null,
                    owner_position: ownerPosition?.value ?? null,
                    round: round?.value ?? null,
                    selectedDays: selectedDays?.value ?? null,
                    diffNights: calendarState?.diffNights ?? null
                });

                alertError(calendarState.object_result.message)
                return;
            }

            var owner_position = document.querySelector('[name="owner_position"]');

            if (calendarState.isInvalidSelection) {
                console.log('❌ Cannot select middle days of an event.');
                alertError('Some of the selected dates are taken.');
                return;
            } else {
                console.log('✅ The selected range is valid.');
            }

            if (fullSelectedDays == parseInt(maxDays.value) - 2 || fullSelectedDays == parseInt(maxDays.value) - 1) {
                alertError('Either book all your available nights or keep at least 3 nights free');
                logToPhp('Either book all your available nights or keep at least 3 nights free', {
                    code: calendarState.object_result.code ?? null,
                    start: calendarState.strStartDate ?? null,
                    end: calendarState.strEndDate ?? null,
                    calendar_id: calendarId?.value ?? null,
                    owner_id: ownerId?.value ?? null,
                    owner_position: ownerPosition?.value ?? null,
                    round: round?.value ?? null,
                    selectedDays: selectedDays?.value ?? null,
                    diffNights: calendarState?.diffNights ?? null
                });
                return;
            }

            confirmationAlert({
                text: 'Do you want to book the property in the selected days?',
                confirmText: 'Yes',
                onConfirm: () => {
                    const params = {
                        action: 'mojo_panel_book_period',
                        calendar_id: calendarId.value,
                        owner_position: owner_position.value,
                        start: calendarState.strStartDate,
                        end: calendarState.strEndDate,
                        round: parseInt(round.value),
                        owner_id: ownerId.value
                    };

                    bookPeriod(params)
                        .then(data => {

                            if (!data?.success) {
                                const errorMessage =
                                    data?.data?.message ||
                                    'There was a problem with the reservation. Please try again.';

                                logToPhp(errorMessage, {
                                    code: calendarState.object_result.code ?? null,
                                    start: calendarState.strStartDate ?? null,
                                    end: calendarState.strEndDate ?? null,
                                    calendar_id: calendarId?.value ?? null,
                                    owner_id: ownerId?.value ?? null,
                                    owner_position: ownerPosition?.value ?? null,
                                    round: round?.value ?? null,
                                    selectedDays: selectedDays?.value ?? null,
                                    diffNights: calendarState?.diffNights ?? null
                                });

                                errorAlert({ text: errorMessage });
                                return;
                            }

                            successAlert({
                                title: 'Reserved!',
                                reload: true,
                                onClose: () => {
                                    logToPhp('Reserved!', {
                                        code: calendarState.object_result.code ?? null,
                                        start: calendarState.strStartDate ?? null,
                                        end: calendarState.strEndDate ?? null,
                                        calendar_id: calendarId?.value ?? null,
                                        owner_id: ownerId?.value ?? null,
                                        owner_position: ownerPosition?.value ?? null,
                                        round: round?.value ?? null,
                                        selectedDays: selectedDays?.value ?? null,
                                        diffNights: calendarState?.diffNights ?? null
                                    });
                                }
                            });
                        })
                        .catch(error => {
                            console.error('Error in the request:', error);
                            logToPhp('Error in the request:', {
                                code: calendarState.object_result.code ?? null,
                                start: calendarState.strStartDate ?? null,
                                end: calendarState.strEndDate ?? null,
                                calendar_id: calendarId?.value ?? null,
                                owner_id: ownerId?.value ?? null,
                                owner_position: ownerPosition?.value ?? null,
                                round: round?.value ?? null,
                                selectedDays: selectedDays?.value ?? null,
                                diffNights: calendarState?.diffNights ?? null
                            });

                            errorAlert({
                                text: 'There was a problem with the request. Please try again.'
                            });
                        });
                },

                onCancel: () => {
                    calendarElement.calendar.unselect();
                    countSelectedCells.innerHTML = `${selectedDays.value}/${maxDays.value}`;
                }
            })
        })
    }

    const confirmDatesButton = document.querySelector('[name="confirm_dates"]');
    if (confirmDatesButton) {
        confirmDatesButton.addEventListener('click', (e) => {
            e.preventDefault();

            var owner_position = document.querySelector('[name="owner_position"]');
            confirmationAlert({
                text: 'Do you want to confirm your reservation?',
                confirmText: 'Yes',

                onConfirm: () => {
                    const params = {
                        action: 'mojo_panel_confirm_reservation',
                        calendar_id: calendarId.value,
                        owner_position: owner_position.value,
                        round: parseInt(round.value),
                        owner_id: ownerId.value
                    };

                    confirmReservation(params)
                        .then(data => {
                            if (!data?.success) {
                                const errorMessage =
                                    data?.data?.message ||
                                    'There was a problem with the reservation. Please try again.';

                                logToPhp(errorMessage, params);

                                errorAlert({ text: errorMessage });
                                return;
                            }

                            successAlert({
                                title: 'Reservation Confirmed!',
                                reload: true,
                                onClose: () => {
                                    logToPhp('Reservation Confirmed!', params);
                                }
                            });
                        })
                        .catch(error => {

                            console.error('Error in the request:', error);

                            logToPhp('Error in the request:', params);

                            errorAlert({
                                text: 'There was a problem with the request. Please try again.'
                            });
                        });
                },

                onCancel: () => {
                    calendarElement.calendar.unselect();
                }
            });
        })
    }

    // ---------------------------------------------------------------------------

    if (rentButton) {
        rentButton.addEventListener('click', (e) => {
            e.preventDefault();

            clearRange();
            unselectCalendar();

            Swal.fire({
                title: "Up for Rental",
                text: "Select the range from your reservations to start renting. Make sure it's a minimum of 3 nights.",
                icon: 'info',
                showConfirmButton: true,
                confirmButtonText: 'OK',
                iconHtml: `<svg width="54" height="53" viewBox="0 0 54 53" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.2012 41.2855L14.1543 51.9562C13.9874 51.6824 13.8749 51.3599 13.8749 50.9887V40.5449L38.4187 16.558L17.2012 41.2855ZM15.7818 52.8487C16.2374 52.8449 16.6987 52.6912 17.0755 52.3143L23.8968 45.4912L18.6993 42.8924L15.7818 52.8487ZM51.0655 0.766784L4.12866 23.8612C-0.0450884 25.9143 -0.0638385 31.8599 4.09679 33.9393L13.2655 38.5237L48.4949 4.10616C48.8774 3.73303 49.4624 3.66741 49.918 3.94491C50.5293 4.31991 50.6605 5.15241 50.1937 5.69803L19.6312 41.278L36.2062 49.528C39.4799 51.1649 43.4212 49.288 44.2124 45.7143L53.7205 2.85553C54.0655 1.31991 52.4774 0.0730338 51.0655 0.766784Z" fill="#60C0A8"/>
                </svg>`
            }).then((result) => {
                if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                    j = 1;

                    if (!rentButton.classList.contains('off')) {
                        rentButton.classList.add('off')
                    }
                    if (cancelRentButton && cancelRentButton.classList.contains('off')) {
                        cancelRentButton.classList.remove('off')
                    }
                    if (exchangeButton && !exchangeButton.classList.contains('off')) {
                        exchangeButton.classList.add('off')
                    }
                    if (buyButton && !buyButton.classList.contains('off')) {
                        buyButton.classList.add('off')
                    }

                    return;
                }
            })

        })
    }

    if (cancelRentButton) {
        cancelRentButton.addEventListener('click', (e) => {
            e.preventDefault();

            Swal.fire({
                text: 'Are you sure you want to cancel the rental process?',
                icon: 'question',
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                    return;
                }
                if (result.isConfirmed) {

                    j = 0;

                    if (rentButton && rentButton.classList.contains('off')) {
                        rentButton.classList.remove('off')
                    }
                    if (!cancelRentButton.classList.contains('off')) {
                        cancelRentButton.classList.add('off')
                    }
                    if (exchangeButton && exchangeButton.classList.contains('off')) {
                        exchangeButton.classList.remove('off')
                    }
                    if (buyButton && buyButton.classList.contains('off')) {
                        buyButton.classList.remove('off')
                    }

                }
            })
        })
    }

    // ---------------------------------------------------------------------------

    if (exchangeButton) {
        exchangeButton.addEventListener('click', (e) => {
            e.preventDefault();

            clearRange();
            unselectCalendar();

            Swal.fire({
                title: "Exchange Dates",
                text: "Select the range of your bookings to start swapping dates.",
                icon: 'info',
                showConfirmButton: true,
                confirmButtonText: 'OK',
                iconHtml: `<svg width="54" height="53" viewBox="0 0 54 53" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.2012 41.2855L14.1543 51.9562C13.9874 51.6824 13.8749 51.3599 13.8749 50.9887V40.5449L38.4187 16.558L17.2012 41.2855ZM15.7818 52.8487C16.2374 52.8449 16.6987 52.6912 17.0755 52.3143L23.8968 45.4912L18.6993 42.8924L15.7818 52.8487ZM51.0655 0.766784L4.12866 23.8612C-0.0450884 25.9143 -0.0638385 31.8599 4.09679 33.9393L13.2655 38.5237L48.4949 4.10616C48.8774 3.73303 49.4624 3.66741 49.918 3.94491C50.5293 4.31991 50.6605 5.15241 50.1937 5.69803L19.6312 41.278L36.2062 49.528C39.4799 51.1649 43.4212 49.288 44.2124 45.7143L53.7205 2.85553C54.0655 1.31991 52.4774 0.0730338 51.0655 0.766784Z" fill="#60C0A8"/>
                </svg>`
            }).then((result) => {
                if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                    k = 1;

                    if (rentButton && !rentButton.classList.contains('off')) {
                        rentButton.classList.add('off')
                    }
                    if (cancelExchangeButton && cancelExchangeButton.classList.contains('off')) {
                        cancelExchangeButton.classList.remove('off')
                    }
                    if (!exchangeButton.classList.contains('off')) {
                        exchangeButton.classList.add('off')
                    }
                    if (buyButton && !buyButton.classList.contains('off')) {
                        buyButton.classList.add('off')
                    }

                    return;
                }
            })

        });
    }

    if (cancelExchangeButton) {
        cancelExchangeButton.addEventListener('click', (e) => {
            e.preventDefault();

            Swal.fire({
                text: 'Are you sure you want to cancel the exchange process?',
                icon: 'question',
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                    return;
                }
                if (result.isConfirmed) {

                    k = 0;
                    l = 0;

                    if (firstExchangeBgEvent) {
                        firstExchangeBgEvent.remove();
                        firstExchangeBgEvent = null;
                    }

                    if (box_body_request) {
                        box_body_request.setAttribute('data-state', 0);
                        box_body_request.querySelector('#from_booking').textContent = ``;
                        box_body_request.querySelector('#to_booking').textContent = ``;
                    }
                    if (rentButton && rentButton.classList.contains('off')) {
                        rentButton.classList.remove('off')
                    }
                    if (!cancelExchangeButton.classList.contains('off')) {
                        cancelExchangeButton.classList.add('off')
                    }
                    if (exchangeButton && exchangeButton.classList.contains('off')) {
                        exchangeButton.classList.remove('off')
                    }
                    if (buyButton && buyButton.classList.contains('off')) {
                        buyButton.classList.remove('off')
                    }

                }
            })
        })
    }

    // --------------------------------------------------------------------------- jcc

    if (buyButton) {
        buyButton.addEventListener('click', (e) => {
            e.preventDefault();
            // jcc
            clearRange();
            unselectCalendar();

            // text: "Pick the rental nights you'd like to purchase from another share. Your request will be sent to the Mojo Sharing admins for approval.",
            Swal.fire({
                title: "Buy Rental Dates",
                text: "Pick the rental nights you'd like to purchase from another share.",
                icon: 'info',
                showConfirmButton: true,
                confirmButtonText: 'OK',
                iconHtml: `<svg width="54" height="53" viewBox="0 0 54 53" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.2012 41.2855L14.1543 51.9562C13.9874 51.6824 13.8749 51.3599 13.8749 50.9887V40.5449L38.4187 16.558L17.2012 41.2855ZM15.7818 52.8487C16.2374 52.8449 16.6987 52.6912 17.0755 52.3143L23.8968 45.4912L18.6993 42.8924L15.7818 52.8487ZM51.0655 0.766784L4.12866 23.8612C-0.0450884 25.9143 -0.0638385 31.8599 4.09679 33.9393L13.2655 38.5237L48.4949 4.10616C48.8774 3.73303 49.4624 3.66741 49.918 3.94491C50.5293 4.31991 50.6605 5.15241 50.1937 5.69803L19.6312 41.278L36.2062 49.528C39.4799 51.1649 43.4212 49.288 44.2124 45.7143L53.7205 2.85553C54.0655 1.31991 52.4774 0.0730338 51.0655 0.766784Z" fill="#60C0A8"/>
                </svg>`
            }).then((result) => {
                if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                    m = 1;

                    if (!buyButton.classList.contains('off')) {
                        buyButton.classList.add('off')
                    }
                    if (cancelBuyButton && cancelBuyButton.classList.contains('off')) {
                        cancelBuyButton.classList.remove('off')
                    }
                    if (rentButton && !rentButton.classList.contains('off')) {
                        rentButton.classList.add('off')
                    }
                    if (exchangeButton && !exchangeButton.classList.contains('off')) {
                        exchangeButton.classList.add('off')
                    }

                    return;
                }
            });
        })
    }

    if (cancelBuyButton) {
        cancelBuyButton.addEventListener('click', (e) => {
            e.preventDefault();

            Swal.fire({
                text: 'Are you sure you want to cancel the purchase process?',
                icon: 'question',
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                    return;
                }
                if (result.isConfirmed) {
                    m = 0;
                    clearRange();
                    unselectCalendar();

                    if (buyButton && buyButton.classList.contains('off')) {
                        buyButton.classList.remove('off')
                    }
                    if (!cancelBuyButton.classList.contains('off')) {
                        cancelBuyButton.classList.add('off')
                    }
                    if (rentButton && rentButton.classList.contains('off')) {
                        rentButton.classList.remove('off')
                    }
                    if (exchangeButton && exchangeButton.classList.contains('off')) {
                        exchangeButton.classList.remove('off')
                    }
                }
            })
        })
    }

    // ---------------------------------------------------------------------------

    function buySelectedDates(info) {
        if (!calendarState.isWithinRentalFromOthers) {
            alertError('You can only buy dates that are listed UP FOR RENTAL by other shares.');
            unselectCalendar();
            return;
        }

        if (calendarState.diffNights < minimumNumberOfNights) {
            alertError('A minimum of 3 consecutive nights must be selected.');
            unselectCalendar();
            return;
        }

        // Front-only mock — no backend call yet.
        Swal.fire({
            title: 'Selection valid',
            text: `You selected ${calendarState.diffNights} night(s) from ${calendarState.strStartDate} to ${calendarState.strEndDate}. Next step (request submission) is not implemented yet.`,
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            clearRange();
            unselectCalendar();
        });
    }

    // ---------------------------------------------------------------------------

    function rentSelectedDates(info) {
        if (calendarState.diffNights < minimumNumberOfNights) {
            alertError('A minimum of 3 consecutive nights must be selected.');
            unselectCalendar();
            return;
        }

        if (!calendarState.isWithinEvent) {
            alertError('Make sure to select dates within your reserved ranges.');
            unselectCalendar();
            return;
        }

        var params = {
            action: 'mojo_panel_rent_period',
            calendar_id: calendarId.value,
            owner_position: ownerShare.value,
            start: calendarState.strStartDate,
            round: parseInt(round.value),
            end: calendarState.strEndDate,
            owner_id: ownerId.value
        };

        console.log(params);

        Swal.fire({
            text: 'Do you want to rent out the property in the selected dates?',
            icon: 'question',
            showConfirmButton: true,
            showCancelButton: true,
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                clearRange();
                unselectCalendar();
                return;
            }

            if (result.isConfirmed) {
                fetch(`${admin_ajax}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(params)
                })
                    .then(res => res.json())
                    .then(resp => {
                        // WP: wp_send_json_success/error => { success: boolean, data: {...} }
                        if (resp && resp.success) {
                            Swal.fire({
                                title: 'Reserved for rent!',
                                text: '',
                                icon: 'success'
                            }).then((r) => {
                                logToPhp('Reserved for rent!', {
                                    code: calendarState.object_result.code ?? null,
                                    start: calendarState.strStartDate ?? null,
                                    end: calendarState.strEndDate ?? null,
                                    calendar_id: calendarId?.value ?? null,
                                    owner_id: ownerId?.value ?? null,
                                    owner_position: ownerPosition?.value ?? null,
                                    round: round?.value ?? null,
                                    selectedDays: selectedDays?.value ?? null,
                                    diffNights: calendarState?.diffNights ?? null
                                });

                                if (r.isConfirmed || r.dismiss === Swal.DismissReason.cancel || r.dismiss === Swal.DismissReason.backdrop || r.dismiss === Swal.DismissReason.esc) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            const msg = (resp && resp.data && resp.data.message)
                                ? resp.data.message
                                : 'There was a problem with the request. Please try again.';
                            alertError(msg);            // <<<<<< aquí verás "You cannot rent days that are on an exchange request."
                            unselectCalendar();
                            clearRange?.();
                        }
                    })
                    .catch(error => {
                        console.error('Error in the request:', error);
                        alertError('There was a problem with the request. Please try again.');
                        unselectCalendar();
                    });
            }
        });
    }

    function exchangeSelectedDatesPart1(info) {

        if (!calendarState.isWithinEvent) {
            alertError('Make sure to select dates within your reserved ranges.');
            unselectCalendar();
            clearRange();
            return;
        }

        first_exchange_dates = {};

        first_exchange_dates = {
            calendarId: calendarId.value,
            startFrom: calendarState.strStartDate,
            endFrom: calendarState.strEndDate,
            ownerFrom: ownerId.value,
            qtyDaysValidation: calendarState.diffNights < minimumNumberOfNights,
            action: 'mojo_panel_save_exchange_request_pre_validation'
        };

        Swal.fire({
            text: 'Are you sure you want to exchange these days?',
            icon: 'question',
            showConfirmButton: true,
            showCancelButton: true,
            cancelButtonText: 'No',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                clearRange();
                unselectCalendar();
                return;
            }

            if (result.isConfirmed) {

                fetch(`${admin_ajax}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(first_exchange_dates)
                })
                    .then(res => res.json())
                    .then(data => {

                        if (data.success) {

                            first_exchange_dates.previous_ampliation = data.data.is_ampliation;

                            // 🔹 1) Pintar el primer rango seleccionado como background
                            // (end es exclusivo, por eso usamos addOneDayString sobre endFrom)
                            const start = first_exchange_dates.startFrom;
                            const end = addOneDayString(first_exchange_dates.endFrom);

                            // si ya había un highlight viejo, lo quitamos
                            if (firstExchangeBgEvent) {
                                firstExchangeBgEvent.remove();
                            }

                            firstExchangeBgEvent = calendarElement.calendar.addEvent({
                                start: start,
                                end: end,
                                allDay: true,
                                display: 'background',       // FullCalendar v5
                                overlap: false,
                                groupId: 'exchange_from',    // opcional
                                classNames: ['exchange-from-highlight'] // opcional para CSS
                            });

                            clearRange();
                            unselectCalendar();
                            k = 0; l = 1;

                            Swal.fire({
                                title: "Now select the days you want",
                                text: "",
                                icon: "success"
                            }).then((result) => {
                                if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {

                                }
                            })

                            if (box_body_request) {
                                box_body_request.setAttribute('data-state', 1);
                                box_body_request.querySelector('#from_booking').textContent = `${formatDMY(first_exchange_dates.startFrom)} - ${formatDMY(first_exchange_dates.endFrom)}`;
                            }

                        } else {
                            alertError(data?.data?.message || 'There was a problem with the reservation. Please try again.');
                            clearRange();
                            unselectCalendar();
                            return;
                        }
                    })
                    .catch(error => {
                        console.error('Error in the request:', error);
                        alertError('There was a problem with the request. Please try again.');
                        clearRange();
                        unselectCalendar();
                        return;
                    });

            }
        });
    }

    function exchangeSelectedDatesPart2(info) {

        if (!calendarState.isWithinEventButIsntOwner) {
            alertError('Make sure to select dates within reserved ranges that are not yours.');
            unselectCalendar();
            return;
        }

        first_exchange_dates.action = 'mojo_panel_save_exchange_request';
        first_exchange_dates.startTo = calendarState.strStartDate;
        first_exchange_dates.endTo = calendarState.strEndDate;
        first_exchange_dates.qtyDaysValidation = calendarState.diffNights < minimumNumberOfNights;

        savingExchangeDates(first_exchange_dates);
    }

    if (requestButton) {
        requestButton.addEventListener('click', (e) => {
            e.preventDefault();

            if (calendarState.diffNights < minimumNumberOfNights) {
                alertError('A minimum of 3 consecutive days must be selected.');
                return;
            }

            var params = {
                action: 'mojo_panel_request_period',
                calendar_id: calendarId.value,
                owner_position: ownerShare.value,
                start: calendarState.strStartDate,
                end: calendarState.strEndDate,
                round: parseInt(round.value),
                owner_id: ownerId.value
            };

            fetch(`${admin_ajax}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(params)
            })
                .then(res => res.json())
                .then(data => {
                    if (data) {

                        Swal.fire({
                            position: "top-end",
                            timer: 5000,
                            timerProgressBar: true,
                            backdrop: false,
                            text: `Your request have been sent to the administrators, it will be responded as soon as possible.`,
                            icon: 'info',
                            showCancelButton: false,
                            showCloseButton: false,
                            showConfirmButton: false,
                            iconHtml: `<svg width="54" height="53" viewBox="0 0 54 53" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.2012 41.2855L14.1543 51.9562C13.9874 51.6824 13.8749 51.3599 13.8749 50.9887V40.5449L38.4187 16.558L17.2012 41.2855ZM15.7818 52.8487C16.2374 52.8449 16.6987 52.6912 17.0755 52.3143L23.8968 45.4912L18.6993 42.8924L15.7818 52.8487ZM51.0655 0.766784L4.12866 23.8612C-0.0450884 25.9143 -0.0638385 31.8599 4.09679 33.9393L13.2655 38.5237L48.4949 4.10616C48.8774 3.73303 49.4624 3.66741 49.918 3.94491C50.5293 4.31991 50.6605 5.15241 50.1937 5.69803L19.6312 41.278L36.2062 49.528C39.4799 51.1649 43.4212 49.288 44.2124 45.7143L53.7205 2.85553C54.0655 1.31991 52.4774 0.0730338 51.0655 0.766784Z" fill="#60C0A8"/>
                            </svg>`
                        }).then((result) => {

                            logToPhp('Your request have been sent to the administrators, it will be responded as soon as possible.', {
                                code: calendarState.object_result.code ?? null,
                                start: calendarState.strStartDate ?? null,
                                end: calendarState.strEndDate ?? null,
                                calendar_id: calendarId?.value ?? null,
                                owner_id: ownerId?.value ?? null,
                                owner_position: ownerPosition?.value ?? null,
                                round: round?.value ?? null,
                                selectedDays: selectedDays?.value ?? null,
                                diffNights: calendarState?.diffNights ?? null
                            });

                            if (result.dismiss === Swal.DismissReason.timer || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                                return;
                            }
                        })

                    }
                })
                .catch(error => {
                    console.error('Error in the request:', error);
                    alertError('There was a problem with the request. Please try again.');
                });

        })
    }

    let delete_buttons_booked_date = document.querySelectorAll('.delete_booked_date');
    if (delete_buttons_booked_date) {
        Array.from(delete_buttons_booked_date).forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                let id = e.currentTarget.getAttribute('data-id');

                Swal.fire({
                    text: 'Are you sure you want to remove these dates?',
                    icon: 'question',
                    showConfirmButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Yes'
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                        return;
                    }
                    if (result.isConfirmed) {

                        var params = {
                            action: 'mojo_panel_delete_booked_date',
                            booked_date_id: id,
                        };

                        fetch(`${admin_ajax}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams(params)
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: "Reservation removed!",
                                        text: "",
                                        icon: "success"
                                    }).then((result) => {

                                        logToPhp('Reservation removed!', params);

                                        if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                                            window.location.reload();
                                        }
                                    })
                                } else {
                                    logToPhp('There was an error deleting the reserved date. Please try again.', params);
                                    alertError('There was an error deleting the reserved date. Please try again.');
                                }
                            })
                            .catch(error => {

                                logToPhp('Error in the request:', {
                                    code: calendarState.object_result.code ?? null,
                                    start: calendarState.strStartDate ?? null,
                                    end: calendarState.strEndDate ?? null,
                                    calendar_id: calendarId?.value ?? null,
                                    owner_id: ownerId?.value ?? null,
                                    owner_position: ownerPosition?.value ?? null,
                                    round: round?.value ?? null,
                                    selectedDays: selectedDays?.value ?? null,
                                    diffNights: calendarState?.diffNights ?? null
                                });

                                console.error('Error in the request:', error);
                                alertError('There was a problem with the request. Please try again.');
                            });

                    }
                })

            })
        })
    }

    let requestButtons = document.querySelectorAll('.request-button');
    if (requestButtons) {
        Array.from(requestButtons).forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();

                let item = e.currentTarget,
                    status = item.getAttribute('data-action'),
                    li = item.closest('li');

                let id, requestor_dates, recipient_dates, from_owner, to_owner;
                id = li.getAttribute('data-id');

                if (li && status == 'approved') {
                    requestor_dates = li.querySelector('[name="requestor_dates"]');
                    recipient_dates = li.querySelector('[name="recipient_dates"]');
                    from_owner = li.querySelector('[name="from_owner"]');
                    to_owner = li.querySelector('[name="to_owner"]');
                }

                const messages = {
                    canceled: 'Are you sure you want to cancel this request?',
                    approved: 'Are you sure you want to approve this request?',
                    rejected: 'Are you sure you want to reject this request?'
                };

                Swal.fire({
                    text: messages[status],
                    icon: 'question',
                    showConfirmButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Yes'
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                        return;
                    }
                    if (result.isConfirmed) {

                        let params = {
                            action: 'mojo_panel_change_request_status',
                            id_request: id,
                            status,
                            id_calendar: calendarId.value,
                            ...(status === 'approved' && {
                                requestor_dates: requestor_dates.value,
                                recipient_dates: recipient_dates.value,
                                from_owner: from_owner.value,
                                to_owner: to_owner.value,
                            })
                        };

                        fetch(`${admin_ajax}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams(params)
                        })
                            .then(res => res.json())
                            .then(data => {

                                console.log(data)

                                if (data.success) {

                                    Swal.fire({
                                        title: "Request Updated!",
                                        text: "",
                                        icon: "success"
                                    }).then((result) => {
                                        if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                                            window.location.reload();
                                        }
                                    })

                                } else {
                                    alertError(data?.data?.message || 'There was a problem with the reservation. Please try again.');
                                }
                            })
                            .catch(error => {
                                console.error('Error in the request:', error);
                                alertError('There was a problem with the request. Please try again.');
                            });

                    }
                })

            })
        })
    }

    // ---------------------------------------------------------------------------
    // ---------------------------------------------------------------------------
    // ---------------------------------------------------------------------------

    // Make sure only the exchange reservations button appears.
    function unselectCalendar() {
        if (calendarElement.calendar) {
            calendarElement.calendar.unselect();
        }
    }

    function savingExchangeDates(first_exchange_dates) {

        Swal.fire({
            text: `Are you sure you want to send your request to exchange this dates?`,
            icon: 'question',
            showConfirmButton: true,
            showCancelButton: true,
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isDenied || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {

                if (box_body_request) {
                    box_body_request.setAttribute('data-state', 1);
                    box_body_request.querySelector('#to_booking').textContent = ``;
                }

                unselectCalendar();
                clearRange();

                return;
            }
            if (result.isConfirmed) {

                fetch(`${admin_ajax}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(first_exchange_dates)
                })
                    .then(res => res.json())
                    .then(data => {

                        console.log(data)

                        if (data.success) {

                            Swal.fire({
                                title: "Request sent!",
                                text: "",
                                icon: "success"
                            }).then((result) => {
                                if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                                    window.location.reload();
                                }
                            })

                        } else {
                            alertError(data?.data?.message || 'There was a problem with the reservation. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error in the request:', error);
                        alertError('There was a problem with the request. Please try again.');
                    });

            }
        })

    }

    function deleteBookedDatesByClick(obj) {
        let id_owner = obj.id_owner ?? 0,
            owner_position = obj.owner_position ?? 0,
            in_round = obj.in_round ?? 0,
            id_period = obj.id_period ?? 0;

        if ((ownerId.value != id_owner) || (owner_position != ownerPosition.value) || (in_round != round.value)) {
            return;
        }

        if (id_owner && owner_position && id_period) {
            Swal.fire({
                text: 'Are you sure you want to remove these dates?',
                icon: 'question',
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                    if (countSelectedCells && selectedDays && maxDays) {
                        countSelectedCells.innerHTML = `${selectedDays.value}/${maxDays.value}`;
                    }
                    return;
                }
                if (result.isConfirmed) {

                    var params = {
                        action: 'mojo_panel_remove_period',
                        id_period: id_period,
                    };

                    fetch(`${admin_ajax}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams(params)
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: "Reservation removed!",
                                    text: "",
                                    icon: "success"
                                }).then((result) => {
                                    if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.backdrop || result.dismiss === Swal.DismissReason.esc) {
                                        window.location.reload();
                                    }
                                })
                            } else {
                                alertError(data?.data?.message || 'There was a problem with the reservation. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Error in the request:', error);
                            alertError('There was a problem with the request. Please try again.');
                        });

                }
            })
        }
    }

    function logToPhp(message, context = {}) {
        // usa tu variable ya definida:
        const params = {
            action: 'mojo_panel_jslog',
            message,
            context: JSON.stringify(context)
        };
        fetch(admin_ajax, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params)
        }).catch(() => { }); // el log no debe romper el flujo
    }

});

