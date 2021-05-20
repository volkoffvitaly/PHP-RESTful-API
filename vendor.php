<?php
$connect = new mysqli("localhost", "mysql", "mysql", 'lab7');

if ($connect->connect_error){
    echo '<br>' . '<b>Server Log</b> ' .  '<br>';
    echo 'Error number: ' . $connect->connect_errno . '<br>';
    echo $connect->connect_error . '<br>';
    http_response_code(504);
    return;
} // catching some errors with connect to a DB


function ConfigureDB() {
    global $connect;
    $adminRoleName = ADMIN_ROLE_NAME;

    ConfigureRole(ADMIN_ROLE_NAME);
    ConfigureRole(MODERATOR_ROLE_NAME);
    ConfigureRole(USER_ROLE_NAME);

    $adminRoleId = mysqli_fetch_array($connect->query("SELECT `Id` FROM `role` WHERE `role`.`Name` = '$adminRoleName'"));
    $admin = $connect->query("SELECT * FROM `user` WHERE `user`.`Role_Id` = '$adminRoleId[0]'");

    if($admin->num_rows == 0) {
        $token = generateToken();
        $connect->query("INSERT INTO `user` (`Id`, `Name`, `Surname`, `Password`, `Birthday`, `Avatar`, `Status`, `Username`, `Token`, `City_Id`, `Role_Id`) 
                               VALUES (NULL, 'Main', 'Admin', '123456789', NULL, NULL, 'Want to die', 'Admin', '$token', NUll, '$adminRoleId[0]')");
    }
}

function ConfigureRole($roleName): void {
    global $connect;
    $role = $connect->query("SELECT * FROM `role` WHERE `role`.`Name` = '$roleName'"); // is exist such role?
    if ($role->num_rows == 0) {
        $connect->query("INSERT INTO `role` (`Id`, `Name`) VALUES (NULL, '$roleName')");
    } // Creating role if it doesn't exists
}

function GetGeneralAccessLevel($bearerToken): Int {
    global $connect;

    if (isset($bearerToken)) {
        $token = str_replace("Bearer ", "", $bearerToken);
        $authorizedUser = mysqli_fetch_array($connect->query("SELECT * FROM `user` WHERE `user`.`token` = '$token'"));
        if (isset($authorizedUser)) {
            $Role_Id = mysqli_fetch_array($connect->query("SELECT Role_Id FROM `user` WHERE `user`.`token` = '$token'"))[0];
            if (isset($Role_Id)) {
                $Role_Name = mysqli_fetch_array($connect->query("SELECT `Name` FROM `role` WHERE `role`.`id` = '$Role_Id'"))[0];
                if (ADMIN_ROLE_NAME === $Role_Name) {
                    return ADMIN_ACCESS_LEVEL;
                }
                else if (MODERATOR_ROLE_NAME === $Role_Name) {
                    return MODERATOR_ACCESS_LEVEL;
                }
                else if (USER_ROLE_NAME === $Role_Name) {
                    return USER_ACCESS_LEVEL;
                }
            }
            return USER_ACCESS_LEVEL; // Such role doesn't contain special rights or wasn't declare any role for user
        }
        else {
            return UNAUTHORIZED_ACCESS_LEVEL; // DB doesn't contain a token
        }
    }

    return UNAUTHORIZED_ACCESS_LEVEL; // Header doesn't contain a token
}

function IdentifyOwner($bearerToken, $id, $entityType): bool {
    global $connect;

    if (isset($bearerToken)) {
        $token = str_replace("Bearer ", "", $bearerToken);
        $userId = ($connect->query("SELECT * FROM `user` WHERE `user`.`Token` = '$token'"))->fetch_assoc()["Id"] ?? null;

        if ($entityType == 'user') {
            if ($id == $userId) {
                return ACCESS_ALLOWED;
            }
        }
        else if ($entityType == 'post') {
            $posts = $connect->query("SELECT * FROM `post` WHERE `post`.`User_Id` = '$userId'");

            $postIds = array();
            while($row = $posts->fetch_object()) {
                array_push($postIds, $row->Id);
            }

            if (in_array($id, $postIds)) {
                return ACCESS_ALLOWED;
            }
        }
        else if ($entityType == 'message') {
            $messages = $connect->query("SELECT * FROM `message` WHERE `message`.`UserStart_Id` = '$userId' OR `message`.`UserEnd_Id` = '$userId'");

            $messageIds = array();
            while($row = $messages->fetch_object()) {
                array_push($messageIds, $row->Id);
            }

            if (in_array($id, $messageIds)) {
                return ACCESS_ALLOWED;
            }
        }
        else if ($entityType == 'photo') {
            $photos = $connect->query("SELECT * FROM `photo` WHERE `photo`.`User_Id` = '$userId'");

            $photoIds = array();
            while($row = $photos->fetch_object()) {
                array_push($photoIds, $row->Id);
            }

            if (in_array($id, $photoIds)) {
                return ACCESS_ALLOWED;
            }
        }
    }

    return ACCESS_DENIED; // Header doesn't contain a token or requester is not an owner
}

function getUserId($bearerToken): ?int {
    global $connect;

    if (isset($bearerToken)) {
        $token = str_replace("Bearer ", "", $bearerToken);
        $userId = ($connect->query("SELECT * FROM `user` WHERE `user`.`Token` = '$token'"))->fetch_assoc()["Id"] ?? null;

        if ($userId != NULL) {
            return $userId;
        }
    }

    return NULL;
}

function getUserRole($user): ?string {
    global $connect;

    $userToken = $user["Token"];
    $Role_Id = mysqli_fetch_array($connect->query("SELECT Role_Id FROM `user` WHERE `user`.`token` = '$userToken'"))["Role_Id"];
    if (isset($Role_Id)) {
        $Role_Name = mysqli_fetch_array($connect->query("SELECT `Name` FROM `role` WHERE `role`.`id` = '$Role_Id'"))["Name"];
        if (isset($Role_Name)) {
            return $Role_Name;
        }
    }
    return NULL;
}

function getUserCity($user): ?string {
    global $connect;

    $userToken = $user["Token"];
    $City_Id = mysqli_fetch_array($connect->query("SELECT City_Id FROM `user` WHERE `user`.`token` = '$userToken'"))["City_Id"];
    if (isset($City_Id)) {
        $City_Name = mysqli_fetch_array($connect->query("SELECT `Name` FROM `city` WHERE `city`.`id` = '$City_Id'"))["Name"];
        if (isset($City_Name)) {
            return $City_Name;
        }
    }
    return NULL;
}

function CheckingForUniqueName($name, $tableName, $fieldName, $id = null): void {
    global $connect;

    $similarCity = $connect->query("SELECT * FROM `$tableName` WHERE `$tableName`.`$fieldName` = '$name'");
    if ($similarCity->num_rows != 0) {
        if ($similarCity->num_rows == 1 && $id != NULL) {
            if ($similarCity->fetch_array()['Id'] == $id) {
                return;
            }
        } // to patch picked city

        http_response_code(409);
        echo json_encode([
            'status' => false,
            'message' => $tableName . ' with such Name already exists.'
        ]);
        exit();
    }
}

function CheckUserExistence($id): void {
    global $connect;
    $user = $connect->query("SELECT * FROM `user` WHERE `user`.`Id` = '$id'");

    if ($user->num_rows == 0) {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'message' => 'User with such Id doesnt exists.'
        ]);
        exit();
    }
}