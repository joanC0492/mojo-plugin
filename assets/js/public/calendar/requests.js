import {
    logRentSuccess,
    paintExchangeFromBackground,
    updateExchangeUI
} from './helpers.js';

import { ADMIN_AJAX } from '../calendar.js';

function getWin() {
    return window;
}

export function bookPeriod(params) {
    return fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    }).then(res => res.json());
}

export function deleteBookedDate(params) {
    return fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    }).then(res => res.json());
}

export function confirmReservation(params) {
    return fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    }).then(res => res.json());
}

export function rentPeriod(params) {
    fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
        .then(res => res.json())
        .then(handleRentResponse)
        .catch(handleRentError);
}

function handleRentResponse(resp) {
    if (!resp?.success) {
        getWin().alertError?.(
            resp?.data?.message ||
            'There was a problem with the request. Please try again.'
        );
        getWin().unselectCalendar?.();
        getWin().clearRange?.();
        return;
    }

    getWin().Swal?.fire({
        title: 'Reserved for rent!',
        icon: 'success'
    }).then(() => {
        logRentSuccess();
        window.location.reload();
    });
}

function handleRentError(error) {
    console.error('Error in the request:', error);
    getWin().alertError?.('There was a problem with the request. Please try again.');
    getWin().unselectCalendar?.();
}

export function saveExchangeFrom(params) {
    fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
        .then(res => res.json())
        .then(data => {
            if (!data?.success) {
                getWin().alertError?.(
                    data?.data?.message ||
                    'There was a problem with the reservation. Please try again.'
                );
                getWin().clearRange?.();
                getWin().unselectCalendar?.();
                return;
            }

            paintExchangeFromBackground(params);
            getWin().clearRange?.();
            getWin().unselectCalendar?.();

            window.k = 0;
            window.l = 1;

            getWin().Swal?.fire({
                title: 'Now select the days you want',
                icon: 'success'
            });

            updateExchangeUI(params);
        })
        .catch(error => {
            console.error('Error in the request:', error);
            getWin().alertError?.('There was a problem with the request. Please try again.');
            getWin().clearRange?.();
            getWin().unselectCalendar?.();
        });
}

export function saveExchangeTo(params) {
    return fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    }).then(res => res.json());
}

export function sendRequestToAdmins(params) {
    return fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    }).then(res => res.json());
}

export function changeRequestStatus(params) {
    return fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    }).then(res => res.json());
}

export function removePeriod(id_period) {
    const params = {
        action: 'mojo_panel_remove_period',
        id_period
    };

    fetch(ADMIN_AJAX, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
        .then(res => res.json())
        .then(handleRemoveResponse)
        .catch(handleRemoveError);
}

function handleRemoveResponse(data) {
    if (!data?.success) {
        getWin().alertError?.(
            data?.data?.message ||
            'There was a problem with the reservation. Please try again.'
        );
        return;
    }

    getWin().Swal?.fire({
        title: 'Reservation removed!',
        icon: 'success'
    }).then(() => {
        window.location.reload();
    });
}

function handleRemoveError(error) {
    console.error('Error in the request:', error);
    getWin().alertError?.('There was a problem with the request. Please try again.');
}
