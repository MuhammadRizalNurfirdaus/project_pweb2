<?php
function redirect($url)
{
    header("Location: $url");
    exit;
}

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
