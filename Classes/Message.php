<?php

class Message
{
    private string $Text;
    private string $Date;

    public function __construct(string $text, string $date) {
        $this->Text = $text;
        $this->Date = $date;
    }

    public static function validateText($text): string {
        if (is_string($text) && 10 <= strlen($text) && strlen($text) <= 1000) {
            return $text;
        }

        http_response_code(400);
        echo json_encode([
            'status' => false,
            'message' => 'Text format is invalid. It must be a string and contain at least 10 and no more than 1000 characters.'
        ]);
        die();
    }
    public function getText(): string {
        return $this->Text;
    }
    public function getDate(): string {
        return $this->Date;
    }
}
