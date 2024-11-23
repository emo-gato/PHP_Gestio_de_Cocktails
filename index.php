<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
$isLoggedIn = isset($_SESSION['login']);
include('Donnees.inc.php'); // Load hierarchy and recipes
include('search.php'); // Include search functionality

// Handle the dynamic content display based on the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'cocktails';

// Load user favorites
$favorites = [];
if ($isLoggedIn) {
    $favoritesFile = 'favorites_' . $_SESSION['login'] . '.txt';
    if (file_exists($favoritesFile)) {
        $favorites = explode('|', trim(file_get_contents($favoritesFile)));
    }
} else {
    $favorites = isset($_SESSION['favorites']) ? $_SESSION['favorites'] : [];
}

// Check if only favorites should be shown
$showFavoritesOnly = isset($_GET['favorites']) && $_GET['favorites'] === 'true';

// Reset category path
if (isset($_GET['reset'])) {
    unset($_SESSION['category_path']);
    header("Location: index.php");
    exit;
}

// Current category or default to 'Aliment'
$currentCategory = isset($_GET['category']) ? $_GET['category'] : 'Aliment';

// Initialize or update category path for breadcrumb
if (!isset($_SESSION['category_path'])) {
    $_SESSION['category_path'] = [];
}

// Prevent duplicate tags in the breadcrumb path
if (isset($_GET['reset_path']) && !empty($_GET['reset_path'])) {
    $resetCategory = $_GET['reset_path'];
    $index = array_search($resetCategory, $_SESSION['category_path']);
    if ($index !== false) {
        // Truncate the path up to the current tag
        $_SESSION['category_path'] = array_slice($_SESSION['category_path'], 0, $index + 1);
    }
    $currentCategory = $resetCategory;
} else {
    if ($currentCategory !== end($_SESSION['category_path'])) {
        // Add the current category only if it's not already the last one
        $_SESSION['category_path'][] = $currentCategory;
    }
}

// Remove duplicates (optional, as a safety measure)
$_SESSION['category_path'] = array_unique($_SESSION['category_path']);

// Function to get subcategories of a category
function getSubcategories($category, $hierarchy) {
    return isset($hierarchy[$category]['sous-categorie']) ? $hierarchy[$category]['sous-categorie'] : [];
}

// Function to get all nested subcategories of a category
function getAllSubcategories($category, $hierarchy) {
    $subcategories = [];
    if (isset($hierarchy[$category]['sous-categorie'])) {
        foreach ($hierarchy[$category]['sous-categorie'] as $subcategory) {
            $subcategories[] = $subcategory;
            $subcategories = array_merge($subcategories, getAllSubcategories($subcategory, $hierarchy));
        }
    }
    return $subcategories;
}

// Retrieve all related categories for filtering
$allRelatedCategories = array_merge([$currentCategory], getAllSubcategories($currentCategory, $Hierarchie));

// Handle search query
$query = isset($_POST['searchString']) ? $_POST['searchString'] : '';
$filteredRecipes = [];

if (!empty($query)) {
    // Use search functions from search.php
    $tags = parseSearchQuery($query, $Hierarchie, $Recettes);
    $filteredRecipes = searchRecipes($tags, $Recettes);

    // Debugging: Write search results to debug_log.txt
    $logData = [
        'Query' => $query,
        'Desired Tags' => $tags['desired'],
        'Undesired Tags' => $tags['undesired'],
        'Unrecognized Tags' => $tags['unrecognized'],
        'Filtered Recipes' => array_map(function ($recipe) {
            return $recipe['titre'];
        }, $filteredRecipes),
    ];
    file_put_contents('debug_log.txt', print_r($logData, true));
} else {
    // Default behavior: Show recipes based on the current category
    foreach ($Recettes as $recipe) {
        $recipeIngredients = $recipe['index'];
        $matchesCategory = count(array_intersect($allRelatedCategories, $recipeIngredients)) > 0;

        if ($matchesCategory && (!$showFavoritesOnly || in_array($recipe['titre'], $favorites))) {
            $filteredRecipes[] = $recipe;
        }
    }
}

// Breadcrumb path
$fullPath = $_SESSION['category_path'];

// Get subcategories for display
$subcategories = getSubcategories($currentCategory, $Hierarchie);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cocktail Recipes</title>
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <header>
    <nav class="navbar">
            <div class="nav-buttons">
                <a href="index.php?reset=true" class="nav-button">Navigation</a>
                <a href="index.php?favorites=true" class="nav-button">Recettes ❤️</a>
            </div>
            <div class="search-container">
                <form id="searchForm" method="POST" action="index.php">
                    <label for="search">Recherche :</label>
                    <input type="text" id="searchString" name="searchString" placeholder="Rechercher..." required>
                    <button type="submit" class="search-button">🔍</button>
                </form>
            </div>
            <div class="login-zone">
                <?php if ($isLoggedIn): ?>
                    <span><?php echo htmlspecialchars($_SESSION['login']); ?></span>
                    <a href="index.php?action=profile">Profil</a>
                    <a href="logout.php" class="logout-link">Se déconnecter</a>
                <?php else: ?>
                    <form action="login.php" method="post" class="login-form">
                        <input type="text" name="login" placeholder="Login" required>
                        <input type="password" name="password" placeholder="Mot de passe" required>
                        <button type="submit" class="login-button">Connexion</button>
                    </form>
                    <a href="index.php?action=register" class="register-link">S'inscrire</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div id="content">
        <aside>
            <h3>Aliment courant</h3>
            <ul>
                <li>
                    <?php
                    echo implode(" / ", array_map(function($cat) {
                        return "<a href='index.php?reset_path=" . urlencode($cat) . "'>" . htmlspecialchars($cat) . "</a>";
                    }, $fullPath)); // Unique tags in the breadcrumb
                    ?>
                </li>
            </ul>
            <h4>Sous-catégories :</h4>
            <ul class="sub-categories">
                <?php foreach ($subcategories as $subcategory): ?>
                    <li>- <a href="index.php?category=<?php echo urlencode($subcategory); ?>"><?php echo htmlspecialchars($subcategory); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </aside>
       
        <main>
            <?php if ($action === 'cocktails'): ?>
                <h2>Liste des cocktails</h2>
            <div id="recipe-list" class="cocktail-list">
                <?php
                if (!empty($filteredRecipes)) {
                    foreach ($filteredRecipes as $recipe) {
                        $photoName = str_replace(' ', '', strtolower($recipe['titre'])) . '.jpg';
                        $photoPath = file_exists('Photos/' . $photoName) ? 'Photos/' . $photoName : 'Photos/default.jpg';
                        $isFavorite = in_array($recipe['titre'], $favorites);
                        $heartIcon = $isFavorite ? '❤️' : '🤍';

                        echo "<div class='cocktail-card'>";
                        echo "<a href='toggle_favorite.php?recipe=" . urlencode($recipe['titre']) . "' class='heart-icon'>$heartIcon</a>";
                        echo "<h3>" . htmlspecialchars($recipe['titre']) . "</h3>";
                        echo "<img src='$photoPath' alt='" . htmlspecialchars($recipe['titre']) . "' class='cocktail-img'>";
                        echo "<ul>";
                        foreach ($recipe['index'] as $ingredient) {
                            echo "<li>" . htmlspecialchars($ingredient) . "</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>Aucune recette trouvée.</p>";
                }
                ?>
                </div>
            <?php elseif ($action === 'profile'): ?>
                <?php
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
                
                ?>
                <h2 class="section-title">Profil Utilisateur</h2>
                <div class="form-container">
                    <form method="POST" action="profile.php" class="profile-form">
                        <div class="form-group">
                            <label for="name">Nom:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="surname">Prénom:</label>
                            <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($surname); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Sexe:</label>
                            <select id="gender" name="gender" required>
                                <option value="homme" <?php echo $gender === 'homme' ? 'selected' : ''; ?>>Homme</option>
                                <option value="femme" <?php echo $gender === 'femme' ? 'selected' : ''; ?>>Femme</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="birthdate">Date de naissance:</label>
                            <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" required>
                        </div>
                        <button type="submit" class="submit-button">Mettre à jour</button>
                    </form>
                    <div class="message">
                        <?php
                        $profilemessage = isset($_GET['message']) ? $_GET['message'] : '';
                        echo $profilemessage; ?>
                </div>
            <?php elseif ($action === 'register'): ?>
                <h2>S'inscrire</h2>
                <form action="register.php" method="POST">
                    <label for="username">Nom d'utilisateur:</label>
                    <input type="text" id="username" name="username" required>
                    <label for="password">Mot de passe:</label>
                    <input type="password" id="password" name="password" required>
                    <button type="submit">S'inscrire</button>
                </form>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
