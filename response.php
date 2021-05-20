<?php

function printUsers($users, $generalAccessLevel) {
    if ($users->num_rows > 0) {
        $array = array();
        while ($row = $users->fetch_assoc()) {
            $obj = new stdClass;
            $obj->Id = $row['Id'];
            $obj->Name = $row['Name'];
            $obj->Surname = $row['Surname'];
            $obj->Status = $row['Status'];
            $obj->City = getUserCity($row);
            if ($generalAccessLevel === ADMIN_ACCESS_LEVEL) {
                $obj->Birthday = $row['Birthday'];
                $obj->Role = getUserRole($row);
            }
            array_push($array, $obj);
        }
        echo json_encode($array);
    }
}

function printJSON($entities) {
    if ($entities->num_rows > 0) {
        $array = array();
        while ($row = $entities->fetch_assoc()) {
            array_push($array, $row);
        }
        echo json_encode($array);
    }
}

