<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];

    // File with user credentials
    $usersFile = 'users.txt';
    $isAuthenticated = false;

    if (file_exists($usersFile)) {
        $file = fopen($usersFile, 'r');
        while (($line = fgets($file)) !== false) {
            list($storedLogin, $storedPasswordHash) = explode('|', trim($line));
            if ($storedLogin === $login && password_verify($password, $storedPasswordHash)) {
                $isAuthenticated = true;
                break;
            }
        }
        fclose($file);
    }

    if ($isAuthenticated) {
        $_SESSION['login'] = $login;
        header("Location: index.php");
        exit;
    } else {
        echo "Invalid login or password.";
    }
}
?>
