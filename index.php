<?php
header('Content-type: json/application');
error_reporting(E_ALL ^ E_WARNING); // disable warnings

require_once "functions.php";
require_once "vendor.php";
require_once "constants.php";
require_once "response.php";
require_once "requestDecliner.php";

function getDataFromRequest($method) { // JSON interaction only (exclude files)
    if ($method === 'GET') return $_GET;
    if ($method === 'POST' && !empty($_FILES)) return $_FILES;

    $incomingData = file_get_contents('php://input');
    $decodedJSON = json_decode($incomingData); //пытаемся преобразовать то, что нам пришло из JSON в объект PHP
    if ($decodedJSON)
    {
        $data = $decodedJSON;
    }
    else
    {
        $data = array();
        $exploded = explode('&', file_get_contents('php://input'));
        foreach($exploded as $pair)
        {
            $item = explode('=', $pair);
            if (count($item) == 2)
            {
                $data[urldecode($item[0])] = urldecode($item[1]);
            }
        }
    }
    return $data;
}

ConfigureDB();



$method = $_SERVER['REQUEST_METHOD'];
$data = getDataFromRequest($method);

if(is_array($data)) {
    if (isset($data["q"])) {
        unset($data["q"]);
    }
}

if (isset($_GET['q'])) { // if local url isn't empty
    $localUrl = $_GET['q']; // Set a local url form $_GET
    $localUrl = rtrim($localUrl, '/'); // Delete last / , if exists
    $localUrlParts = explode('/', $localUrl); // Get all parts of local path
    $controller = $localUrlParts[0]; // Get controller from a local path
    $controllerData = array_slice($localUrlParts, 1); // Get a local path exclude a controller
}
else {
    $localUrl = '';
}


if (isset($localUrlParts)) { // if local url isn't empty
    if (file_exists('Controllers/' . $controller . '.php')) {
        require_once 'Controllers/' . $controller . '.php';
        route($method, $controllerData, $data);
    }
    else {
        http_response_code(404);
    }
}
else {
    http_response_code(400); // TODO: maybe...
}

http_response_code(404);
echo json_encode([
    'status' => false,
    'message' => 'Endpoint doesnt exists.'
]);