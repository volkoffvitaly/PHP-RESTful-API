<?php


function route($method, $controllerData, $requestData) {
    global $connect;
    $headers = getallheaders();

    if (isset($headers["Authorization"])) $generalAccessLevel = GetGeneralAccessLevel($headers["Authorization"]);
    else $generalAccessLevel = UNAUTHORIZED_ACCESS_LEVEL;


    if ($method == "GET") {
        if (Count($controllerData) === 0) {
            if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) {
                $userId = getUserId($headers["Authorization"]);
                $photos = $connect->query("SELECT * FROM `photo` WHERE `User_Id` = '$userId'");
                printJSON($photos);
                exit();
            }
            Forbidden_403();
        } // /photos:  Get user photos (authorized only)
    }


    if ($method == "POST") {
        if (Count($controllerData) === 0) {
            if (Count($requestData) == 1) {
                if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) {
                    $userId = getUserId($headers["Authorization"]);

                    $detectedType = exif_imagetype($requestData["photo"]["tmp_name"]);

                    if (in_array($detectedType, ALLOWED_PHOTO_TYPES) && $requestData["photo"]["error"] == UPLOAD_ERR_OK) {

                        $name = htmlspecialchars(basename($requestData["photo"]["name"]));
                        $path = "Uploads/Photos/" . time() . $name;
                        if (move_uploaded_file($requestData["photo"]["tmp_name"], $path)) {
                            $connect->query("INSERT INTO `photo` (`Id`, `Link`, `User_Id`) VALUES (NULL, '$path', '$userId')");
                            $photo = $connect->query("SELECT * FROM `photo` WHERE `Link` = '$path'");
                            printJSON($photo);
                            exit();
                        }
                        Internal_Server_Error_500();
                    }
                    BadRequest_400();
                }
                Forbidden_403();
            } //  /photos:  Upload photo (authorized only)
        }
    }

    if ($method == "DELETE") {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                $photoId = (int)$controllerData[0];

                if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) $ownerAccess = IdentifyOwner($headers["Authorization"], $photoId, 'photo');
                else $ownerAccess = ACCESS_DENIED;

                $photo = $connect->query("SELECT * FROM `photo` WHERE `Id` = $photoId");
                if ($photo->num_rows == 0) {
                    Entity_Not_Found_404();
                }

                if ($generalAccessLevel == ADMIN_ACCESS_LEVEL || $ownerAccess == ACCESS_ALLOWED) {
                    $link = mysqli_fetch_array($connect->query("SELECT * FROM `photo` WHERE `photo`.`Id` = '$photoId '"));
                    $connect->query("DELETE FROM `photo` WHERE `photo`.`Id` = '$photoId '");
                   unlink($link["Link"]);
                    exit();
                }
                Forbidden_403();
            }
            else BadRequest_400();
        }
    }
}