function getWin() {
    return window;
}

export function updateSelectedCounter() {
    const { countSelectedCells, selectedDays, maxDays } = getWin();

    if (countSelectedCells && selectedDays && maxDays) {
        countSelectedCells.innerHTML = `${selectedDays.value}/${maxDays.value}`;
    }
}

export function logToPhp(message, context = {}) {
    const { admin_ajax } = getWin();

    if (!admin_ajax) return;

    const params = {
        action: 'mojo_panel_jslog',
        message,
        context: JSON.stringify(context)
    };

    fetch(admin_ajax, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    }).catch(() => {});
}

export function buildRentParams({
    calendarState,
    calendarId,
    ownerId,
    ownerShare,
    round
}) {
    return {
        action: 'mojo_panel_rent_period',
        calendar_id: calendarId?.value ?? null,
        owner_position: ownerShare?.value ?? null,
        start: calendarState?.strStartDate ?? null,
        end: calendarState?.strEndDate ?? null,
        round: parseInt(round?.value ?? 0),
        owner_id: ownerId?.value ?? null
    };
}

export function logRentSuccess() {
    const {
        calendarState,
        calendarId,
        ownerId,
        ownerPosition,
        round,
        selectedDays
    } = getWin();

    logToPhp('Reserved for rent!', {
        code: calendarState?.object_result?.code ?? null,
        start: calendarState?.strStartDate ?? null,
        end: calendarState?.strEndDate ?? null,
        calendar_id: calendarId?.value ?? null,
        owner_id: ownerId?.value ?? null,
        owner_position: ownerPosition?.value ?? null,
        round: round?.value ?? null,
        selectedDays: selectedDays?.value ?? null,
        diffNights: calendarState?.diffNights ?? null
    });
}

export function buildExchangeFromParams() {
    const { calendarId, calendarState, ownerId } = getWin();

    return {
        action: 'mojo_panel_save_exchange_request_pre_validation',
        calendarId: calendarId?.value ?? null,
        startFrom: calendarState?.strStartDate ?? null,
        endFrom: calendarState?.strEndDate ?? null,
        ownerFrom: ownerId?.value ?? null
    };
}

export function buildExchangeToParams(params) {
    const { calendarState } = getWin();

    return {
        ...params,
        action: 'mojo_panel_save_exchange_request',
        startTo: calendarState?.strStartDate ?? null,
        endTo: calendarState?.strEndDate ?? null
    };
}

export function paintExchangeFromBackground(params) {
    const { calendarElement, firstExchangeBgEvent } = getWin();
    if (!calendarElement || !params?.startFrom || !params?.endFrom) return;

    const start = params.startFrom;
    const end = addOneDayString(params.endFrom);

    if (firstExchangeBgEvent) {
        firstExchangeBgEvent.remove();
    }

    getWin().firstExchangeBgEvent = calendarElement.calendar.addEvent({
        start,
        end,
        allDay: true,
        display: 'background',
        overlap: false,
        groupId: 'exchange_from',
        classNames: ['exchange-from-highlight']
    });
}

export function updateExchangeUI(params) {
    const { box_body_request } = getWin();
    if (!box_body_request || !params) return;

    box_body_request.setAttribute('data-state', 1);

    box_body_request.querySelector('#from_booking').textContent =
        `${formatDMY(params.startFrom)} - ${formatDMY(params.endFrom)}`;
}

/* ========= CONFIRM MODALS ========= */
export function confirmDeletePeriod() {
    return Swal.fire({
        text: 'Are you sure you want to remove these dates?',
        icon: 'question',
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: 'Yes'
    });
}

export function confirmSendExchangeRequest() {
    return Swal.fire({
        text: 'Are you sure you want to send your request to exchange this dates?',
        icon: 'question',
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: 'Yes'
    });
}