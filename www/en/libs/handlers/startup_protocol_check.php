<?php
    /*
     * Protocol has changed! This means that the current session_id might not be secure. Drop it, and start over.
     */
    load_libs('user');
    user_signout();
    session_start();
    $_SESSION['protocol'] = $_SERVER['SERVER_PROTOCOL'];

    html_flash_set(tr('Your session was closed because your protocol security has changed'), 'warning');
?>