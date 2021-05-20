<?php
require_once 'Classes/Role.php';



function route($method, $controllerData, $requestData)
{
    global $connect;
    $headers = getallheaders();


    if (isset($headers["Authorization"])) $generalAccessLevel = GetGeneralAccessLevel($headers["Authorization"]);
    else $generalAccessLevel = UNAUTHORIZED_ACCESS_LEVEL;




    if ($method == 'GET') {
        if (Count($controllerData) === 0) {
            $roles = $connect->query("SELECT * FROM `role`");
            printJSON($roles);
            exit();
        } // Get all roles

        else if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                $roleId = (int)$controllerData[0];
                $role = $connect->query("SELECT * FROM `role` WHERE `id` = $roleId");

                if ($role->num_rows == 0) {
                    Entity_Not_Found_404();
                }

                printJSON($role);
                exit();
            } // Get a single role by Id
        }
    }



    else if ($method == 'POST') {
        if (Count($controllerData) === 0) {
            if ($generalAccessLevel == ADMIN_ACCESS_LEVEL) {
                $model = new Role(
                    Role::validateName($requestData->Name)
                );

                CheckingForUniqueName($model->getName(), "role", "Name");

                $name = $model->getName();
                $connect->query("INSERT INTO `role` (`Id`, `Name`) VALUES (NULL, '$name')");
                exit();
            }
            else BadRequest_400();
        } // /roles: Create a new role (Admin only)
    }



    else if ($method == 'PATCH') {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                if ($generalAccessLevel == ADMIN_ACCESS_LEVEL) {

                    $roleId = (int)$controllerData[0];
                    $role = $connect->query("SELECT * FROM `role` WHERE `role`.`Id` = '$roleId'");

                    if ($role->num_rows == 0) {
                        Entity_Not_Found_404();
                    }

                    if (in_array($role->fetch_array()['Name'], SYSTEM_ROLES)) {
                        Forbidden_403();
                    } // exclude system roles

                    $model = new Role(
                        Role::validateName($requestData->Name)
                    );

                    $name = ($model->getName() === "") ? $role->fetch_array()['Name'] : $model->getName();
                    CheckingForUniqueName($name, "role", "Name", $roleId);

                    $connect->query("UPDATE `role` SET `Name` = '$name' WHERE `role`.`Id` = '$roleId'");
                    exit();
                }
                else Forbidden_403();
            } // /roles/{id}: Update an existing role (Admin only)
        }
    }



    else if ($method == 'DELETE') {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                if ($generalAccessLevel == ADMIN_ACCESS_LEVEL) {

                    $roleId = (int)$controllerData[0];
                    $role = $connect->query("SELECT * FROM `role` WHERE `role`.`Id` = '$roleId'");

                    if ($role->num_rows == 0) {
                        Entity_Not_Found_404();
                    }

                    if (in_array($role->fetch_array()['Name'], SYSTEM_ROLES)) {
                        Forbidden_403();
                    } // exclude system roles

                    $connect->query("DELETE FROM `role` WHERE `role`.`Id` = '$roleId'");
                    exit();
                }
                Forbidden_403();
            }
        } // /roles/{id}: Delete an existing role (Admin only)
    }

}