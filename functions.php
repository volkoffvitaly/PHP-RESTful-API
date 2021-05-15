<?php
function getDataByForm($method) {
    if ($method === 'GET') return $_GET;
    if ($method === 'POST' && !empty($_POST)) return $_POST;

    $incomingData = file_get_contents('php://input');
    $decodedJSON = json_decode($incomingData);
    if ($decodedJSON)
    {
        $data = $decodedJSON;
    }
    else
    {
        $data = array();
        $exploded = explode('&', file_get_contents('php://input'));
        foreach($exploded as $pair)
        {
            $item = explode('=', $pair);
            if (count($item) == 2)
            {
                $data[urldecode($item[0])] = urldecode($item[1]);
            }
        }
    }
    return $data;
}