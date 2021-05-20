<?php


function BadRequest_400() {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Another data format expected.'
    ]);
    exit();
}

function Forbidden_403() {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => 'Access is denied.'
    ]);
    exit();
}

function Entity_Not_Found_404() {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => 'This object doesnt exists yet.'
    ]);
    exit();
}

function Internal_Server_Error_500() {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Internal Server Error. Try later.'
    ]);
    exit();
}
