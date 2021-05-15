<?php
include_once 'functions.php';

$formData = getDataByForm($_SERVER['REQUEST_METHOD']);

$url = rtrim((isset($_GET['q'])) ? $_GET['q'] : '', '/');
$urls = explode('/', $url);

$router = $urls[0];
$urlData = array_slice($urls, 1);