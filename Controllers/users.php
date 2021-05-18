<?php
require_once 'Classes/User.php';
require_once "functions.php";
require_once "vendor.php";

function printUsers($DbUsers) {
    if ($DbUsers->num_rows > 0) {
        while ($row = $DbUsers->fetch_assoc()) {
            echo json_encode(array(
                'Id' => $row['Id'],
                'Name' => $row['Name'],
                'Surname' => $row['Surname'],
                'Status' => $row['Status'],
            ));
        }
    }
}

function route($method, $controllerData, $formData) {
    global $connect;

    if ($method === 'GET') {
        if (Count($controllerData) === 0) {
            $DbUsers = $connect->query("SELECT * FROM `user`");
            printUsers($DbUsers);
        } // Get all users
        if (Count($controllerData) === 1) {
            $requiredUserId = (int)$controllerData[0];
            $DbUser = $connect->query("SELECT * FROM `user` WHERE `id` = $requiredUserId");
            printUsers($DbUser);
        } // Get a single user by Id
    }
    else if ($method === 'POST') {
        if (Count($controllerData) === 0) {

            $model = new User();
            $model->Name = $formData["Name"]; // тут тоже можно поделать всякие валидации и кэтчи, но они примитивны и я не хочу :))
            $model->Surname = $formData["Surname"]; //
            $model->Username = $formData["Username"]; //
            $model->Password = $formData["Password"]; //

            if (isset($formData["Birthday"])) { // валидация введенного ДР
                $result = $model->setBirthday($formData["Birthday"]);
                if (!$result) {
                    http_response_code(400);
                    return;
                }
                else {
                    $model->Birthday = $formData["Birthday"];
                }
            }

            //$token = generateToken();

            $similarUser = $connect->query("SELECT * FROM `user` WHERE `user`.`Username` = '$model->Username'");
            if ($similarUser->num_rows > 0) {
                http_response_code(409); // user with such Username already exists
                return;
            }

            $connect->query("INSERT INTO `user` (`Id`, `Name`, `Surname`, `Password`, `Birthday`, `Avatar`, `Status`, `Username`, `Token`, `City_Id`, `Role_Id`) 
                                   VALUES (NULL, '$model->Name', '$model->Surname', '$model->Password', '$model->Birthday', NULL, NULL, '$model->Username', NULL, NUll, NULL)");
        } // Register a new user

//        if ($model->rules()) {
//            //print_r($model->rules());
//            $myscl->query("INSERT INTO `user` (`id`, `name`, `surname`, `password`, `bitrhday`, `avatar`, `status`, `username`) VALUES (NULL, '$model->name', '$model->surname', '$model->password', '$model->birthday', NULL, NULL, '$model->username')");
//
//        } else {
//            header('X-PHP-Response-Code: 404', true, 404);
//            return;
//        }
//
//        if ($permis == -1) {
//            $user = $myscl->query("SELECT * FROM `user` WHERE `username` = '$model->username' AND `password` = '$model->password'");
//            if ($user->num_rows > 0) {
//                while ($row = $user->fetch_assoc()) {
//                    $id = $row['id'];
//                }
//            }
//            $token = gen_token();
//            $myscl->query("UPDATE `user` SET `token` = '$token' WHERE `user`.`id` = $id");
//            echo json_encode(array(
//                'token' => $token,
//                'iduser' => $id
//            ));
//        }
    }
}