/* ===== STATE ===== */
const CommentsState = {
    byDate: {} // 'YYYY-MM-DD'
};

function initComments(comments = []) {
    CommentsState.byDate = {};

    comments
        .slice()
        .sort((a, b) => b.id - a.id)
        .forEach(comment => {
            (CommentsState.byDate[comment.date] ||= []).push(comment);
        });
}

function getCommentsByDate(dateStr) {
    return CommentsState.byDate[dateStr] || [];
}

function renderCommentsTable(dateStr) {
    const tbody = document.querySelector('#comments-table tbody');
    const titleEl = document.getElementById('comments-date-title');
    if (!tbody) return;

    const comments = getCommentsByDate(dateStr);

    if (titleEl) {
        titleEl.style.display = comments.length ? 'block' : 'none';
        titleEl.textContent = comments.length
            ? `Comments for ${formatPrettyDate(dateStr)}`
            : '';
    }

    tbody.innerHTML = comments.length
        ? comments.map(renderCommentRow).join('')
        : `<tr><td>No comments for this day</td></tr>`;
}

function renderCommentRow(comment) {
    return `
        <tr>
            <td colspan="4">
                <p class="title_comment"><b>${comment.title}</b></p>
                ${comment.description ? `<p>${comment.description}</p>` : ''}
            </td>
            <td>
                <button 
                    type="button"
                    class="delete_comment"
                    data-id="${comment.id}">
                    &#10006;
                </button>
            </td>
        </tr>
    `;
}

window.renderCommentsInDayCell = function (arg) {
    const dateStr = arg.date?.toLocaleDateString('en-CA');
    if (!dateStr) return { html: arg.dayNumberText };

    const comments = getCommentsByDate(dateStr);
    let html = `<div class="fc-day-number">${arg.dayNumberText}</div>`;

    if (comments.length) {
        const last = comments.at(-1);
        html += `
            <div class="fc-comments">
                <div class="fc-comment-item"
                    data-comment-id="${last.id}"
                    data-comment-date="${last.date}">
                    ${last.title}
                </div>
            </div>
        `;
    }

    return { html };
};

document.addEventListener('click', (e) => {
    const commentCenter = document.getElementById('comment-center');
    if (!commentCenter) return;

    const calendarItem = e.target.closest('.fc-comment-item');
    const deleteBtn = e.target.closest('.delete_comment');
    const insideCenter = e.target.closest('#comment-center');

    if (calendarItem) {
        const { commentId, commentDate } = calendarItem.dataset;
        if (!commentId || !commentDate) return;

        renderCommentsTable(commentDate);
        commentCenter.classList.add('active');
        return;
    }

    if (deleteBtn) {
        if (!confirm('Are you sure you want to delete this comment?')) return;
        submitDelete(deleteBtn.dataset.id);
        return;
    }

    if (!insideCenter) {
        commentCenter.classList.remove('active');
    }
});

function submitDelete(commentId) {
    if (!commentId) return;

    const form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = `
        <input type="hidden" name="comment_id" value="${commentId}">
        <input type="hidden" name="delete_comment" value="1">
    `;
    document.body.appendChild(form);
    form.submit();
}

function formatPrettyDate(dateStr) {
    const date = new Date(`${dateStr}T00:00:00`);
    const day = date.getDate();
    const suffix =
        day > 3 && day < 21 ? 'th' :
        day % 10 === 1 ? 'st' :
        day % 10 === 2 ? 'nd' :
        day % 10 === 3 ? 'rd' : 'th';

    return `${date.toLocaleString('en-US', { month: 'short' })} ${day}${suffix}, ${date.getFullYear()}`;
}

document.addEventListener('DOMContentLoaded', () => {
    initComments(window.MOJO_COMMENTS || []);

    const titleEl = document.getElementById('comments-date-title');
    if (titleEl) titleEl.style.display = 'none';

    const addBtn = document.getElementById('comment');
    const popup = document.getElementById('commenting');
    if (addBtn && popup) {
        addBtn.addEventListener('click', () => popup.classList.add('active'));
    }

    initCreateValidation();
});

function initCreateValidation() {
    const form = document.querySelector('#commenting form');
    if (!form) return;

    const submitBtn = document.getElementById('submitComment');
    const titleInput = form.querySelector('[name="title"]');
    const dateInput = form.querySelector('[name="date"]');
    if (!submitBtn || !titleInput || !dateInput) return;

    const toggle = () => {
        submitBtn.disabled = !titleInput.value.trim() || !dateInput.value.trim();
    };

    titleInput.addEventListener('input', toggle);
    dateInput.addEventListener('input', toggle);
    toggle();
}

window.adjustCommentsPosition = function () {
    document.querySelectorAll('.fc-daygrid-day-frame').forEach(day => {
        const comments = day.querySelector('.fc-comments');
        if (!comments) return;

        const height = day.offsetHeight;
        console.log(height, 'height')
        comments.style.top = height > 85 ? '67px' : '48px';
    });
};
