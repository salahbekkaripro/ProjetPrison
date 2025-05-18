<?php
session_start();
$input = trim($_POST['captcha'] ?? '');
if (strtolower($input) === strtolower($_SESSION['captcha_answer'] ?? '')) {
    echo 'OK';
} else {
    echo 'ERROR';
}

