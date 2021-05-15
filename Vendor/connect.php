<?php
    $connect = mysqli_connect('localhost', 'mysql', 'mysql', 'lab7');

    if (!$connect) {
        //echo "Ошибка: Невозможно установить соединение с MySQL." . PHP_EOL;
        //echo "Код ошибки errno: " . mysqli_connect_errno() . PHP_EOL;
        //echo "Текст ошибки error: " . mysqli_connect_error() . PHP_EOL;
        //exit;
    }
