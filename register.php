<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];
    
    //check if user exists
    $users_file = fopen("users.txt", "r");
    $exists = false;
    if ($users_file)
    {
        while (($line = fgets($users_file)) !== false)
        {
            $regex = '/^(.*?)\|/';
            if (preg_match($regex, $line, $matches))
                if($login === $matches[1])
                {
                    $exists = true;
                    break;
                }
        }
        fclose($users_file);
    }

    if ($exists) 
    {
        echo "Registration failed; Username already exists";
    }
    else
    {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        
        // Store the new user in 'users.txt'
        $users_file = fopen("users.txt", "w");
        fwrite($users_file, "$login|$hashedPassword\n");
        fclose($users_file);

        echo "Registration successful. <a href='index.php'>Return to login</a>";
    } 
}
$isLoggedIn = isset($_SESSION['login']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>

<header>
        <nav class="navbar">
            <div class="nav-buttons">
                <a href="index.php?reset=true" class="nav-button">Navigation</a>
                <a href="index.php?favorites=true" class="nav-button">Recettes ❤️</a>
            </div>
            <div class="search-container">
                <form action="index.php" method="POST">
                    <label for="search">Recherche :</label>
                    <input type="text" id="searchString" name="searchString" placeholder="Rechercher..." required>
                    <button type="submit" class="search-button">🔍</button>
                </form>
            </div>
            <div class="login-zone">
                <?php if ($isLoggedIn): ?>
                    <span><?php echo htmlspecialchars($_SESSION['login']); ?></span>
                    <a href="profile.php">Profil</a>
                    <a href="logout.php" class="logout-link">Se déconnecter</a>
                <?php else: ?>
                    <form action="login.php" method="post" class="login-form">
                        <input type="text" name="login" placeholder="Login" required>
                        <input type="password" name="password" placeholder="Mot de passe" required>
                        <button type="submit" class="login-button">Connexion</button>
                    </form>
                    <a href="register.php" class="register-link">S'inscrire</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

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
