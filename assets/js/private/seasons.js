function parseLocalYMD(ymd) {
    const [y, m, d] = ymd.split('-').map(Number);
    return new Date(y, m - 1, d, 0, 0, 0, 0); // medianoche local
}

function addDays(date, n) {
    const x = new Date(date);
    x.setDate(x.getDate() + n);
    x.setHours(0, 0, 0, 0);
    return x;
}

function toYMDLocal(date) {
    // YYYY-MM-DD en hora local, sin problemas de zona
    return date.toLocaleDateString('en-CA');
}

jQuery(document).ready(function ($) {
    let selected_dates = [];

    const calendarEl = document.getElementById('calendar');
    const yearSelected = document.querySelector('#year');

    let strStartDateOfRange, strEndDateOfRange;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'multiMonthYear',
        initialDate: `${yearSelected.value}-01-01`,
        selectable: true,
        events: events,
        select: function (info) {
            selected_dates = [];

            const start = parseLocalYMD(info.startStr);      // start inclusivo (local)
            const endExcl = parseLocalYMD(info.endStr);      // end exclusivo (local)
            const endIncl = addDays(endExcl, -1);            // lo convertimos a inclusivo

            for (let d = new Date(start); d <= endIncl; d = addDays(d, 1)) {
                const ymd = toYMDLocal(d);                     // SIN UTC
                selected_dates.push(ymd);
            }

            // console.table(selected_dates);
        }
    });

    calendar.render();

    $('.season_button').on('click', function (e) {
        e.preventDefault();
        let n = $(this).attr('name');

        if (!Array.isArray(selected_dates) || selected_dates.length === 0) {
            alert('There are no dates selected');
            return;
        }

        const requests = selected_dates.map(date => {
            return $.post(ajaxurl, {
                action: 'save_or_update_season',
                date: date,
                type: n,
                year: yearSelected.value
            });
        });

        Promise.all(requests).then(responses => {
            console.log(responses)
            location.reload();
        });

    });

    $('.season_clear').on('click', function (e) {
        e.preventDefault();

        if (!Array.isArray(selected_dates) || selected_dates.length === 0) {
            alert('There are no dates selected');
            return;
        }

        const requests = selected_dates.map(date => {
            return $.post(ajaxurl, {
                action: 'remove_season',
                date: date,
                year: yearSelected.value
            });
        });

        Promise.all(requests).then(responses => {
            console.log(responses)
            location.reload();
        });

    });

});