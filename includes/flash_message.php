<?php
function set_flash($name, $message)
{
    $_SESSION[$name] = $message;
}

function get_flash($name)
{
    if (isset($_SESSION[$name])) {
        $msg = $_SESSION[$name];
        unset($_SESSION[$name]);
        return $msg;
    }
    return null;
}
