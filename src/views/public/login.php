<?php

function get_owners_login()
{
    $html = '<div class="mojo_plugin mojo_login-widget">
        <div class="mojo_login-logo">
            <img src="'.MEDIA.'/logo.png" width="281">
        </div>
        <div id="response">

        </div>
        <form class="mojo_login-form" id="mojo_login_form">
            <div class="mojo_login-control">
                <label for="mojo_email">Email</label>
                <input type="email" id="mojo_email" name="mojo_email" required>
            </div>
            <div class="mojo_login-control mblock">
                <label for="mojo_password">Password</label>
                <input type="password" id="mojo_password" name="mojo_password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="mojo_login-advice">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_232_2436)">
                    <path d="M11.25 14.375H10.625V8.78875C10.625 8.78188 10.6231 8.77563 10.6231 8.76937C10.6231 8.76313 10.625 8.75688 10.625 8.75C10.625 8.405 10.345 8.125 10 8.125H8.75C8.405 8.125 8.125 8.405 8.125 8.75C8.125 9.095 8.405 9.375 8.75 9.375H9.375V14.375H8.75C8.405 14.375 8.125 14.655 8.125 15C8.125 15.345 8.405 15.625 8.75 15.625H11.25C11.595 15.625 11.875 15.345 11.875 15C11.875 14.655 11.595 14.375 11.25 14.375ZM10 6.875C10.6906 6.875 11.25 6.315 11.25 5.625C11.25 4.935 10.6906 4.375 10 4.375C9.30937 4.375 8.75 4.935 8.75 5.625C8.75 6.315 9.31 6.875 10 6.875ZM10 0C4.4775 0 0 4.47688 0 10C0 15.5231 4.47688 20 10 20C15.5231 20 20 15.5231 20 10C20 4.47688 15.5231 0 10 0ZM10 18.7694C5.175 18.7694 1.25 14.8244 1.25 9.99937C1.25 5.17438 5.175 1.24937 10 1.24937C14.825 1.24937 18.75 5.17438 18.75 9.99937C18.75 14.8244 14.825 18.7694 10 18.7694Z" fill="#33475B" />
                </g>
                <defs>
                    <clipPath id="clip0_232_2436">
                        <rect width="20" height="20" fill="white" />
                    </clipPath>
                </defs>
            </svg>
            <p>If you have forgotten your password, please contact us at <a href="mailto:'.get_contact_us_email().'">'.get_contact_us_email().'</a> to help you recover it</p>
        </div>
    </div>';

    return $html;
}
add_shortcode('mojo_login', 'get_owners_login'); // [mojo_login]


function get_button_login()
{
    $button = '<a class="mojo_simple_button" href="' . esc_url(home_url('panel')) . '" title="Login">
        <svg width="15" height="17" viewBox="0 0 15 17" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_11_37)">
                <path d="M7.49999 0.708334C5.39056 0.708334 3.68054 2.45256 3.68054 4.60417C3.68054 6.75578 5.39056 8.5 7.49999 8.5C9.60943 8.5 11.3194 6.75578 11.3194 4.60417C11.3194 2.45256 9.60943 0.708334 7.49999 0.708334Z" fill="white"/>
                <path d="M4.02776 9.91666C2.11011 9.91666 0.555542 11.5023 0.555542 13.4583V15.5833C0.555542 15.9745 0.866459 16.2917 1.24999 16.2917H13.75C14.1335 16.2917 14.4444 15.9745 14.4444 15.5833V13.4583C14.4444 11.5023 12.8898 9.91666 10.9722 9.91666H4.02776Z" fill="white"/>
            </g>
            <defs>
                <clipPath id="clip0_11_37">
                    <rect width="15" height="17" fill="white"/>
                </clipPath>
            </defs>
        </svg>
        LOGIN
    </a>';

    return $button;
}
add_shortcode('mojo_button', 'get_button_login'); // [mojo_button]


add_action('wp_ajax_nopriv_mojo_login_owner', 'mojo_login_owner');
add_action('wp_ajax_mojo_login_owner', 'mojo_login_owner');

function mojo_login_owner()
{
    global $wpdb;

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        wp_send_json_error(['message' => 'Please fill all fields']);
    }

    $owner = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM cs_owners WHERE email = %s", $email)
    );

    if (!$owner) {
        wp_send_json_error(['message' => 'Wrong credentials']);
    }

    if ($owner->password !== $password) {
        wp_send_json_error(['message' => 'Wrong credentials']);
    }

    $_SESSION['mojo_owner_id'] = $owner->id;
    $_SESSION['mojo_owner_name'] = $owner->name;

    wp_send_json_success([
        'message' => 'Login successful',
        'owner_id' => $owner->id,
        'owner_name' => $owner->name
    ]);
}





function mojo_logout()
{
    session_start();
    
    unset($_SESSION['mojo_owner_id']);
    unset($_SESSION['mojo_owner_name']);
    session_destroy();

    // wp_logout();
    wp_send_json_success();
}
add_action('wp_ajax_mojo_logout', 'mojo_logout');
add_action('wp_ajax_nopriv_mojo_logout', 'mojo_logout');