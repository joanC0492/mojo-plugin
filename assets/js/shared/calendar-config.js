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

const inputHiddenBlockedDates = document.querySelector('[name="blocked_dates"]');

export const last_month_to_show = (() => {
    const el = document.querySelector('[name="last_month_to_show"]');
    const n = Number(el?.value?.trim());
    return Number.isFinite(n) ? n - 1 : 0;
})();

const today = new Date();
const actualYear = today.getFullYear() + 0;
const currentMonth = today.getMonth();
export const initialDate = new Date(actualYear, last_month_to_show, 1);

export const calendarEl = document.getElementById('calendar'),
    countSelectedCells = document.querySelector('#countSelectedCells'),
    bookButton = document.querySelector('[name="book"]'),
    rentButton = document.querySelector('[name="rent"]'),
    cancelRentButton = document.querySelector('[name="cancel_rent"]'),
    cancelExchangeButton = document.querySelector('[name="cancel_exchange"]'),
    requestButton = document.querySelector('[name="request"]'),
    resetExchangeButton = document.querySelector('[name="reset_exchange"]'),
    exchangeButton = document.querySelector('[name="exchange"]'),
    // jcc
    buyButton = document.querySelector('[name="buy"]'),
    cancelBuyButton = document.querySelector('[name="cancel_buy"]');
// jcc
export const purchaseMinNights = document.querySelector('[name="purchase_min_nights"]');

export const admin_ajax = document.querySelector('#mojo-admin_ajax').value;

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
    object_result: null,
    isWithinEvent: null,
    isWithinEventButIsntOwner: null,
    isRentedTheEvent: null,
    // jcc - purchase mode: selection lies inside an UP FOR RENTAL range owned by someone else
    isWithinRentalFromOthers: null,
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

const dateRangeEl = document.getElementById('date_range');

export const LS_DATE1_KEY = `mojo_date1_${calendarId?.value || 'default'}`;
export const LS_DATE2_KEY = `mojo_date2_${calendarId?.value || 'default'}`;
export const LS_TOGGLE_KEY = `mojo_toggle_${calendarId?.value || 'default'}`;

// Normaliza el toggle (default: 'off')
(function ensureToggleInit() {
    if (!localStorage.getItem(LS_TOGGLE_KEY)) {
        localStorage.setItem(LS_TOGGLE_KEY, 'off');
    }
})();

function getToggle() { return localStorage.getItem(LS_TOGGLE_KEY) === 'on' ? 'on' : 'off'; }
function setToggle(v) { localStorage.setItem(LS_TOGGLE_KEY, v === 'on' ? 'on' : 'off'); }

function setDate1(ymd) { localStorage.setItem(LS_DATE1_KEY, ymd); }
function setDate2(ymd) { localStorage.setItem(LS_DATE2_KEY, ymd); }
function getDate1() { return localStorage.getItem(LS_DATE1_KEY); }
function getDate2() { return localStorage.getItem(LS_DATE2_KEY); }
export function clearRange() { localStorage.removeItem(LS_DATE1_KEY); localStorage.removeItem(LS_DATE2_KEY); }

document.addEventListener("DOMContentLoaded", () => {
    clearRange();
});

function minMax(a, b) { return a <= b ? [a, b] : [b, a]; }

export function formatDMY(s /* YYYY-MM-DD */) {
    if (!s) return '';
    const [y, m, d] = s.split('-');
    return `${d}/${m}/${String(y).slice(2)}`;
}

export function setDateRange(startStr, endStr) {
    if (!dateRangeEl) return;
    if (startStr && endStr) {
        dateRangeEl.textContent = `${formatDMY(startStr)} - ${formatDMY(endStr)}`;
    } else if (startStr) {
        dateRangeEl.textContent = `${formatDMY(startStr)} - ${formatDMY(startStr)}`;
    } else {
        dateRangeEl.textContent = '';
    }
}

// --- NUEVO: helper para saber si el rango incluye bloqueadas ---
function hasBlockedInRange(startStr, endStr) {
    if (!Array.isArray(BLOCKED_DATES) || !BLOCKED_DATES.length) return false;
    const blocked = new Set(BLOCKED_DATES);
    const days = expandDateRange(startStr, endStr); // ya existe en este archivo
    return days.some(d => blocked.has(d));
}

export function blockingDates(info) {

    if (countSelectedCells) {
        countSelectedCells.innerHTML = `${selectedDays.value}/${maxDays.value}`;
    }

    // info.dateStr viene de FullCalendar en formato YYYY-MM-DD
    const day = info.dateStr || toYMD(info.date);
    if (!day) return;

    // No permitir empezar/terminar en un día bloqueado
    if (Array.isArray(BLOCKED_DATES) && BLOCKED_DATES.includes(day)) {
        if (calendarElement.calendar) calendarElement.calendar.unselect();
        alertError('This date is blocked.');
        return;
    }

    const toggle = getToggle();

    // === PRIMER CLICK (toggle OFF o no hay fecha1) ===
    if (toggle === 'off' || !getDate1()) {
        setDate1(day);
        localStorage.removeItem(LS_DATE2_KEY);
        setToggle('on'); // esperando segundo click

        // UI
        if (calendarElement.calendar) {
            calendarElement.calendar.unselect();
            calendarElement.calendar.select({ start: day, end: addOneDayString(day), allDay: true });
        }
        setDateRange(day, day);
        return;
    }

    // === SEGUNDO CLICK (toggle ON) ===
    const date1 = getDate1();
    const [startStr, endInclusive] = minMax(date1, day);

    // Si el rango toca bloqueadas -> no cerramos, dejamos el toggle en 'on' para que vuelva a intentar
    if (hasBlockedInRange(startStr, endInclusive)) {
        if (calendarElement.calendar) calendarElement.calendar.unselect();
        alertError('Some of the selected dates are blocked.');
        // No movemos date1 ni el toggle, para que el usuario elija otro fin
        if (calendarElement.calendar) {
            calendarElement.calendar.select({ start: date1, end: addOneDayString(date1), allDay: true });
        }
        setDateRange(date1, date1);
        return;
    }

    // Guardar rango completo y cerrar ciclo
    setDate1(startStr);
    setDate2(endInclusive);
    setToggle('off'); // listo el ciclo

    if (calendarElement.calendar) {
        calendarElement.calendar.select({
            start: startStr,
            end: addOneDayString(endInclusive),
            allDay: true
        });
    }

    setDateRange(startStr, endInclusive);
}

export function alertError(message = '', position = "top-end", timer = 3000) {

    localStorage.removeItem(LS_DATE1_KEY);
    localStorage.removeItem(LS_DATE2_KEY);
    localStorage.setItem(LS_TOGGLE_KEY, 'off');

    if (countSelectedCells) {
        countSelectedCells.innerHTML = `${selectedDays.value}/${maxDays.value}`;
    }
    if (dateRangeEl) {
        dateRangeEl.innerHTML = '';
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

        if (isHigh) {
            const nextIsHigh = isDateInSeason(nextNight, seasonMap.high);

            if (nextIsHigh) {
                result.high++;
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
            }
        }
    }

    return result;
}

function countAllDaysBySeason(startDate, endDate, seasonMap) {
    const result = {
        low: 0,
        middle: 0,
        high: 0
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

function orderReservasAsc(reservas) {
    reservas.sort((a, b) => new Date(a.startStr) - new Date(b.startStr));
}

// ------------------------------------------------------------------
// ------------------------------------------------------------------

function getLastDateReservedBeforeStartDate(reservas, firstCalendarDate, startDate) {
    let lastDateReservedBeforeStartDate = firstCalendarDate;
    if (reservas.length > 0) {
        for (const event of reservas) {
            const start = new Date(event.start);
            const end = new Date(event.end ?? event.start);
            end.setDate(end.getDate() - 1);

            if (end <= startDate) {
                lastDateReservedBeforeStartDate = end;
            } else {
                break;
            }
        }
    }
    return lastDateReservedBeforeStartDate;
}

function getFirstDateReservedAfterEndDate(reservas, lastCalendarDate, endDate) {
    let firstDateReservedAfterEndDate = lastCalendarDate;
    const invertido = reservas.slice().reverse();

    if (reservas.length > 0) {
        for (const event of invertido) {
            const start = new Date(event.start);
            const end = new Date(event.end ?? event.start);
            // end.setDate(end.getDate() - 1);

            if (start >= endDate) {
                firstDateReservedAfterEndDate = start;
            } else {
                break;
            }
        }
    }
    return firstDateReservedAfterEndDate;
}

function areAllTheDatesInTheGapBeforeHigh(reservas, firstCalendarDate, startDate, ifTheEventIsHighSeasonValidation) {
    if (!isDateInSeason(startDate, ifTheEventIsHighSeasonValidation)) return false;

    let end;
    const invertido = reservas.slice().reverse();
    if (invertido.length > 0) {
        for (const event of invertido) {
            end = new Date(event.end ?? event.start);
            if (end <= startDate) {
                let currentDate = new Date(startDate);
                currentDate.setHours(0, 0, 0, 0);
                //currentDate.setDate(currentDate.getDate() + 1);
                while (currentDate > end) {
                    if (isDateInSeason(currentDate, ifTheEventIsHighSeasonValidation)) {
                        currentDate.setDate(currentDate.getDate() - 1);
                    } else {
                        return false;
                    }
                }
                return true;
            }
        }
    }
    // si no hay reservas ó el rango seleccionado es posterior a todas las reservas
    end = firstCalendarDate;
    if (end < startDate) {
        let currentDate = new Date(startDate);
        currentDate.setHours(0, 0, 0, 0);
        //currentDate.setDate(currentDate.getDate() + 1);
        while (currentDate > end) {
            if (isDateInSeason(currentDate, ifTheEventIsHighSeasonValidation)) {
                currentDate.setDate(currentDate.getDate() - 1);
            } else {
                return false;
            }
        }
    }
    return true;
}

function areAllTheDatesInTheGapAfterHigh(reservas, lastCalendarDate, endDate, ifTheEventIsHighSeasonValidation) {
    if (!isDateInSeason(endDate, ifTheEventIsHighSeasonValidation)) return false;

    let start;
    if (reservas.length > 0) {
        for (const event of reservas) {
            start = new Date(event.start);
            if (start >= endDate) {
                let currentDate = new Date(endDate);
                currentDate.setHours(0, 0, 0, 0);
                //currentDate.setDate(currentDate.getDate() + 1);
                while (currentDate < start) {
                    if (isDateInSeason(currentDate, ifTheEventIsHighSeasonValidation)) {
                        currentDate.setDate(currentDate.getDate() + 1);
                    } else {
                        return false;
                    }
                }
                return true;
            }
        }
    }
    // si no hay reservas ó el rango seleccionado es posterior a todas las reservas
    start = lastCalendarDate;
    if (start >= endDate) {
        let currentDate = new Date(endDate);
        currentDate.setHours(0, 0, 0, 0);
        //currentDate.setDate(currentDate.getDate() + 1);
        while (currentDate < start) {
            if (isDateInSeason(currentDate, ifTheEventIsHighSeasonValidation)) {
                currentDate.setDate(currentDate.getDate() + 1);
            } else {
                return false;
            }
        }
    }
    return true;
}

function getFirstHighDateForThatRange(startDate, endDate, firstCalendarDate, isStartDateHigh, isEndDateHigh, ifTheEventIsHighSeasonValidation) {
    let firstHighDate = null;
    let currentDate = null;
    if (isStartDateHigh) {
        currentDate = new Date(startDate);
    } else if (isEndDateHigh) {
        currentDate = new Date(endDate);
    } else {
        return null;
    }
    currentDate.setHours(0, 0, 0, 0);
    while (currentDate >= firstCalendarDate) {
        if (isDateInSeason(currentDate, ifTheEventIsHighSeasonValidation)) {
            firstHighDate = new Date(currentDate);
            firstHighDate.setHours(0, 0, 0, 0);
        } else {
            break;
        }
        currentDate.setDate(currentDate.getDate() - 1);
    }
    return firstHighDate;
}

function getLastHighDateForThatRange(startDate, endDate, lastDateCalendar, isStartDateHigh, isEndDateHigh, ifTheEventIsHighSeasonValidation) {
    let lastHighDate = null;
    let currentDate = null;
    if (isStartDateHigh) {
        currentDate = new Date(startDate);
    } else if (isEndDateHigh) {
        currentDate = new Date(endDate);
    } else {
        return null;
    }
    currentDate.setHours(0, 0, 0, 0);
    while (currentDate <= lastDateCalendar) {
        /*console.log("currentDate: "+currentDate);
        console.log("lastHighDate: "+lastHighDate);
        console.log("isDateInSeason: "+isDateInSeason(currentDate, ifTheEventIsHighSeasonValidation));*/
        if (isDateInSeason(currentDate, ifTheEventIsHighSeasonValidation)) {
            lastHighDate = new Date(currentDate);
            lastHighDate.setHours(0, 0, 0, 0);
        } else {
            break;
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }
    return lastHighDate;
}

// ---------------------------------------------------------------------------------------------------------------

function isEarliestReservation(reservas, startDate) {
    if (reservas.length > 0) {
        for (const event of reservas) {
            let start = new Date(event.start);
            start.setHours(0, 0, 0, 0);
            if (start < startDate) {
                return false;
            } else {
                return true;
            }
        }
    }
    return true;
}

function isLatestReservation(reservas, endDate) {
    if (reservas.length > 0) {
        const invertido = reservas.slice().reverse();
        for (const event of invertido) {
            let end = new Date(event.end);
            end.setHours(0, 0, 0, 0);
            end.setDate(end.getDate() - 1);
            return (endDate >= end);
        }
    }
    return true;
}

function isTheSameOwnerExtending(currentOwnerEvents, startDate, endDate) {
    //console.log("currentOwnerEvents: ");
    //console.log(currentOwnerEvents);
    for (const event of currentOwnerEvents) {
        let end = new Date(event.end);
        let start = new Date(event.start);
        end.setHours(0, 0, 0, 0);
        end.setHours(0, 0, 0, 0);
        end.setDate(end.getDate() - 1);

        if (isSameDay(startDate, end) || isSameDay(endDate, start)) {
            return true;
        }
    }
    return false;
}

// Días entre dos YYYY-MM-DD en UTC.
// Si endUtcStr es EXCLUSIVO (como endStr de FullCalendar), pásalo directo.
function diffDaysUTC(startUtcStr, endUtcStr) {
    const [y1, m1, d1] = startUtcStr.split('-').map(Number);
    const [y2, m2, d2] = endUtcStr.split('-').map(Number);
    const ms = Date.UTC(y2, m2 - 1, d2) - Date.UTC(y1, m1 - 1, d1);
    return Math.round(ms / 86400000); // 24*60*60*1000
}

function fullCalendarInfoToCalendarInfo(calendarElement, info, round, qtyShares) {

    let strStartDate = info.startStr;
    let strEndDate = info.endStr;

    let selectedStartDate = strStartDate + 'T00:00:00';
    let selectedEndDate = strEndDate + 'T00:00:00';

    // startDate, endDate

    let startDate = new Date(selectedStartDate),
        endDate = new Date(selectedEndDate);
    endDate.setDate(endDate.getDate() - 1);

    // nightsAvailable

    let nightsAvailable = 0;
    nightsAvailable = parseInt(maxDays.value) - parseInt(selectedDays.value);

    // allowedNights

    let allowedNights = 0;
    allowedNights = parseInt(maxDays.value);

    // isLastRound

    let isLastRound = round.value == 6 || (parseInt(qtyShares?.value || 0, 10) === 8 && round.value == 5);

    // nightCount

    let diffTime = Math.abs(info.start - info.end);
    // let nightCount = Math.max(Math.ceil(diffTime / (1000 * 60 * 60 * 24)) - 1, 0);
    let nightCount = Math.max(diffDaysUTC(info.startStr, info.endStr) - 1, 0);

    // gapBefore, gapAfter

    let reservas = calendarElement.calendar.getEvents().filter(event => {
        return Object.keys(event.extendedProps ?? {}).length > 0;
    });
    orderReservasAsc(reservas);
    //console.log("reservas: ");
    //console.log(reservas);

    let firstCalendarDate = new Date(startValidRange + 'T00:00:00');
    let lastCalendarDate = new Date(subtractOneDayString(endValidRange) + 'T00:00:00');

    let lastDateReservedBeforeStartDate = getLastDateReservedBeforeStartDate(reservas, firstCalendarDate, startDate);
    let firstDateReservedAfterEndDate = getFirstDateReservedAfterEndDate(reservas, lastCalendarDate, endDate);

    //console.log("firstDateReservedAfterEndDate: " + firstDateReservedAfterEndDate);
    //console.log("lastDateReservedBeforeStartDate: " + lastDateReservedBeforeStartDate);

    let gapBefore = Math.floor((startDate - lastDateReservedBeforeStartDate) / (1000 * 60 * 60 * 24));
    let gapAfter = Math.floor((firstDateReservedAfterEndDate - endDate) / (1000 * 60 * 60 * 24));

    // areAllSelectedDatesHigh

    const ifTheEventIsLowSeasonValidation = calendarElement.calendar.getEvents().filter(event => {
        return event.display == 'background' && event.backgroundColor == '#FFE0E0';
    });
    const ifTheEventIsMiddleSeasonValidation = calendarElement.calendar.getEvents().filter(event => {
        return event.display == 'background' && event.backgroundColor == '#FFF0D9';
    });
    const ifTheEventIsHighSeasonValidation = calendarElement.calendar.getEvents().filter(event => {
        return event.display == 'background' && event.backgroundColor == '#D7E8FA';
    });

    const currentOwnerEvents = ownerId?.value
        ? calendarElement.calendar.getEvents().filter(event => {
            const props = event.extendedProps ?? {};
            return (
                Object.keys(props).length > 0 &&
                props.id_owner == ownerId.value
            );
        })
        : [];



    const seasonCounts = countAllNightsBySeason(startDate, endDate, {
        low: ifTheEventIsLowSeasonValidation,
        middle: ifTheEventIsMiddleSeasonValidation,
        high: ifTheEventIsHighSeasonValidation
    });

    let areAllSelectedDatesHigh = seasonCounts.high >= 1 && seasonCounts.low == 0 && seasonCounts.middle == 0;

    // isStartDateHigh, isEndDateHigh

    let isStartDateHigh = isDateInSeason(startDate, ifTheEventIsHighSeasonValidation);
    let isEndDateHigh = isDateInSeason(endDate, ifTheEventIsHighSeasonValidation);

    // lastHighDateForThatRange, firstHighDateForThatRange

    let lastHighDateForThatRange = getLastHighDateForThatRange(startDate, endDate, lastCalendarDate, isStartDateHigh, isEndDateHigh, ifTheEventIsHighSeasonValidation);
    let firstHighDateForThatRange = getFirstHighDateForThatRange(startDate, endDate, firstCalendarDate, isStartDateHigh, isEndDateHigh, ifTheEventIsHighSeasonValidation);

    // areThereHighAndLowSelectedDates

    let areThereHighAndLowSelectedDates = seasonCounts.high > 0 && (seasonCounts.low > 0 || seasonCounts.middle > 0);

    let highNightCount = seasonCounts.high;
    let lowMiddleNightCount = seasonCounts.low + seasonCounts.middle;

    return {
        startDate: startDate,
        endDate: endDate,
        isLastRound: isLastRound,
        nightCount: nightCount,
        gapBefore: gapBefore,
        gapAfter: gapAfter,
        areAllSelectedDatesHigh: areAllSelectedDatesHigh,
        lastHighDateForThatRange: lastHighDateForThatRange,
        firstHighDateForThatRange: firstHighDateForThatRange,
        areThereHighAndLowSelectedDates: areThereHighAndLowSelectedDates,
        areAllTheDatesInTheGapAfterHigh: areAllTheDatesInTheGapAfterHigh(reservas, lastCalendarDate, endDate, ifTheEventIsHighSeasonValidation),
        areAllTheDatesInTheGapBeforeHigh: areAllTheDatesInTheGapBeforeHigh(reservas, firstCalendarDate, startDate, ifTheEventIsHighSeasonValidation),
        isStartDateHigh: isStartDateHigh,
        isEndDateHigh: isEndDateHigh,
        isEarliestReservation: isEarliestReservation(reservas, startDate),
        isLatestReservation: isLatestReservation(reservas, endDate),
        lowMiddleNightCount: lowMiddleNightCount,
        highNightCount: highNightCount,
        isTheSameOwnerExtending: isTheSameOwnerExtending(currentOwnerEvents, startDate, endDate),
        nightsAvailable: nightsAvailable,
        allowedNights: allowedNights,
        selectedStartDate: selectedStartDate,
        selectedEndDate: selectedEndDate,

        strStartDate: strStartDate,
        strEndDate: strEndDate,

        ifTheEventIsLowSeasonValidation: ifTheEventIsLowSeasonValidation,
        ifTheEventIsMiddleSeasonValidation: ifTheEventIsMiddleSeasonValidation,
        ifTheEventIsHighSeasonValidation: ifTheEventIsHighSeasonValidation,

    };
}

function isSameDay(a, b) {
    const A = toDateOrNull(a);
    const B = toDateOrNull(b);
    if (!A || !B) return false;          // si alguno no existe o es inválido

    // normaliza a medianoche para comparar solo la parte de fecha
    A.setHours(0, 0, 0, 0);
    B.setHours(0, 0, 0, 0);
    return A.getTime() === B.getTime();
}

function toDateOrNull(x) {
    if (!x) return null;

    // Date ya válido
    if (x instanceof Date && !isNaN(x)) return new Date(x.getTime());

    // timestamp numérico
    if (typeof x === 'number') {
        const d = new Date(x);
        return isNaN(d) ? null : d;
    }

    // string "YYYY-MM-DD" (evita problemas de zona horaria)
    if (typeof x === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(x)) {
        const [y, m, d] = x.split('-').map(Number);
        const dt = new Date(y, m - 1, d);
        return isNaN(dt) ? null : dt;
    }

    // cualquier otro string que Date pueda parsear
    if (typeof x === 'string') {
        const d = new Date(x);
        return isNaN(d) ? null : d;
    }

    return null;
}

/**
 * Retorna la cantidad de días entre dos fechas (inclusive o no)
 * @param {string} startStr - Fecha inicial en formato YYYY-MM-DD
 * @param {string} endStr - Fecha final en formato YYYY-MM-DD
 * @param {boolean} inclusive - Si debe contar ambos días (por defecto false)
 * @returns {number} Cantidad de días entre las fechas
 */
export function daysBetween(startStr, endStr, inclusive = false) {
    const start = new Date(startStr);
    const end = new Date(endStr);

    // Diferencia en milisegundos
    const diffMs = end - start;

    // Convertimos milisegundos a días
    let days = diffMs / (1000 * 60 * 60 * 24);

    // Si es inclusivo (por ejemplo 22 al 25 → 4 días en lugar de 3)
    if (inclusive) days += 1;

    return days;
}

/*
    $isLastRound bool (Verdadero si nos encontramos en la última ronda)
    $startDate date (La primera fecha seleccionada)
    $endDate date (La última fecha seleccionada)
    $nighCount int ($endDate - $startDate)
    $lastDateReservedBeforeStartDate date (La última fecha reservada previa a $startDate)
    $firstDateReservedAfterEndDate date (La primera fecha reservada posterior a $endDate)
    $gapBefore int ($startDate - $lastDateReservedBeforeStartDate OR $firstCalendarDate)
    $gapAfter int ($firstDateReservedAfterEndDate - $endDate OR $lastCalendarDate)
    $areAllSelectedDatesHigh bool (Verdadero si todas las fechas seleccionadas son High)
    $lastHighDateForThatRange date (La última fecha del intervalo high en el que se encuentran $startDate y/o $endDate)
    $firstHighDateForThatRange date (La primera fecha del intervalo high en el que se encuentran $startDate y/o $endDate)
    $areThereHighAndLowSelectedDates bool (Verdadero si algunas fechas son high y otras son low)
    ---$isThereAnyOtherHighDateReservationAfterEnd bool (Verdadero si en el rango high en que se encuentra $endDate hay alguna otra reserva)
    ---$isThereAnyOtherHighDateReservationBeforeStart bool (Verdadero si en el rango high en que se encuentra $startDate hay alguna otra reserva)
    $areAllTheDatesInTheGapAfterHigh bool (Verdadero si todas las fechas entre $endDate y $firstDateReservedAfterEndDate son high)
    $areAllTheDatesInTheGapBeforeHigh bool (Verdadero si todas las fechas entre $startDate y $lastDateReservedBeforeStartDate son high)
    $isStartDateHigh bool (Verdadero si $startDate es High)
    $isEndDateHigh bool (Verdadero si $endDate es High)
*/

export function isAllowed(
    startDate, endDate, isLastRound, nightCount, gapBefore, gapAfter, areAllSelectedDatesHigh,
    firstHighDateForThatRange, lastHighDateForThatRange, areThereHighAndLowSelectedDates, isStartDateHigh, isEndDateHigh,
    isEarliestReservation, isLatestReservation, highNightCount, isTheSameOwnerExtending, nightsAvailable, allowedNights
) {

    const safeDate = (d) => {
        if (!d) return '';
        if (d instanceof Date) return d.toISOString().slice(0, 10); // YYYY-MM-DD
        const s = String(d);

        // Si ya viene como 'YYYY-MM-DD...' úsalo directo
        if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10);

        // Intenta parsear strings tipo "Tue Jun 09 2026 ..."
        const parsed = new Date(s);
        return Number.isNaN(parsed.getTime()) ? '' : parsed.toISOString().slice(0, 10);
    };

    // Convierte bool a 1/0 para PHP
    const b = (v) => (v ? 1 : 0);

    // Asegura enteros
    const i = (v, def = 0) => {
        const n = parseInt(v, 10);
        return Number.isFinite(n) ? n : def;
    };

    const params = {
        action: 'mojo_is_allowed',
        startDate: safeDate(startDate),
        endDate: safeDate(endDate),
        isLastRound: b(isLastRound),
        nightCount: i(nightCount),
        gapBefore: i(gapBefore),
        gapAfter: i(gapAfter),
        areAllSelectedDatesHigh: b(areAllSelectedDatesHigh),
        firstHighDateForThatRange: safeDate(firstHighDateForThatRange),
        lastHighDateForThatRange: safeDate(lastHighDateForThatRange),
        areThereHighAndLowSelectedDates: b(areThereHighAndLowSelectedDates),
        allowedNights: i(allowedNights),
        isStartDateHigh: b(isStartDateHigh),
        isEndDateHigh: b(isEndDateHigh),
        isEarliestReservation: b(isEarliestReservation),
        isLatestReservation: b(isLatestReservation),
        highNightCount: i(highNightCount),
        isTheSameOwnerExtending: b(isTheSameOwnerExtending),
        nightsAvailable: i(nightsAvailable),
    };

    if (admin_ajax) {

        fetch(`${admin_ajax}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params)
        })
            .then(res => res.json())
            .then(data => {
                if (data) {
                    calendarState.object_result = data.data;
                    return;
                } else {
                    calendarState.object_result = {};
                }
            })
            .catch(err => {
                console.error(err);
                alertError('There was a problem with the request. Please try again.');
            });

    }


    /*let isFillingTheGap = (gapBefore === 0 && gapAfter === 0);
    let booking7 = (nightCount >= 7);
    let gapRule3 = (gapBefore >= 3 || gapBefore === 0) && (gapAfter >= 3 || gapAfter === 0);
    let gapRule7 = (gapBefore >= 7 || gapBefore === 0) && (gapAfter >= 7 || gapAfter === 0);
    let booking3 = (nightCount >= 3);
    let bookingAllAvailable = (allowedNights == nightCount);
    let isEarliest = isEarliestReservation;
    let isLatest = isLatestReservation;
    let zeroNights = (nightCount === 0);
    let someHighSomeLow = areThereHighAndLowSelectedDates;
    let allHigh = areAllSelectedDatesHigh;
    let availableGap = gapAfter + gapBefore + nightCount;
    let gapLess14 = (availableGap < 14);

    let isGlued = gapAfter === 0 || gapBefore === 0;

    // Must select at least one night
    if (zeroNights) return { allowed: false, code: 'ERROR #00', message: 'Must select at least one night' };

    // Everything is allowed in the last round
    if (isLastRound) return { allowed: true, code: 'TRUE #00', message: 'Everything is allowed in the last round' };

    // Same owner extending
    if (isTheSameOwnerExtending) return { allowed: true, code: 'TRUE #01', message: 'Same owner extending' };

    if (allHigh || someHighSomeLow) {

        let matchSeasonStart = isSameDay(firstHighDateForThatRange, startDate);
        let matchSeasonEnd = isSameDay(lastHighDateForThatRange, endDate);

        if (booking7) {
            if (gapRule7) {
                return { allowed: true, code: 'TRUE #03', message: 'All conditions met for 7 nights or more' };
            } else {
                if (gapLess14) {
                    return { allowed: true, code: 'TRUE #06', message: 'All conditions met for 7 nights or more and available gap is 14 or less' };
                } else {
                    return { allowed: false, code: 'ERROR #01', message: 'It does not meet the gap rules' };
                }
            }
        } else {
            if (booking3) {
                if (isFillingTheGap) {
                    return { allowed: true, code: 'TRUE #05', message: 'All conditions met for 3 nights or more and filling the gap' };
                } else {
                    if (gapRule3) {
                        if (bookingAllAvailable) {
                            return { allowed: true, code: 'TRUE #07', message: 'All conditions met for 3 nights or more and gap rules satisfied' };
                        } else {
                            if ((matchSeasonStart || matchSeasonEnd) && isGlued) {
                                return { allowed: true, code: 'TRUE #08', message: 'All conditions met for 3 nights or more and starts with a high season interval' };
                            } else {
                                return { allowed: false, code: 'ERROR #02', message: 'It does not meet the gap rules' };
                            }
                        }
                    } else {
                        return { allowed: false, code: 'ERROR #03', message: 'It does not meet the gap rules' };
                    }
                }
            } else {
                return { allowed: false, code: 'ERROR #04', message: 'It does not meet the gap rules' };
            }
        }
    } else {
        if (booking3) {
            if (gapRule3) {
                return { allowed: true, code: 'TRUE #08', message: 'All conditions met for 3 nights or more and gap rules satisfied' };
            } else {
                if (bookingAllAvailable || isEarliest || isLatest) {
                    return { allowed: true, code: 'TRUE #09', message: 'Max nights available for 3 nights or more' };
                } else {
                    return { allowed: false, code: 'ERROR #05', message: 'It does not meet the gap rules' };
                }
            }
        } else {
            return { allowed: false, code: 'ERROR #06', message: 'It does not meet the gap rules' };
        }
    }*/
}

// Genera un array de YYYY-MM-DD entre start y end (inclusive), usando UTC para evitar problemas DST
function expandDateRange(startStr, endStr) {
    const toUTC = (s) => {
        const [y, m, d] = s.split('-').map(Number);
        return new Date(Date.UTC(y, m - 1, d));
    };

    let start = toUTC(startStr);
    let end = toUTC(endStr);

    // Asegura orden si vinieran invertidas
    if (start > end) [start, end] = [end, start];

    const out = [];
    const cur = new Date(start);
    while (cur <= end) {
        out.push(cur.toISOString().slice(0, 10)); // YYYY-MM-DD
        cur.setUTCDate(cur.getUTCDate() + 1);
    }
    return out;
}

function buildMergedOwnerRanges(eventsFromOwner) {
    // 1. Normalizamos start/end a objetos Date y hacemos end inclusivo
    const ranges = eventsFromOwner.map(ev => {
        const start = new Date(ev.start);
        const end = new Date(ev.end ?? ev.start);
        const owner = ev?.extendedProps?.id_owner ?? 0;

        if (ev.end) {
            // FullCalendar da end exclusivo, lo volvemos inclusivo
            end.setDate(end.getDate() - 1);
        }

        return { start, end, owner };
    });

    // 2. Ordenamos por fecha de inicio
    ranges.sort((a, b) => a.start - b.start);

    // 3. Fusionamos rangos que se tocan o se solapan
    const merged = [];
    for (const r of ranges) {
        if (merged.length === 0) {
            merged.push({ start: new Date(r.start), end: new Date(r.end) });
            continue;
        }

        const last = merged[merged.length - 1];

        // Día siguiente al último día del bloque previo
        const dayAfterLastEnd = new Date(last.end);
        dayAfterLastEnd.setDate(dayAfterLastEnd.getDate() + 1);

        // Caso A: r.start es el mismo día o inmediatamente después de last.end
        // Caso B: se solapan
        if (r.start <= dayAfterLastEnd) {
            // extendemos el final si este rango termina después
            if (r.end > last.end) {
                last.end = new Date(r.end);
            }
        } else {
            // no se tocan -> nuevo bloque
            merged.push({ start: new Date(r.start), end: new Date(r.end), owner: r.owner });
        }
    }

    return merged;
}

export function selectingDatesInCalendar(info) {

    const start = info.startStr;                         // YYYY-MM-DD
    const endInclusive = subtractOneDayString(info.endStr); // end de FC es exclusivo
    const isSingleDay = start === endInclusive;

    // ⚠️ Si es un clic simple (1 día), NO toques localStorage aquí.
    // Ese caso ya lo maneja dateClick -> blockingDates.
    if (isSingleDay) {
        // Solo reflejamos en el label lo que ya esté en LS (si quieres).
        const d1 = getDate1?.() || null;
        const d2 = getDate2?.() || null;

        if (inputHiddenBlockedDates) {
            inputHiddenBlockedDates.value = d1;
        }

        if (d1 && d2) setDateRange(d1, d2);
        else if (d1) setDateRange(d1, d1);
        return; // <- Salimos para no sobre-escribir date1/date2
    }

    setDate1(start);
    setDate2(endInclusive);
    setToggle('off');
    setDateRange(start, endInclusive);

    if (BLOCKED_DATES.length) {
        // Expande el rango seleccionado de forma inclusiva y verifica cruce
        const pickedDays = expandDateRange(start, endInclusive); // devuelve ["YYYY-MM-DD", ...]
        const touchesBlocked = pickedDays.some(d => BLOCKED_DATES.includes(d));

        if (touchesBlocked) {
            // Limpia/rehace UI y no sigas con nada más
            if (inputHiddenBlockedDates) inputHiddenBlockedDates.value = '';
            if (calendarElement?.calendar) calendarElement.calendar.unselect();
            if (typeof alertError === 'function') alertError('Some selected dates are blocked.');
            return; // ⛔ nada más
        }
    }

    if (document.querySelector('.in_admin')) {
        if (bookButton) {
            bookButton.style.display = 'block';
        }
    }

    let params = fullCalendarInfoToCalendarInfo(calendarElement, info, round, qtyShares);

    calendarState.strStartDate = params.strStartDate;
    calendarState.strEndDate = subtractOneDayString(params.strEndDate);

    calendarState.selectedStartDate = params.selectedStartDate + 'T00:00:00';
    calendarState.selectedEndDate = params.selectedEndDate + 'T00:00:00';

    // ---------------------------------------------------------------------

    let result = isAllowed(params.startDate, params.endDate, params.isLastRound, params.nightCount, params.gapBefore, params.gapAfter,
        params.areAllSelectedDatesHigh, params.firstHighDateForThatRange, params.lastHighDateForThatRange, params.areThereHighAndLowSelectedDates, params.isStartDateHigh, params.isEndDateHigh,
        params.isEarliestReservation, params.isLatestReservation, params.highNightCount, params.isTheSameOwnerExtending, params.nightsAvailable, params.allowedNights);

    /*console.log(result);
    calendarState.object_result = result;*/

    let startCurrentEvent = params.startDate;
    let endCurrentEvent = params.endDate;

    if (document.querySelector('.in_admin') && inputHiddenBlockedDates) {
        const { strStartDate, strEndDate } = calendarState || {};

        if (strStartDate && strEndDate) {
            const dates = expandDateRange(strStartDate, strEndDate);
            inputHiddenBlockedDates.value = dates.join(',');
            // console.log('Rango expandido:', dates);
        } else {
            inputHiddenBlockedDates.value = '';
        }
    }

    const seasonCounts = countAllNightsBySeason(startCurrentEvent, endCurrentEvent, {
        low: params.ifTheEventIsLowSeasonValidation,
        middle: params.ifTheEventIsMiddleSeasonValidation,
        high: params.ifTheEventIsHighSeasonValidation,
    });

    const seasonCountsByDays = countAllDaysBySeason(startCurrentEvent, endCurrentEvent, {
        low: params.ifTheEventIsLowSeasonValidation,
        middle: params.ifTheEventIsMiddleSeasonValidation,
        high: params.ifTheEventIsHighSeasonValidation,
    });

    const ifThereIsAnEventValidation = calendarElement.calendar.getEvents().filter(event => Object.keys(event.extendedProps ?? {}).length > 0);

    // ------------------------------------------------

    let isWithinEvent, isRentedTheEvent, isWithinEventButIsntOwner;

    if (document.querySelector('.in_panel')) {
        const ifTheEventIsFromTheOwnerOnTurnValidation = calendarElement.calendar.getEvents().filter(event => {
            const props = event.extendedProps ?? {};
            return (
                Object.keys(props).length > 0 &&
                props.id_owner == ownerId.value
            );
        });

        const mergedOwnerRanges = buildMergedOwnerRanges(ifTheEventIsFromTheOwnerOnTurnValidation);
        isWithinEvent = mergedOwnerRanges.some(event => {
            const eventStart = new Date(event.start);
            const eventEnd = new Date(event.end ?? event.start); // Si no tiene end, usa start
            /*if (event.end) {
                eventEnd.setDate(eventEnd.getDate() - 1);
            }*/

            return startCurrentEvent >= eventStart && endCurrentEvent <= eventEnd;
        });
        calendarState.isWithinEvent = isWithinEvent;

        // --------------------------------------------------------------------------------------------------------

        const ifTheEventIsntFromTheOwnerOnTurnValidation = calendarElement.calendar.getEvents().filter(event => {
            const props = event.extendedProps ?? {};
            return (
                Object.keys(props).length > 0 &&
                props.id_owner != ownerId.value
            );
        });

        const mergedIsntOwnerRanges = buildMergedOwnerRanges(ifTheEventIsntFromTheOwnerOnTurnValidation);
        isWithinEventButIsntOwner = mergedIsntOwnerRanges.some(event => {
            const eventStart = new Date(event.start);
            const eventEnd = new Date(event.end ?? event.start); // Si no tiene end, usa start
            /*if (event.end) {
                eventEnd.setDate(eventEnd.getDate() - 1);
            }*/

            return startCurrentEvent >= eventStart && endCurrentEvent <= eventEnd;
        });
        calendarState.isWithinEventButIsntOwner = isWithinEventButIsntOwner;

        // --------------------------------------------------------------------------------------------------------

        const ifTheEventIsRentedAndFromTheOwnerOnTurnValidation = calendarElement.calendar.getEvents().filter(event => {
            const props = event.extendedProps ?? {};
            return (
                Object.keys(props).length > 0 &&
                props.id_owner == ownerId.value &&
                props.owner_position == ownerShare.value &&
                event.backgroundColor == '#CCCCCC'
            );
        });

        isRentedTheEvent = ifTheEventIsRentedAndFromTheOwnerOnTurnValidation.some(event => {
            const eventStart = new Date(event.start);
            const eventEnd = new Date(event.end ?? event.start); // Si no tiene end, usa start
            /*if (event.end) {
                eventEnd.setDate(eventEnd.getDate() - 1);
            }*/

            return startCurrentEvent >= eventStart && endCurrentEvent <= eventEnd;
        });
        calendarState.isRentedTheEvent = isRentedTheEvent;

        // --------------------------------------------------------------------------------------------------------

        // jcc - purchase mode: rental events (#CCCCCC) belonging to OTHER owners (anyone but me)
        const ifTheEventIsRentalFromOthersValidation = calendarElement.calendar.getEvents().filter(event => {
            const props = event.extendedProps ?? {};
            return (
                Object.keys(props).length > 0 &&
                props.id_owner != ownerId.value &&
                event.backgroundColor == '#CCCCCC'
            );
        });

        const mergedRentalFromOthersRanges = buildMergedOwnerRanges(ifTheEventIsRentalFromOthersValidation);
        const isWithinRentalFromOthers = mergedRentalFromOthersRanges.some(event => {
            const eventStart = new Date(event.start);
            const eventEnd = new Date(event.end ?? event.start);
            return startCurrentEvent >= eventStart && endCurrentEvent <= eventEnd;
        });
        calendarState.isWithinRentalFromOthers = isWithinRentalFromOthers;

        // --------------------------------------------------------------------------------------------------------
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Calcular la diferencia en días
    const diffTime = Math.abs(info.start - info.end);
    calendarState.diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    // calendarState.diffNights = Math.max(Math.ceil(diffTime / (1000 * 60 * 60 * 24)) - 1, 0);
    calendarState.diffNights = Math.max(diffDaysUTC(info.startStr, info.endStr) - 1, 0);


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

        countSelectedCells.innerHTML = `${parseInt(selectedDays.value) + calendarState.diffNights}/${maxDays.value}`;

    } else {



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

}