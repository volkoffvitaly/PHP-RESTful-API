<?php

use JetBrains\PhpStorm\NoReturn;

header('Content-type: json/application');
require_once "vendor.php";

#[NoReturn] function route($method, $controllerData, $formData) {
    if ($method === 'POST') {
        global $connect;
        $headers = getallheaders();

        if (isset($headers["Authorization"])) {
            $token = str_replace("Bearer ", "", $headers["Authorization"]);
            $connect->query("UPDATE `user` SET `Token` = NULL WHERE `user`.`Token` = '$token'");
        }
        exit();
    }
}