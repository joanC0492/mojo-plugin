// assets/js/shared/calendar-config.js

/**
 * Suma un día a una fecha en formato YYYY-MM-DD
 */
export function addOneDayString(fechaString) {
    const [year, month, day] = fechaString.split('-').map(Number);
    const fecha = new Date(year, month - 1, day);
    fecha.setDate(fecha.getDate() + 1);
    return formatDate(fecha);
}

/**
 * Resta un día a una fecha en formato YYYY-MM-DD
 */
export function subtractOneDayString(fechaString) {
    const [year, month, day] = fechaString.split('-').map(Number);
    const fecha = new Date(year, month - 1, day);
    fecha.setDate(fecha.getDate() - 1);
    return formatDate(fecha);
}

// "2026-01-09T00:00:00" -> "2026-01-09"
export function toYMD(isoLikeString) {
    // Soporta "YYYY-MM-DDTHH:mm:ss" o "YYYY-MM-DD HH:mm:ss"
    const m = /^(\d{4}-\d{2}-\d{2})/.exec(String(isoLikeString));
    return m ? m[1] : '';
}

/**
 * Convierte una fecha JS a formato YYYY-MM-DD
 */
function formatDate(date) {
    const nuevoYear = date.getFullYear();
    const nuevoMonth = String(date.getMonth() + 1).padStart(2, '0');
    const nuevoDay = String(date.getDate()).padStart(2, '0');
    return `${nuevoYear}-${nuevoMonth}-${nuevoDay}`;
}

// Variables obtenidas del DOM
export const startValidRange = document.getElementById('season_first_date')?.value || null;
const endValidRangeRaw = document.getElementById('season_last_date')?.value || null;
export const endValidRange = endValidRangeRaw ? addOneDayString(endValidRangeRaw) : null;

export const last_month_to_show = (() => {
    const el = document.querySelector('[name="last_month_to_show"]');
    const n = Number(el?.value?.trim());
    return Number.isFinite(n) ? n - 1 : 0;
})();

const today = new Date();
const nextYear = today.getFullYear() + 1;
const currentMonth = today.getMonth();
export const initialDate = new Date(nextYear, last_month_to_show, 1);

export const calendarEl = document.getElementById('calendar'),
    countSelectedCells = document.querySelector('#countSelectedCells'),
    bookButton = document.querySelector('[name="book"]'),
    rentButton = document.querySelector('[name="rent"]'),
    requestButton = document.querySelector('[name="request"]');

export const maxDays = document.querySelector('[name="max_days"]'),
    selectedDays = document.querySelector('[name="selected_days"]'),
    calendarId = document.querySelector('[name="calendar_id"]'),
    round = document.querySelector('[name="round"]'),
    qtyShares = document.querySelector('[name="qty_shares"]'),
    ownerShare = document.querySelector('[name="owner_share"]'),
    ownerPosition = document.querySelector('[name="owner_position"]'),
    ownerId = document.querySelector('[name="owner_id"]');

export const minimumNumberOfNights = 3, minimumNumberOfNightsInHigh = 7, minimumNumberOfNightsIn14Day = 14;

export const calendarState = {
    diffDays: 0,
    diffNights: 0,
    strStartDate: null,
    strEndDate: null,
    selectedStartDate: null,
    selectedEndDate: null,
    leavesGap: false,
    withoutAnySpace: false,
    reservaAntes: false,
    isValidHigh: true,
    isValidLow: true,
    isValidMiddle: true,
    isValid14Day: true,
    highLeavesShortGap: false,
    isInvalidSelection: false,
    forbidLowMidAfterHigh: false,
};

export const calendarElement = {
    calendar: null
};

export function applyLastDayStyling(info) {
    const event = info.event;
    const el = info.el;

    const start = new Date(event.start);
    const end = new Date(event.end ?? event.start);
};

export function alertError(message = '', position = "top-end", timer = 3000) {
    if (countSelectedCells) {
        countSelectedCells.innerHTML = `${selectedDays.value}/${maxDays.value}`;
    }
    if (calendarElement.calendar) {
        calendarElement.calendar.unselect();
    }

    Swal.fire({
        position: position,
        timer: timer,
        timerProgressBar: true,
        backdrop: false,
        text: `${message}`,
        icon: 'error',
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

function formatAndSortDates(date1, date2) {
    // Convertimos a objetos Date
    let d1 = new Date(date1); // endStr
    let d2 = new Date(date2); // startStr

    // SUMAMOS 1 día a d2 (que representa el verdadero start)
    d2.setDate(d2.getDate() + 1);

    // Ordenamos fechas
    const [start, end] = d1 < d2 ? [d1, d2] : [d2, d1];

    // Función para formatear una fecha a MM/DD/YYYY
    function formatDate(date) {
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Los meses son 0-indexados
        const day = String(date.getDate()).padStart(2, '0');
        const year = date.getFullYear();
        return `${month}/${day}/${year}`;
    }

    // Devolvemos el string final
    return `${formatDate(start)} - ${formatDate(end)}`;
}

const isOverlap = (s1, e1, s2, e2) => !(e2 < s1 || s2 > e1);
const isDateInRange = (date, start, end) => date >= start && date < end;

function countAllNightsBySeason(startDate, endDate, seasonMap) {
    const result = {
        low: 0,
        middle: 0,
        high: 0,
        day14: 0
    };

    const start = new Date(startDate);
    const end = new Date(endDate);
    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);

    const totalNights = Math.floor((end - start) / (1000 * 60 * 60 * 24));

    for (let i = 0; i < totalNights; i++) {
        const nightStart = new Date(start);
        nightStart.setDate(start.getDate() + i);

        const nextNight = new Date(nightStart);
        nextNight.setDate(nightStart.getDate() + 1);

        // --- Verificamos si es High o 14Day ---
        const isHigh = isDateInSeason(nightStart, seasonMap.high);
        const is14Day = isDateInSeason(nightStart, seasonMap.day14);

        if (isHigh || is14Day) {
            const nextIsHigh = isDateInSeason(nextNight, seasonMap.high);
            const nextIsDay14 = isDateInSeason(nextNight, seasonMap.day14);

            if (nextIsHigh) {
                result.high++;
            } else if (nextIsDay14) {
                result.day14++;
            } else {
                if (isDateInSeason(nextNight, seasonMap.low)) {
                    result.low++;
                }
                if (isDateInSeason(nextNight, seasonMap.middle)) {
                    result.middle++;
                }
                continue;
            }

        } else {
            if (isDateInSeason(nightStart, seasonMap.low)) {
                result.low++;
            } else if (isDateInSeason(nightStart, seasonMap.middle)) {
                result.middle++;
            } else if (isDateInSeason(nightStart, seasonMap.day14)) {
                result.day14++;
            }
        }
    }

    return result;
}

function countAllDaysBySeason(startDate, endDate, seasonMap) {
    const result = {
        low: 0,
        middle: 0,
        high: 0,
        day14: 0
    };

    const start = new Date(startDate);
    const end = new Date(endDate);
    end.setDate(end.getDate() + 1);

    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);

    const totalDays = Math.floor((end - start) / (1000 * 60 * 60 * 24));

    for (let i = 0; i < totalDays; i++) {
        const dayStart = new Date(start);
        dayStart.setDate(start.getDate() + i);

        if (isDateInSeason(dayStart, seasonMap.low)) {
            result.low++;
        } else if (isDateInSeason(dayStart, seasonMap.middle)) {
            result.middle++;
        } else if (isDateInSeason(dayStart, seasonMap.day14)) {
            result.day14++;
        } else if (isDateInSeason(dayStart, seasonMap.high)) {
            result.high++;
        }
    }

    return result;
}

function isDateInSeason(date, seasonEvents) {
    return seasonEvents.some(event => {
        const sStart = new Date(event.start);
        const sEnd = new Date(event.end ?? event.start);
        sStart.setHours(0, 0, 0, 0);
        sEnd.setHours(0, 0, 0, 0);
        sEnd.setDate(sEnd.getDate() + 1);
        return date >= sStart && date < sEnd;
    });
}

function subtractOneDayDateFormat(d) {
    const x = new Date(d);
    x.setDate(x.getDate() - 1);
    return x;
}

export function selectingDatesInCalendar(info) {

    if (document.querySelector('.in_admin')) {
        if (bookButton) {
            bookButton.style.display = 'block';
        }
    }

    calendarState.diffDays = 0;
    calendarState.diffNights = 0;
    calendarState.withoutAnySpace = false;
    calendarState.isValidHigh = true;
    calendarState.isValidLow = true;
    calendarState.isValidMiddle = true;
    calendarState.isValid14Day = true;
    calendarState.highLeavesShortGap = false;

    // console.log(info)

    calendarState.strStartDate = info.startStr;
    calendarState.strEndDate = info.endStr;

    calendarState.selectedStartDate = calendarState.strStartDate + 'T00:00:00';
    calendarState.selectedEndDate = calendarState.strEndDate + 'T00:00:00';

    let startCurrentEvent = new Date(calendarState.selectedStartDate),
        endCurrentEvent = new Date(calendarState.selectedEndDate);
    endCurrentEvent.setDate(endCurrentEvent.getDate() - 1);

    let isWithinEvent, isRentedTheEvent;

    // console.log('startCurrentEvent:'+ startCurrentEvent);
    // console.log('endCurrentEvent:'+ endCurrentEvent);

    // ---------------------- validation to leave no more than 3 gaps --------------------------

    const selectedStart = startCurrentEvent;
    const selectedEnd = endCurrentEvent;

    const reservas = calendarElement.calendar.getEvents().filter(event => {
        return Object.keys(event.extendedProps ?? {}).length > 0;
    });

    calendarState.leavesGap = false;

    // ---------------------------------------------------------------

    // Validation to avoid leaving empty spaces between reservations
    calendarState.reservaAntes = false;
    if (reservas.length > 0) {
        for (const event of reservas) {
            const start = new Date(event.start);
            const end = new Date(event.end ?? event.start);
            // end.setDate(end.getDate() - 1);

            const daysBetweenStart = Math.floor((selectedStart - end) / (1000 * 60 * 60 * 24));
            const daysBetweenEnd = Math.floor((start - selectedEnd) / (1000 * 60 * 60 * 24));

            if (daysBetweenStart == 0 || daysBetweenStart == 1) {
                calendarState.withoutAnySpace = true;
                calendarState.leavesGap = true;
                break;
            }

            if (daysBetweenEnd == 1 || daysBetweenEnd == 2) {
                calendarState.leavesGap = true;
                break;
            }

            if (end < selectedStart) {
                calendarState.reservaAntes = true;
            }
        }
    }

    // ---------------------------------------------------------------

    if (reservas.length > 0) {
        // -----
    } else {
        // -----
    }

    // Validation to avoid leaving an empty space, starting from the first day of the calendar or ending from the last day of the calendar.
    let firstDayInCalendar = new Date(startValidRange + 'T00:00:00');
    let lastDayInCalendar = new Date(endValidRange + 'T00:00:00');

    let gapWithCalendarStart = Math.floor((selectedStart - firstDayInCalendar) / (1000 * 60 * 60 * 24));
    let gapWithCalendarEnd = Math.floor((lastDayInCalendar - selectedEnd) / (1000 * 60 * 60 * 24));

    if (gapWithCalendarStart == 1 || gapWithCalendarStart == 2 || gapWithCalendarEnd == 2 || gapWithCalendarEnd == 3) {
        calendarState.leavesGap = true;
    }

    // -----------------------------------------------------------------------------------------

    const ifThereIsAnEventValidation = calendarElement.calendar.getEvents().filter(event => Object.keys(event.extendedProps ?? {}).length > 0);

    const ifTheEventIsLowSeasonValidation = calendarElement.calendar.getEvents().filter(event => {
        return event.display == 'background' && event.backgroundColor == '#FFE0E0';
    });
    const ifTheEventIsMiddleSeasonValidation = calendarElement.calendar.getEvents().filter(event => {
        return event.display == 'background' && event.backgroundColor == '#FFF0D9';
    });
    const ifTheEventIsHighSeasonValidation = calendarElement.calendar.getEvents().filter(event => {
        return event.display == 'background' && event.backgroundColor == '#D7E8FA';
    });
    const ifTheEventIs14_DaySeasonValidation = calendarElement.calendar.getEvents().filter(event => {
        return event.display == 'background' && event.backgroundColor == '#D4E5F7';
    });

    const seasonCounts = countAllNightsBySeason(startCurrentEvent, endCurrentEvent, {
        low: ifTheEventIsLowSeasonValidation,
        middle: ifTheEventIsMiddleSeasonValidation,
        high: ifTheEventIsHighSeasonValidation,
        day14: ifTheEventIs14_DaySeasonValidation
    });

    const seasonCountsByDays = countAllDaysBySeason(startCurrentEvent, endCurrentEvent, {
        low: ifTheEventIsLowSeasonValidation,
        middle: ifTheEventIsMiddleSeasonValidation,
        high: ifTheEventIsHighSeasonValidation,
        day14: ifTheEventIs14_DaySeasonValidation
    });

    /*console.table({
        'Nights in Low': seasonCounts.low,
        'Nights in Middle': seasonCounts.middle,
        'Nights in High': seasonCounts.high,
        'Nights in 14-Day': seasonCounts.day14
    });

    console.table({
        'Days in Low': seasonCountsByDays.low,
        'Days in Middle': seasonCountsByDays.middle,
        'Days in High': seasonCountsByDays.high,
        'Days in 14-Day': seasonCountsByDays.day14
    });*/

    // ---- validar huecos cortos SOLO en días High LIBRES, antes y después de la selección ----
    /*(function computeFreeHighGapsAtEdges() {
        // no aplica esta regla para 8 shares
        if (parseInt(qtyShares?.value || 0, 10) === 8) {
            calendarState.highLeavesShortGap = false;
            return;
        }

        const isHighDay = d => isDateInSeason(d, ifTheEventIsHighSeasonValidation);

        // ¿el día está ya reservado por algún evento (de cualquier owner)?
        const isBookedDay = d => {
            return ifThereIsAnEventValidation.some(ev => {
                const s = new Date(ev.start); s.setHours(0, 0, 0, 0);
                const e = new Date(ev.end ?? ev.start); e.setHours(0, 0, 0, 0); // fin exclusivo
                return d >= s && d < e;
            });
        };

        // libres (no reservados) ANTES del inicio seleccionado, dentro de High
        let freeHighBefore = 0;
        const b = new Date(startCurrentEvent);
        b.setHours(0, 0, 0, 0);
        b.setDate(b.getDate() - 1); // día inmediatamente anterior a tu inicio

        while (isHighDay(b) && !isBookedDay(b)) {
            freeHighBefore++;
            b.setDate(b.getDate() - 1);
        }

        // libres (no reservados) DESPUÉS del fin seleccionado, dentro de High
        let freeHighAfter = 0;
        const a = new Date(endCurrentEvent);
        a.setHours(0, 0, 0, 0);
        a.setDate(a.getDate() + 1); // primer día libre tras tu fin

        while (isHighDay(a) && !isBookedDay(a)) {
            freeHighAfter++;
            a.setDate(a.getDate() + 1);
        }

        // si dejas 1..6 noches libres High en cualquiera de los bordes => inválido
        calendarState.highLeavesShortGap =
            (freeHighBefore > 0 && freeHighBefore < minimumNumberOfNightsInHigh) ||
            (freeHighAfter > 0 && freeHighAfter < minimumNumberOfNightsInHigh);

        // Debug
        // console.log({ freeHighBefore, freeHighAfter, highLeavesShortGap: calendarState.highLeavesShortGap });
    })();*/

    // --- Regla: no permitir selección Low/Middle pegada a un día High/14-Day LIBRE
    /*(function forbidLowMiddleAdjToFreeHighOr14Day() {
        // no aplica esta regla para 8 shares
        if (parseInt(qtyShares?.value || 0, 10) === 8) {
            calendarState.forbidLowMidAfterHigh = false;
            return;
        }

        // Sólo nos interesa cuando la selección NO incluye High ni 14-Day,
        // pero SÍ incluye Low o Middle.
        const onlyLowMiddle =
            seasonCountsByDays.high === 0 &&
            seasonCountsByDays.day14 === 0 &&
            (seasonCountsByDays.low > 0 || seasonCountsByDays.middle > 0);

        if (!onlyLowMiddle) {
            calendarState.forbidLowMidAfterHigh = false;
            return;
        }

        const isHighDay = (d) => isDateInSeason(d, ifTheEventIsHighSeasonValidation);
        const is14DayDay = (d) => isDateInSeason(d, ifTheEventIs14_DaySeasonValidation);

        // ¿el día está ya reservado por algún evento (de cualquier owner)?
        const isBookedDay = (d) => {
            return calendarElement.calendar
                .getEvents()
                .filter(ev => Object.keys(ev.extendedProps ?? {}).length > 0) // sólo bookings, no “background”
                .some(ev => {
                    const s = new Date(ev.start); s.setHours(0, 0, 0, 0);
                    const e = new Date(ev.end ?? ev.start); e.setHours(0, 0, 0, 0); // fin exclusivo
                    return d >= s && d < e;
                });
        };

        // Día inmediatamente ANTERIOR al primer día seleccionado
        const prev = new Date(startCurrentEvent);
        prev.setHours(0, 0, 0, 0);
        prev.setDate(prev.getDate() - 1);

        const prevIsHighOr14 = isHighDay(prev) || is14DayDay(prev);
        const prevIsBooked = isBookedDay(prev);
        const forbidByPrev = prevIsHighOr14 && !prevIsBooked; // deja hueco no reservable al inicio

        // Día inmediatamente POSTERIOR al último día seleccionado
        const next = new Date(endCurrentEvent);
        next.setHours(0, 0, 0, 0);
        next.setDate(next.getDate() + 1);

        const nextIsHighOr14 = isHighDay(next) || is14DayDay(next);
        const nextIsBooked = isBookedDay(next);
        const forbidByNext = nextIsHighOr14 && !nextIsBooked; // deja hueco no reservable al final

        calendarState.forbidLowMidAfterHigh = !!(forbidByPrev || forbidByNext);

        // Debug
        // console.log('forbidLowMidAfterHigh:', calendarState.forbidLowMidAfterHigh, { prevIsHighOr14, prevIsBooked, nextIsHighOr14, nextIsBooked });
    })();*/

    // ------------------------------------------------

    const isOverlappingEvent = ifThereIsAnEventValidation.some(event => {
        const eventStart = new Date(event.start);
        const eventEnd = new Date(event.end ?? event.start);
        return !(endCurrentEvent <= eventStart || startCurrentEvent >= eventEnd);
    });

    if (document.querySelector('.in_panel')) {
        const ifTheEventIsFromTheOwnerOnTurnValidation = calendarElement.calendar.getEvents().filter(event => {
            const props = event.extendedProps ?? {};
            return (
                Object.keys(props).length > 0 &&
                props.id_owner == ownerId.value
            );
        });
        const ifTheEventIsRentedAndFromTheOwnerOnTurnValidation = calendarElement.calendar.getEvents().filter(event => {
            const props = event.extendedProps ?? {};
            return (
                Object.keys(props).length > 0 &&
                props.id_owner == ownerId.value &&
                props.owner_position == ownerShare.value &&
                event.backgroundColor == '#CCCCCC'
            );
        });

        isWithinEvent = ifTheEventIsFromTheOwnerOnTurnValidation.some(event => {
            const eventStart = new Date(event.start);
            const eventEnd = new Date(event.end ?? event.start); // Si no tiene end, usa start
            return startCurrentEvent >= eventStart && endCurrentEvent <= eventEnd;
        });
        isRentedTheEvent = ifTheEventIsRentedAndFromTheOwnerOnTurnValidation.some(event => {
            const eventStart = new Date(event.start);
            const eventEnd = new Date(event.end ?? event.start); // Si no tiene end, usa start
            return startCurrentEvent >= eventStart && endCurrentEvent <= eventEnd;
        });
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Calcular la diferencia en días
    const diffTime = Math.abs(info.start - info.end);
    calendarState.diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    calendarState.diffNights = Math.max(Math.ceil(diffTime / (1000 * 60 * 60 * 24)) - 1, 0);

    if (countSelectedCells) {

        // Nueva validación: solo extremos de eventos o días libres
        calendarState.isInvalidSelection = false;

        const rangeDates = [];
        let currentDate = new Date(startCurrentEvent);
        while (currentDate < endCurrentEvent) {
            rangeDates.push(new Date(currentDate)); // clonar fecha
            currentDate.setDate(currentDate.getDate() + 1);
        }

        for (const date of rangeDates) {
            const dateTime = date.getTime();

            for (const event of ifThereIsAnEventValidation) {
                const eventStart = new Date(event.start);
                eventStart.setDate(eventStart.getDate() - 1);

                const eventEndExclusive = new Date(event.end ?? event.start);
                const eventEndInclusive = new Date(eventEndExclusive);
                eventEndInclusive.setDate(eventEndInclusive.getDate() - 1);

                const eventStartTime = eventStart.getTime();
                const eventEndTime = eventEndInclusive.getTime();

                if (dateTime >= eventStartTime && dateTime <= eventEndTime) {
                    // este día está dentro del evento
                    const isStart = dateTime === eventStartTime;
                    const isEnd = dateTime === eventEndTime;

                    if (!isStart && !isEnd) {
                        // está en medio del evento
                        calendarState.isInvalidSelection = true;
                        break;
                    }
                }
            }

            if (calendarState.isInvalidSelection) break;
        }

        if (calendarState.withoutAnySpace && calendarState.diffNights != 0) {
            countSelectedCells.innerHTML = `${parseInt(selectedDays.value) + calendarState.diffNights + 1}/${maxDays.value}`;
        } else {
            countSelectedCells.innerHTML = `${parseInt(selectedDays.value) + calendarState.diffNights}/${maxDays.value}`;
        }

    } else {

        if (document.querySelector('.in_panel')) {
            if (isWithinEvent) {
                console.log('✅ The selected range is within a valid event.');

                if (isRentedTheEvent) {
                    if (requestButton && requestButton.classList.contains('off')) {
                        requestButton.classList.remove('off')
                    }
                    if (rentButton && !rentButton.classList.contains('off')) {
                        rentButton.classList.add('off')
                    }
                } else {
                    if (rentButton && rentButton.classList.contains('off')) {
                        rentButton.classList.remove('off')
                    }
                    if (requestButton && !requestButton.classList.contains('off')) {
                        requestButton.classList.add('off')
                    }
                }
            } else {
                console.log('❌ The selected range is NOT allowed.');

                if (rentButton && !rentButton.classList.contains('off')) {
                    rentButton.classList.add('off')
                }
                if (requestButton && !requestButton.classList.contains('off')) {
                    requestButton.classList.add('off')
                }
            }
        }

    }

    // ----------------------------------------------------------------------------------------------------------------

    if (parseInt(qtyShares?.value || 0, 10) !== 8) {
        if (seasonCounts.low > 0) {
            calendarState.isValidLow = seasonCounts.low >= minimumNumberOfNights; //minimo 3 dias o minimo 2 noches
        }
        if (seasonCounts.middle > 0) {
            calendarState.isValidMiddle = seasonCounts.middle >= minimumNumberOfNights; //minimo 3 dias o minimo 2 noches
        }
    }
    if (round.value != 6 && (parseInt(qtyShares?.value || 0, 10) === 8 && round.value != 5)) {
        if (seasonCounts.high > 0) {
            calendarState.isValidHigh = seasonCounts.high >= minimumNumberOfNightsInHigh; //minimo 7 noches
        }
    }
    if (parseInt(qtyShares?.value || 0, 10) !== 8 && round.value != 6) {
        if (seasonCounts.day14 > 0) {
            calendarState.isValid14Day = seasonCounts.day14 >= minimumNumberOfNightsIn14Day; //minimo 15 dias o 14 noches
        }
    }

}

export function removeEventBooked(info) {

}