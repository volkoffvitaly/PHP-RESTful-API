<?php
require_once 'Classes/City.php';


function route($method, $controllerData, $requestData)
{
    global $connect;
    $headers = getallheaders();

    if (isset($headers["Authorization"])) $generalAccessLevel = GetGeneralAccessLevel($headers["Authorization"]);
    else $generalAccessLevel = UNAUTHORIZED_ACCESS_LEVEL;



    if ($method == 'GET') {

        if (Count($controllerData) === 0) {
            $cities = $connect->query("SELECT * FROM `city`");
            printJSON($cities);
            exit();
        } //   /cities:  Get all cities

        else if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                $cityId = (int)$controllerData[0];
                $city = $connect->query("SELECT * FROM `city` WHERE `id` = $cityId");

                if ($city->num_rows == 0) {
                    Entity_Not_Found_404();
                }

                printJSON($city);
                exit();
            } //  /cities/{id}:  Get a single city by Id
        }

        else if (Count($controllerData) === 2) {
            if (is_numeric($controllerData[0])) {
                if ($controllerData[1] == 'peoples') {
                    $cityId = (int)$controllerData[0];
                    $city = $connect->query("SELECT * FROM `city` WHERE `city`.`Id` = '$cityId'");

                    if ($city->num_rows == 0) {
                        Entity_Not_Found_404();
                    } // City doesn't exist

                    $users = $connect->query("SELECT * FROM `user` WHERE `user`.`City_Id` = '$cityId'");

                    printUsers($users, $generalAccessLevel);
                    exit();
                } //  /cities/{id}/peoples:  Get all peoples with requested city
            }
        }
    }


    else if ($method == 'POST') {
        if (Count($controllerData) === 0) {
            if ($generalAccessLevel == ADMIN_ACCESS_LEVEL) {

                $model = new City(
                    City::validateName($requestData->Name)
                );

                CheckingForUniqueName($model->getName(), 'city', 'Name');

                $name = $model->getName();
                $connect->query("INSERT INTO `city` (`Id`, `Name`) VALUES (NULL, '$name')");
                exit();
            }
        } // /cities: Create a new city (Admin only)
    }


    else if ($method == 'PATCH') {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                if ($generalAccessLevel == ADMIN_ACCESS_LEVEL) {

                    $cityId = (int)$controllerData[0];
                    $city = $connect->query("SELECT * FROM `city` WHERE `city`.`Id` = '$cityId'"); // find user to patch

                    if ($city->num_rows == 0) {
                        Entity_Not_Found_404();
                    } // city not found


                    $model = new City(
                        City::validateName($requestData->Name)
                    );

                    $name = ($model->getName() === "") ? $city->fetch_array()['Name'] : $model->getName();
                    CheckingForUniqueName($model->getName(), 'city', 'Name', $cityId);

                    $connect->query("UPDATE `city` SET `Name` = '$name' WHERE `city`.`Id` = '$cityId'");
                }
            } // /cities/{id}: Update an existing city (Admin only)
        }
    }


    else if ($method == 'DELETE') {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                if ($generalAccessLevel == ADMIN_ACCESS_LEVEL) {

                    $cityId = (int)$controllerData[0];
                    $city = $connect->query("SELECT * FROM `city` WHERE `city`.`Id` = '$cityId'"); // find user to patch

                    if ($city->num_rows == 0) {
                        Entity_Not_Found_404();
                    } // city not found

                    $connect->query("DELETE FROM `city` WHERE `city`.`Id` = '$cityId'");
                }
            } // /cities/{id}: Delete an existing city (Admin only)
        }
    }

}