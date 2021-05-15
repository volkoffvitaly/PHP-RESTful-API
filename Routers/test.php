<?php

$url = rtrim((isset($_GET['q'])) ? $_GET['q'] : '', '/');
die($url . ' test.s');