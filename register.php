<?php
session_start(); // Start the session to store login information

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if the user already exists
    $users_file = 'users.txt';
    $userExists = false;

    if (file_exists($users_file)) {
        $users = file($users_file, FILE_IGNORE_NEW_LINES);
        foreach ($users as $user) {
            $storedLogin = explode('|', $user)[0];
            if ($storedLogin === $login) {
                $userExists = true;
                break;
            }
        }
    }

    if ($userExists) {
        header("Location: index.php?action=register&error=user_exists");
        exit;
    }

    // Add the user to the file
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    file_put_contents($users_file, "$login|$hashedPassword\n", FILE_APPEND);

    // Log the user in by setting the session variable
    $_SESSION['login'] = $login;

    // Redirect to the cocktails page (or profile page if needed)
    header("Location: index.php?action=cocktails");
    exit;
}
    