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
    selectingDatesInCalendar,
    toYMD,
    LS_DATE1_KEY,
    LS_DATE2_KEY,
    LS_TOGGLE_KEY,
} = await import(url);

jQuery(document).ready(function ($) {

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

    const bookingPopup = document.querySelector('#booking'),
        reassigningPopup = document.querySelector('#reassigning'),
        sortOwnersPopup = document.querySelector('#sort_owners');

    const close_popup = document.querySelectorAll('.close_popup');
    if (close_popup) {
        Array.from(close_popup).forEach(close => {
            close.addEventListener('click', (e) => {
                e.preventDefault();

                Array.from(document.querySelectorAll('.popup')).forEach(popup => {
                    popup.querySelector('form').reset();
                    if (popup.classList.contains('active')) {
                        popup.classList.remove('active')
                    }
                })
            })
        })
    }

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
            start: 'title',
            center: '',
            end: 'prev,next',
        },
        firstDay: 1,
        // selectMinDistance: 4,
        selectable: true,
        unselectAuto: false,
        selectMirror: false,
        events: events,
        dayCellContent: window.renderCommentsInDayCell,
        eventDidMount(info) {
            applyLastDayStyling(info);

            requestAnimationFrame(() => {
                window.adjustCommentsPosition?.();
            });
        },
        eventClick: function (info) {
            var eventObj = info.event;

            console.log(eventObj.extendedProps);

            if (eventObj && !reassigningPopup.classList.contains('active')) {
                reassigningPopup.classList.add('active');

                let startDate = eventObj.startStr,
                    endDate = subtractOneDayString(eventObj.endStr);

                let id_owner = eventObj.extendedProps.id_owner ?? 0,
                    owner_position = eventObj.extendedProps.owner_position ?? 0,
                    id_period = eventObj.extendedProps.id_period ?? 0,
                    use = eventObj.extendedProps.use ?? '';

                reassigningPopup.querySelector('[name="start_date_r"]').value = startDate
                reassigningPopup.querySelector('[name="end_date_r"]').value = endDate
                reassigningPopup.querySelector('[name="id_booking_r"]').value = id_period
                reassigningPopup.querySelector(`#use_selected_r option[value="${use}"]`).selected = true;
                reassigningPopup.querySelector(`#owner_selected_r option[value="${owner_position} - ${id_owner}"]`).selected = true;
            }
        },
        select: selectingDatesInCalendar,
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
                return;
            }

            if (d1 && tg === 'on') {
                // hay primer click "pendiente"
                calendarElement.calendar.select({ start: d1, end: addOneDayString(d1), allDay: true });
            }

        },
        unselect: function () {
            if (bookButton) {
                bookButton.style.display = 'none';
            }
        },
        dateClick: blockingDates,
    });
    calendarElement.calendar.render();

    // ---------------------------------------------------------------------------
    // ---------------------------------------------------------------------------
    // ---------------------------------------------------------------------------

    if (bookButton) {
        bookButton.addEventListener('click', (e) => {
            e.preventDefault();

            let fullSelectedDays = parseInt(selectedDays.value) + calendarState.diffNights;
            if (fullSelectedDays > maxDays.value) {
                alertError('You must not select more days than the maximum allocated for this turn.');
                return;
            }

            console.log(calendarState.object_result.code);
            if (!calendarState.object_result.allowed) {
                alertError(calendarState.object_result.message)
                return;
            }

            /*if (calendarState.diffNights < minimumNumberOfNights) {
                alertError('A minimum of 3 consecutive days must be selected.', 'center');
                return;
            }
            if (fullSelectedDays == 0) {
                alertError('Make sure to select your respective days on the interactive calendar');
                return;
            }
            if (calendarState.leavesGap) {
                //let gapMsg = calendarState.reservaAntes ? 'Your selection leaves a gap of less than 3 days with another reservation.' : 'Your selection leaves a gap of less than 3 days with the start of the calendar.';
                alertError('Your selection creates a gap of less than 3 days, which is not allowed.');
                return;
            }*/

            if (calendarState.isInvalidSelection) {
                console.log('❌ Cannot select middle days of an event.');
                alertError('Some of the selected dates are taken.');
                return;
            } else {
                console.log('✅ The selected range is valid.');
            }

            /*if (parseInt(qtyShares?.value || 0, 10) !== 8) {
                if (!calendarState.isValidLow) {
                    alertError('A minimum of 3 consecutive days in Low Season must be selected.');
                    return;
                }
                if (!calendarState.isValidMiddle) {
                    alertError('A minimum of 3 consecutive days in Middle Season must be selected.');
                    return;
                }
            }
            if (round.value != 6 && (parseInt(qtyShares?.value || 0, 10) === 8 && round.value != 5)) {
                if (!calendarState.isValidHigh) {
                    alertError('A minimum of 7 consecutive days in High Season must be selected.');
                    return;
                }
            }
            if (parseInt(qtyShares?.value || 0, 10) !== 8 && round.value != 6) {
                if (!calendarState.isValid14Day) {
                    alertError('A minimum of 14 consecutive days in 14-Day Season must be selected.');
                    return;
                }
            }*/

            // ---------------------------------------------------------------------------

            /*if (calendarState.withoutAnySpace) {
                fullSelectedDays += 1;
            }*/

            if (fullSelectedDays == parseInt(maxDays.value) - 2 || fullSelectedDays == parseInt(maxDays.value) - 1) {
                alertError('Either book all your available nights or keep at least 3 nights free');
                return;
            }

            // ------------------------------------------------------------------------

            /*if (calendarState.forbidLowMidAfterHigh) {
                alertError('You cannot start or end a Low/Middle selection immediately next to a free High/14-Day day, as this would leave a non-bookable slot.');
                return;
            }

            if (calendarState.highLeavesShortGap) {
                alertError('In High Season you cannot leave gaps smaller than 7 nights at the start or end of your selection.');
                return;
            }*/

            // ------------------------------------------------------------------------

            if (!bookingPopup.classList.contains('active')) {
                bookingPopup.classList.add('active')
            }

            let startDateEL = bookingPopup.querySelector('[name="start_date"]');
            let endDateEL = bookingPopup.querySelector('[name="end_date"]');

            var endDateValue = toYMD(calendarState.selectedEndDate);

            startDateEL.value = toYMD(calendarState.selectedStartDate);
            endDateEL.value = subtractOneDayString(endDateValue);

        })
    }

    // ---------------------------------------------------------------------------
    // ---------------------------------------------------------------------------
    // ---------------------------------------------------------------------------

    const openCalendarButton = document.querySelector('[data-id="sort_owners"]');
    if (openCalendarButton) {
        openCalendarButton.addEventListener('click', (e) => {
            if (!sortOwnersPopup.classList.contains('active')) {
                sortOwnersPopup.classList.add('active')
            }
        })
    }

    const sortEl = document.querySelector('#sort'),
        inputOrderedOwners = document.querySelector('input[name="ordered_owners"]'),
        inputOrderedColors = document.querySelector('input[name="ordered_colors"]');

    if (sortEl && inputOrderedOwners && inputOrderedColors) {
        new Sortable(sortEl, {
            animation: 150,
            ghostClass: 'blue-background-class',
            onSort: function () {
                let items = sortEl.querySelectorAll('li');
                let orderedOwners = {}, orderedColors = {};

                items.forEach((item, index) => {
                    orderedOwners[(index + 1).toString()] = parseInt(item.getAttribute('data-id'));
                    orderedColors[(index + 1).toString()] = item.getAttribute('data-color');
                });

                inputOrderedOwners.value = JSON.stringify(orderedOwners);
                inputOrderedColors.value = JSON.stringify(orderedColors);
            }
        });
    }

});