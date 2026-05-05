document.getElementById('mojo_login_form').addEventListener('submit', function(e) {
    e.preventDefault();

    const admin_ajax = document.querySelector('#mojo-admin_ajax').value,
        uri = document.querySelector('#mojo-uri').value;

    const email = document.getElementById('mojo_email').value,
        password = document.getElementById('mojo_password').value;

    const response = document.querySelector('#response');
    // response.innerHTML = '';

    fetch(`${admin_ajax}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'mojo_login_owner',
            email: email,
            password: password
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = uri + '/dashboard';
        } else {
            response.innerHTML = `<div class="mojo_login-error">
                <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512">
                    <path d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" />
                    <path d="M250.26 166.05L256 288l5.73-121.95a5.74 5.74 0 00-5.79-6h0a5.74 5.74 0 00-5.68 6z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" />
                    <path d="M256 367.91a20 20 0 1120-20 20 20 0 01-20 20z" />
                </svg>
                <p>${data.data.message}</p>
            </div>`;
        }
    });
});