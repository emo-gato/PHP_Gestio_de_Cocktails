<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include('users.php'); // Include the global user data

$isLoggedIn = isset($_SESSION['login']);

if (!$isLoggedIn) {
    header("Location: index.php"); // Redirect to index.php if not logged in
    exit;
}

$currentUser = htmlspecialchars(trim($_SESSION['login'])); // Sanitize username
$profile = &$Users[$currentUser]['profile']; // Reference user's profile

$responseMessage = ''; // Feedback message for the user

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];

    // Validate inputs
    if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $name)) {
        $responseMessage = "Le nom ne peut contenir que des lettres et des espaces.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $surname)) {
        $responseMessage = "Le prénom ne peut contenir que des lettres et des espaces.";
    } elseif (!in_array($gender, ['homme', 'femme'])) {
        $responseMessage = "Le sexe doit être 'homme' ou 'femme'.";
    } elseif (!validateBirthdate($birthdate)) {
        $responseMessage = "Vous devez avoir 18 ans ou plus.";
    } else {
        // Update user profile
        $profile['name'] = $name;
        $profile['surname'] = $surname;
        $profile['gender'] = $gender;
        $profile['birthdate'] = $birthdate;

        saveUsers($Users); // Save updated profile
        $responseMessage = "Les informations ont été mises à jour avec succès.";
    }
    header("Location: index.php?action=profile&message=" . urlencode($responseMessage));
    exit;
}

// Function to validate birthdate
function validateBirthdate($birthdate) {
    $birthdateTimestamp = strtotime($birthdate);

    if ($birthdateTimestamp === false) {
        return false; // Invalid date
    }

    $ageInSeconds = time() - $birthdateTimestamp;
    return $ageInSeconds >= (18 * 365.25 * 24 * 60 * 60); // At least 18 years old
}

// Function to save $Users back to users.php
function saveUsers($users) {
    $fileContent = "<?php\n\$Users = " . var_export($users, true) . ";\n?>";
    file_put_contents('users.php', $fileContent);
}
?>
