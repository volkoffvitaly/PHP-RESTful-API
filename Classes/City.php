<?php

class City
{
    private string $Name;

    public function __construct(string $name) {
        $this->Name = $name;
    }

    public static function validateName($name): string {
        if (is_string($name) && strlen($name) <= 127) {
            return $name;
        }

        http_response_code(400);
        echo json_encode([
            'status' => false,
            'message' => 'city Name format is invalid. It must be a string and contain exactly or less than to 63 characters.'
        ]);
        die();
    }
    public function getName(): string {
        return $this->Name;
    }
}
