<?php


function route($method, $controllerData, $requestData)
{
    global $connect;
    $headers = getallheaders();

    if (isset($headers["Authorization"])) $generalAccessLevel = GetGeneralAccessLevel($headers["Authorization"]);
    else $generalAccessLevel = UNAUTHORIZED_ACCESS_LEVEL;


    if ($method === 'GET') {
        if (Count($controllerData) === 0) {
            if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) {
                $userId = getUserId($headers["Authorization"]);
                $message = $connect->query("SELECT * FROM `message` WHERE `UserStart_Id` = '$userId' OR `UserEND_Id` = '$userId'");

                printJSON($message);
                exit();
            } //  /users/{messageId}:  Get all your messages
            else Forbidden_403();
        }

        else if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                $messageId = (int)$controllerData[0];

                if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) $ownerAccess = IdentifyOwner($headers["Authorization"], $messageId, 'message');
                else $ownerAccess = ACCESS_DENIED;

                $message = $connect->query("SELECT * FROM `message` WHERE `Id` = $messageId");
                if ($message->num_rows == 0) {
                    Entity_Not_Found_404();
                }
                if ($ownerAccess == ACCESS_ALLOWED || $generalAccessLevel == ADMIN_ACCESS_LEVEL) {
                    printJSON($message);
                    exit();
                }
               Forbidden_403();
            } //  /users/{messageId}:  Get message (for both users and admin)
            BadRequest_400();
         }
    }

    if ($method === 'DELETE') {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                $messageId = (int)$controllerData[0];

                if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) $ownerAccess = IdentifyOwner($headers["Authorization"], $messageId, 'message');
                else $ownerAccess = ACCESS_DENIED;

                $message = $connect->query("SELECT * FROM `message` WHERE `Id` = $messageId");
                if ($message->num_rows == 0) {
                    Entity_Not_Found_404();
                }

                if ($generalAccessLevel == ADMIN_ACCESS_LEVEL || $ownerAccess == ACCESS_ALLOWED) {
                    $connect->query("DELETE FROM `message` WHERE `message`.`Id` = '$messageId '");
                    exit();
                }
                Forbidden_403();
            }
            else BadRequest_400();
        }
    }
}