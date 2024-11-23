<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include('users.php'); // Include the users.php file for global user data

$isLoggedIn = isset($_SESSION['login']);

if (!$isLoggedIn) {
    header("Location: index.php"); // Redirect to index.php if not logged in
    exit;
}

$username = htmlspecialchars(trim($_SESSION['login'])); // Sanitize username

// Ensure the user exists in the global $Users array
if (!isset($Users[$username])) {
    $Users[$username] = array(
        'favorites' => array(),
        'profile' => array(
            'name' => '',
            'surname' => '',
            'gender' => '',
            'birthdate' => '',
        ),
    );
    saveUsers($Users); // Save to users.php
}

// Reference the user's profile data
$profile = &$Users[$username]['profile'];

$name = $profile['name'];
$surname = $profile['surname'];
$gender = $profile['gender'];
$birthdate = $profile['birthdate'];

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
            // Update user profile data
            $profile['name'] = $name;
            $profile['surname'] = $surname;
            $profile['gender'] = $gender;
            $profile['birthdate'] = $birthdate;

            // Save updated data to users.php
            saveUsers($Users);
            $profilemessage = "Les informations ont été mises à jour avec succès.";
        }
    }
    header("Location: index.php?action=profile&message=" . urlencode($profilemessage));
    exit;
}

// Function to save $Users back to users.php
function saveUsers($users) {
    $fileContent = "<?php\n\$Users = " . var_export($users, true) . ";\n?>";
    file_put_contents('users.php', $fileContent);
}
?>
