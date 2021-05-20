<?php

require_once 'Classes/User.php';
require_once 'Classes/Message.php';

function directLogin($username, $password) {
    global $connect;
    $token = generateToken();
    $connect->query("UPDATE `user` SET `token` = '$token' WHERE `user`.`Username` = '$username' AND `user`.`Password` = '$password'");

    echo json_encode($token);
}
function createUser($requestData): User {
    if (isset($requestData->Birthday)) {
        $userTemp = new User(
            User::validateName($requestData->Name),
            User::validateSurname($requestData->Surname),
            User::validateUsername($requestData->Username),
            User::validatePassword($requestData->Password),
            User::validateBirthday($requestData->Birthday)
        );
    }
    else {
        $userTemp = new User(
            User::validateName($requestData->Name),
            User::validateSurname($requestData->Surname),
            User::validateUsername($requestData->Username),
            User::validatePassword($requestData->Password),
        );
    }
    if (isset($requestData->Avatar)) {
        global $connect;
        $photo = $connect->query("SELECT * FROM `photo` WHERE `photo`.`Link` = '$requestData->Avatar'");

        if ($photo->num_rows == 0) {
            Entity_Not_Found_404();
        }

        $userTemp->setAvatar($requestData->Avatar);
    }

    return $userTemp;
}



function route($method, $controllerData, $requestData) {
    global $connect;
    $headers = getallheaders();

    if (isset($headers["Authorization"])) $generalAccessLevel = GetGeneralAccessLevel($headers["Authorization"]);
    else $generalAccessLevel = UNAUTHORIZED_ACCESS_LEVEL;




    if ($method === 'GET') {

        if (Count($controllerData) === 0) {
            $user = $connect->query("SELECT * FROM `user`");

            if ($user->num_rows == 0) {
                Entity_Not_Found_404();
            }

            printUsers($user, $generalAccessLevel);
            exit();
        } //  /users/:  Get all users

        else if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                $userId = (int)$controllerData[0];
                $user = $connect->query("SELECT * FROM `user` WHERE `id` = $userId");
                printUsers($user, $generalAccessLevel);
                exit();
            } //  /users/{id}:  Get user information
        }

        else if (Count($controllerData) === 2) {

            if (is_numeric($controllerData[0])) {
                if ($controllerData[1] == 'posts') {
                    $Id = (int)$controllerData[0];
                    $user = $connect->query("SELECT * FROM `user` WHERE `user`.`Id` = '$Id'");

                    if ($user->num_rows == 0) {
                        Entity_Not_Found_404();
                    }

                    $userPosts = $connect->query("SELECT * FROM `post` WHERE `post`.`User_Id` = '$Id'");
                    printJSON($userPosts);
                    exit();
                } //  /users/{id}/posts:  Get all peoples with requested city

                if ($controllerData[1] == 'messages') {
                    if (Count($requestData) == 2) {
                        if (is_numeric($requestData["offset"]) && is_numeric($requestData["limit"])) {

                            $userStart_Id = (int)$controllerData[0];
                            $user = $connect->query("SELECT * FROM `user` WHERE `user`.`Id` = '$userStart_Id'");

                            if ($user->num_rows == 0) {
                                Entity_Not_Found_404();
                            }

                            $limit = $requestData["limit"];
                            $offset = $requestData["offset"];

                            $userMessages = $connect->query("SELECT * FROM `message` WHERE `message`.`UserStart_Id` = '$userStart_Id' OR `message`.`UserEnd_Id` = '$userStart_Id' 
                                                                                           ORDER BY `message`.`Date` LIMIT $offset, $limit");

                            printJSON($userMessages);
                            exit();
                        } //  /users/{userId}/messages/?offset={offsetCount}&limit={limitCount}:  Get range of a message list
                    }
                }
            }

            if ($controllerData[0] == "photos") {
                if (is_numeric($controllerData[1])) {
                    $userId = (int)$controllerData[1];

                    $user = $connect->query("SELECT * FROM `user` WHERE `Id` = '$userId'");

                    if ($user->num_rows == 0) {
                        Entity_Not_Found_404();
                    }

                    $photos = $connect->query("SELECT * FROM `photo` WHERE `User_Id` = '$userId'");
                    printJSON($photos);
                    exit();
                } // /photos:  Get user photos by userId
                BadRequest_400();
            }
        }
    }



    else if ($method === 'POST') {
        if (Count($controllerData) === 0) {
            if (($generalAccessLevel === UNAUTHORIZED_ACCESS_LEVEL || $generalAccessLevel === ADMIN_ACCESS_LEVEL)) {

                $model = createUser($requestData);
                $name = $model->getName();  // Не поиграешь тут особо в ООП даже
                $surname = $model->getSurname(); // mysqli_query() не терпит передаваемых ей значений в виде методов с возвращемым значением
                $password = $model->getPassword(); // Не понятно тогда зачем я все это делал (я про класс)... Ибо иных вариантов не наблюдаю
                $birthday = $model->getBirthday(); // Но пусть будет, красота ж
                $username = $model->getUsername(); // Дурацкая пыха, ъуъ

                CheckingForUniqueName($username, 'user', 'Username');

                if (is_null($birthday)) {
                    $connect->query("INSERT INTO `user` (`Id`, `Name`, `Surname`, `Password`, `Birthday`, `Avatar`, `Status`, `Username`, `Token`, `City_Id`, `Role_Id`)
                    VALUES (NULL, '$name', '$surname', '$password', NULL, NULL, NULL, '$username', NULL, NUll, NULL)");
                }
                else {
                    $connect->query("INSERT INTO `user` (`Id`, `Name`, `Surname`, `Password`, `Birthday`, `Avatar`, `Status`, `Username`, `Token`, `City_Id`, `Role_Id`)
                    VALUES (NULL, '$name', '$surname', '$password', '$birthday', NULL, NULL, '$username', NULL, NUll, NULL)");
                }

                if ($generalAccessLevel == UNAUTHORIZED_ACCESS_LEVEL) { // If request from unauthorized user -> direct login (return json token)
                    directLogin($model->getUsername(), $model->getPassword());
                }
                exit();
            }
        } // Register a new user (by unauthorized user or admin only)

        else if (Count($controllerData) === 2) {
            if (is_numeric($controllerData[0])) {
                if ($controllerData[1] == 'messages') {
                    if (($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL)) {
                        $userStartId = getUserId($headers["Authorization"]);

                        $userEndId = (int)$controllerData[0];
                        $userEnd = $connect->query("SELECT * FROM `user` WHERE `user`.`Id` = '$userEndId'");

                        if ($userEnd->num_rows == 0) {
                            Entity_Not_Found_404();
                        }

                        $model = new Message(
                            Message::validateText($requestData->Text),
                            date('Y-m-d h:i:s', time())
                        );


                        $text = $model->getText();
                        $date = $model->getDate();

                        $connect->query("INSERT INTO `message` (`Id`, `Text`, `Date`, `UserStart_Id`, `UserEnd_Id`) VALUES (NULL, '$text', '$date', '$userStartId', '$userEndId')");
                        exit();
                    }
                    Forbidden_403();
                } //  /users/{userId}/messages:  Send message
                BadRequest_400();
            }
        }
    }



    else if ($method === 'PATCH') {

        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {

                if (isset($headers["Authorization"])) $ownerAccess = IdentifyOwner($headers["Authorization"], (int)$controllerData[0], 'user');
                else $ownerAccess = ACCESS_DENIED;

                if ($generalAccessLevel == ADMIN_ACCESS_LEVEL || $ownerAccess == ACCESS_ALLOWED) {

                    $userId = (int)$controllerData[0];
                    $user = $connect->query("SELECT * FROM `user` WHERE `user`.`id` = '$userId'");

                    if ($user->num_rows == 0) {
                        Entity_Not_Found_404();
                    }

                    $model = createUser($requestData);
                    $name = ($model->getName() === "") ? $user->fetch_array()['Name'] : $model->getName();                 //
                    $surname = ($model->getSurname() === "") ? $user->fetch_array()['Surname'] : $model->getSurname();     //
                    $password = ($model->getPassword() === "") ? $user->fetch_array()['Password'] : $model->getPassword(); // if we've got "", dont update this field
                    $birthday = ($model->getBirthday() === "") ? $user->fetch_array()['Birthday'] : $model->getBirthday(); //
                    $username = ($model->getUsername() === "") ? $user->fetch_array()['Username'] : $model->getUsername(); //
                    $avatar = ($model->getAvatar() === "") ? $user->fetch_array()['Avatar'] : $model->getAvatar(); //
                    CheckingForUniqueName($username, 'user', 'Username', $userId);

                    if (is_null($birthday)) {
                        $connect->query("UPDATE `user` SET `Name` = '$name',`Username` = '$username',`Surname` = '$surname', 
                                        `Password` = '$password', `Birthday` = NULL, `Avatar` = '$avatar' WHERE `user`.`Id` = '$userId'");
                    }
                    else {
                        $connect->query("UPDATE `user` SET `Name` = '$name',`Username` = '$username',`Surname` = '$surname', 
                                        `Password` = '$password', `Birthday` = '$birthday', `Avatar` = '$avatar' WHERE `user`.`Id` = '$userId'");
                    }

                    printUsers($connect->query("SELECT * FROM `user` WHERE `user`.`id` = '$userId'"), $generalAccessLevel);
                    exit();
                } // Access is allowed
                Forbidden_403(); // Access is denied
            } // Edit existing user by Id (by user for yourself or admin)
        }

        else if (Count($controllerData) === 2) {
            if (is_numeric($controllerData[0])) {

                if ($controllerData[1] == 'city') {
                    if (is_numeric($requestData->CityId)) {
                        $userId = (int)$controllerData[0];

                        if (isset($headers["Authorization"])) $ownerAccess = IdentifyOwner($headers["Authorization"], $userId, 'user');
                        else $ownerAccess = ACCESS_DENIED;

                        if ($generalAccessLevel == ADMIN_ACCESS_LEVEL || $ownerAccess == ACCESS_ALLOWED) {
                            $cityId = ($connect->query("SELECT * FROM `city` WHERE `city`.`Id` = '$requestData->CityId'"))->fetch_assoc()["Id"] ?? null;

                            if ($cityId == null) {
                                Entity_Not_Found_404();
                            }

                            $connect->query("UPDATE `user` SET `City_Id` = '$cityId' WHERE `user`.`Id` = '$userId'");
                            exit();
                        } // Access is allowed

                        Forbidden_403();
                    }
                    BadRequest_400();
                }   //  /users/{id}/city: Set city to user by Id (by user for yourself or admin)

                if ($controllerData[1] == 'status') {
                    if (in_array($requestData->Status, STATUS, true)) {
                        $userId = (int)$controllerData[0];

                        if (isset($headers["Authorization"])) $ownerAccess = IdentifyOwner($headers["Authorization"], $userId, 'user');
                        else $ownerAccess = ACCESS_DENIED;

                        if ($generalAccessLevel == ADMIN_ACCESS_LEVEL || $ownerAccess == ACCESS_ALLOWED) {
                            $connect->query("UPDATE `user` SET `Status` = '$requestData->Status' WHERE `user`.`Id` = '$userId'");
                            exit();
                        } // Access is allowed
                        Forbidden_403();
                    }
                    BadRequest_400();
                } //  /users/{id}/status: Set status to user by Id (by user for yourself or admin)

                if ($controllerData[1] == 'role') {
                    if (is_numeric($requestData->RoleId)) {
                        $userId = (int)$controllerData[0];
                        CheckUserExistence($userId); // Will terminate if user doesn't exist

                        if ($generalAccessLevel == ADMIN_ACCESS_LEVEL) {
                            $roleId = ($connect->query("SELECT * FROM `role` WHERE `role`.`Id` = '$requestData->RoleId'"))->fetch_assoc()["Id"] ?? null;

                            if ($roleId == null) {
                                Entity_Not_Found_404();
                            } // Role doesn't exist

                            $connect->query("UPDATE `user` SET `Role_Id` = '$roleId' WHERE `user`.`Id` = '$userId'");
                            exit();
                        }
                        Forbidden_403();
                    }
                    BadRequest_400();
                }   //  /users/{id}/role: Set status to user by Id (admin only)

            }
        }

    }



    else if ($method === 'DELETE') {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                $userId = (int)$controllerData[0];
                CheckUserExistence($userId); // Will terminate if user doesn't exist

                if ($generalAccessLevel == ADMIN_ACCESS_LEVEL) {
                    $connect->query("DELETE FROM `user` WHERE `user`.`Id` = $userId");
                    exit();
                }
                Forbidden_403();
            } //  /users/{id}: Delete user by Id (admin only)
            else BadRequest_400();
        }
    }

}