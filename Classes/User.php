<?php

class User
{
    public string $Name;
    public string $Surname;
    public string $Username;
    public string $Password;
    public string $Birthday;

    function setBirthday($date) : bool {
        if (strlen($date) == 10) {
            preg_match('/((([1][9][0-9][0-9])|([2][0][0-1][0-5]))\-([0][1-9]|([1][0-2]))\-([0][1-9]|([1][0-2])))/', $date, $matches);
            if (isset($matches[0])) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
}
