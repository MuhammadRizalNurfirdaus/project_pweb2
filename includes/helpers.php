<?php
function redirect($url)
{
    global $base_url;
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = rtrim($base_url, '/') . '/' . ltrim($url, '/');
    }
    header("Location: " . $url);
    exit;
}

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function e($string)
{
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function input($key, $default = null, $method = 'post')
{
    switch (strtolower($method)) {
        case 'get':
            return isset($_GET[$key]) ? $_GET[$key] : $default;
        case 'request':
            return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
        case 'post':
        default:
            return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
}
