<?php

function generateToken(): String {
    return bin2hex(random_bytes(31));
}
