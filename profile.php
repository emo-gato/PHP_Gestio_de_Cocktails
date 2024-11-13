<?php
session_start();

$isLoggedIn = isset($_SESSION['login']);
if (!$isLoggedIn) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['login'];
$userDirectory = 'users'; // Define the users directory
$userFile = $userDirectory . '/' . $username . '.txt';

// Check if the 'users' directory exists; if not, create it
if (!is_dir($userDirectory)) {
    mkdir($userDirectory, 0777, true); // Create the directory with recursive flag and permissions
}

// Check if the user file exists; if not, create it with default data
if (!file_exists($userFile)) {
    $userData = [
        'name' => '',
        'surname' => '',
        'gender' => '',
        'birthdate' => ''
    ];
    // Create the file with default empty user data
    file_put_contents($userFile, json_encode($userData));
} else {
    // Load user data from existing file
    $userData = json_decode(file_get_contents($userFile), true);
}

$name = isset($userData['name']) ? $userData['name'] : '';
$surname = isset($userData['surname']) ? $userData['surname'] : '';
$gender = isset($userData['gender']) ? $userData['gender'] : '';
$birthdate = isset($userData['birthdate']) ? $userData['birthdate'] : '';

$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];

    // Validate birthdate (user must be 18+)
    $birthdateTimestamp = strtotime($birthdate);
    if ($birthdateTimestamp && time() - $birthdateTimestamp < 18 * 365 * 24 * 60 * 60) {
        $errorMessage = "Vous devez avoir 18 ans ou plus.";
    } else {
        // Update user data
        $userData['name'] = $name;
        $userData['surname'] = $surname;
        $userData['gender'] = $gender;
        $userData['birthdate'] = $birthdate;

        // Save changes to the file
        file_put_contents($userFile, json_encode($userData));
        $successMessage = "Les informations ont été mises à jour avec succès.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profil Utilisateur</title>
</head>
<body>
    <h1>Mon Profil</h1>

    <?php if ($successMessage): ?>
        <p style="color:green;"><?php echo $successMessage; ?></p>
    <?php elseif ($errorMessage): ?>
        <p style="color:red;"><?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <form method="POST" action="profile.php">
        <label for="name">Nom:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required><br>

        <label for="surname">Prénom:</label>
        <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($surname); ?>" required><br>

        <label for="gender">Sexe:</label>
        <select id="gender" name="gender" required>
            <option value="homme" <?php echo $gender === 'homme' ? 'selected' : ''; ?>>Homme</option>
            <option value="femme" <?php echo $gender === 'femme' ? 'selected' : ''; ?>>Femme</option>
        </select><br>

        <label for="birthdate">Date de naissance:</label>
        <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" required><br>

        <button type="submit">Mettre à jour</button>
    </form>

    <a href="logout.php">Se déconnecter</a>
</body>
</html>
