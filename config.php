<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
// allow cross origin requests
header("Access-Control-Allow-Origin: *");

// load values from .env file
$env = file_get_contents('.env');
$env = explode("\n", $env);
$env = array_map(function($item) {
    return explode('=', $item);
}, $env);
$env = array_combine(array_column($env, 0), array_column($env, 1));

putenv('AWS_DEFAULT_REGION='.$env['AWS_DEFAULT_REGION']);
putenv('AWS_ACCESS_KEY_ID='.$env['AWS_ACCESS_KEY_ID']);
putenv('AWS_SECRET_ACCESS_KEY='.$env['AWS_SECRET_ACCESS_KEY']);

$uploadSizeLimit = "20000000";
?>
