<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
$isLoggedIn = isset($_SESSION['login']);

if (!$isLoggedIn) {
    header("Location: index.php"); // Redirect to index.php if not logged in
    exit;
}

$username = htmlspecialchars(trim($_SESSION['login'])); // Sanitize username
$userDirectory = 'users'; // Define the users directory
$userFile = $userDirectory . '/' . $username . '.txt';

// Check if the 'users' directory exists; if not, create it
if (!is_dir($userDirectory)) {
    if (!mkdir($userDirectory, 0777, true) && !is_dir($userDirectory)) {
        die("Failed to create user directory.");
    }
}

// Check if the user file exists; if not, create it with default data
if (!file_exists($userFile)) {
    $userData = [
        'name' => '',
        'surname' => '',
        'gender' => '',
        'birthdate' => ''
    ];
    file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
} else {
    // Load user data from existing file
    $userData = json_decode(file_get_contents($userFile), true);
}

$name = isset($userData['name']) ? $userData['name'] : '';
$surname = isset($userData['surname']) ? $userData['surname'] : '';
$gender = isset($userData['gender']) ? $userData['gender'] : '';
$birthdate = isset($userData['birthdate']) ? $userData['birthdate'] : '';

$profilemessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];

    // Validate names (only letters allowed)
    if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $name)) {
        $profilemessage = "Le nom ne peut contenir que des lettres et des espaces.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $surname)) {
        $profilemessage = "Le prénom ne peut contenir que des lettres et des espaces.";
    } else {
        // Validate birthdate (user must be 18+)
        $birthdateTimestamp = strtotime($birthdate);
        if ($birthdateTimestamp && time() - $birthdateTimestamp < 18 * 365 * 24 * 60 * 60) {
            $profilemessage = "Vous devez avoir 18 ans ou plus.";
        } else {
            // Update user data
            $userData['name'] = $name;
            $userData['surname'] = $surname;
            $userData['gender'] = $gender;
            $userData['birthdate'] = $birthdate;

            // Save changes to the file
            if (file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
                $profilemessage = "Échec de la mise à jour des informations.";
            } else {
                $profilemessage = "Les informations ont été mises à jour avec succès.";
            }
        }
    }
    header("Location: index.php?action=profile&message=" . urlencode($profilemessage));
    exit;
}
?>
