<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Store the new user in 'users.txt'
    $usersFile = 'users.txt';
    $file = fopen($usersFile, 'a');
    fwrite($file, "$login|$hashedPassword\n");
    fclose($file);

    echo "Registration successful. <a href='index.php'>Return to login</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <form action="register.php" method="post">
        <label for="login">Login:</label>
        <input type="text" name="login" required><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br>

        <button type="submit">Register</button>
    </form>
</body>
</html>
