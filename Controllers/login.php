<?php


function route($method, $controllerData, $requestData) {
    if ($method == 'POST') {
        $headers = getallheaders();
        $username = $requestData->Username;
        $password = $requestData->Password;

        global $connect;
        $suchUser = mysqli_fetch_array($connect->query("SELECT * FROM `user` WHERE `user`.`Username` = '$username' AND `user`.`Password` = '$password'"));
        if (isset($suchUser)) {
            if (isset($suchUser["Token"])) {
                if("Bearer " . $suchUser["Token"] == $headers["Authorization"]) {
                    http_response_code(405);
                    echo json_encode([
                        'status' => false,
                        'message' => 'You are already log on. Token doesnt change. If you want change token - re-login, please'
                    ]);
                    exit();
                }

                http_response_code(409);
                echo json_encode([
                    'status' => false,
                    'message' => 'This user already log on.'
                ]);
            }
            else {
                $token = generateToken();
                $connect->query("UPDATE `user` SET `token` = '$token' WHERE `user`.`Username` = '$username' AND `user`.`Password` = '$password'");

                echo json_encode($token);
            }
            exit();
        }
        else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'message' => 'User with such Username or/and Password doesnt exist yet.'
            ]);
            exit();
        }

    }
}
