<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include('Donnees.inc.php'); // Load recipes and hierarchy
include('users.php'); // Load user data
include('search.php'); // Include search functionality

$isLoggedIn = isset($_SESSION['login']);

// Ensure user data exists
if ($isLoggedIn) {
    $currentUser = $_SESSION['login'];

    // Initialize user in $Users if not present
    if (!isset($Users[$currentUser])) {
        $Users[$currentUser] = array(
            'favorites' => array(),
            'profile' => array(
                'name' => '',
                'surname' => '',
                'gender' => '',
                'birthdate' => '',
            ),
        );
        saveUsers($Users); // Save changes to users.php
    }

    // Reference the current user's data
    $favorites = &$Users[$currentUser]['favorites'];
    $profile = &$Users[$currentUser]['profile'];
} else {
    $favorites = array(); // Guests have no saved favorites
    $profile = array(); // Guests have no profile
}

// Handle toggling favorites
if (isset($_GET['toggle_favorite'])) {
    $drinkTitle = urldecode($_GET['toggle_favorite']);
    if (in_array($drinkTitle, $favorites)) {
        $favorites = array_diff($favorites, [$drinkTitle]); // Remove from favorites
    } else {
        $favorites[] = $drinkTitle; // Add to favorites
    }
    saveUsers($Users); // Save changes to users.php
    header("Location: index.php?action=cocktails");
    exit;
}

// Function to save $Users back to users.php
function saveUsers($users) {
    $fileContent = "<?php\n\$Users = " . var_export($users, true) . ";\n?>";
    file_put_contents('users.php', $fileContent);
}

// Handle the dynamic content display based on the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'cocktails';
$selectedCocktail = isset($_GET['cocktail']) ? urldecode($_GET['cocktail']) : null;

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

if (isset($_GET['reset_path']) && !empty($_GET['reset_path'])) {
    $resetCategory = $_GET['reset_path'];
    $index = array_search($resetCategory, $_SESSION['category_path']);
    if ($index !== false) {
        $_SESSION['category_path'] = array_slice($_SESSION['category_path'], 0, $index + 1);
    }
    $currentCategory = $resetCategory;
} else {
    if ($currentCategory !== end($_SESSION['category_path'])) {
        $_SESSION['category_path'][] = $currentCategory;
    }
}

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
    $tags = parseSearchQuery($query, $Hierarchie, $Recettes);
    $filteredRecipes = searchRecipes($tags, $Recettes);
} else {
    foreach ($Recettes as $recipe) {
        $recipeIngredients = $recipe['index'];
        $matchesCategory = count(array_intersect($allRelatedCategories, $recipeIngredients)) > 0;

        if ($matchesCategory && (!$showFavoritesOnly || in_array($recipe['titre'], $favorites))) {
            $filteredRecipes[] = $recipe;
        }
    }
}

$fullPath = $_SESSION['category_path'];
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
                }, $fullPath));
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
    <?php if ($action === 'profile' && $isLoggedIn): ?>
    <h2>Profil Utilisateur</h2>
    <?php if (isset($_GET['message'])): ?>
        <div class="message"><?php echo htmlspecialchars($_GET['message']); ?></div>
    <?php endif; ?>
    <form method="POST" action="profile.php">
        <div>
            <label for="name">Nom:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($profile['name']); ?>" required>
        </div>
        <div>
            <label for="surname">Prénom:</label>
            <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($profile['surname']); ?>" required>
        </div>
        <div>
            <label for="gender">Sexe:</label>
            <select id="gender" name="gender" required>
                <option value="homme" <?php echo $profile['gender'] === 'homme' ? 'selected' : ''; ?>>Homme</option>
                <option value="femme" <?php echo $profile['gender'] === 'femme' ? 'selected' : ''; ?>>Femme</option>
            </select>
        </div>
        <div>
            <label for="birthdate">Date de naissance:</label>
            <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($profile['birthdate']); ?>" required>
        </div>
        <button type="submit">Mettre à jour</button>
    </form>

        <?php elseif ($selectedCocktail): ?>
            <?php
            $selectedRecipe = null;
            foreach ($Recettes as $recipe) {
                if ($recipe['titre'] === $selectedCocktail) {
                    $selectedRecipe = $recipe;
                    break;
                }
            }
            ?>
            <?php if ($selectedRecipe): ?>
                <div class="cocktail-detail">
                    <h2><?php echo htmlspecialchars($selectedRecipe['titre']); ?></h2>
                    <img src="<?php echo file_exists('Photos/' . str_replace(' ', '', strtolower($selectedRecipe['titre'])) . '.jpg') 
                                ? 'Photos/' . str_replace(' ', '', strtolower($selectedRecipe['titre'])) . '.jpg' 
                                : 'Photos/default.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($selectedRecipe['titre']); ?>" 
                         class="cocktail-img">
                    <h3>Ingrédients:</h3>
                    <ul>
                        <?php foreach (explode('|', $selectedRecipe['ingredients']) as $ingredient): ?>
                            <li><?php echo htmlspecialchars($ingredient); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <h3>Préparation:</h3>
                    <p><?php echo htmlspecialchars($selectedRecipe['preparation']); ?></p>
                    <a href="index.php?action=cocktails" class="nav-button">Retour à la liste</a>
                </div>
            <?php else: ?>
                <p>Recette non trouvée.</p>
                <a href="index.php?action=cocktails" class="nav-button">Retour à la liste</a>
            <?php endif; ?>
        <?php else: ?>
            <h2>Liste des cocktails</h2>
            <div class="cocktail-list">
                <?php foreach ($filteredRecipes as $recipe): ?>
                    <?php
                    $photoName = str_replace(' ', '', strtolower($recipe['titre'])) . '.jpg';
                    $photoPath = file_exists('Photos/' . $photoName) ? 'Photos/' . $photoName : 'Photos/default.jpg';
                    $isFavorite = in_array($recipe['titre'], $favorites);
                    $heartIcon = $isFavorite ? '❤️' : '🤍';
                    ?>
                    <div class="cocktail-card">
                        <a href="index.php?toggle_favorite=<?php echo urlencode($recipe['titre']); ?>" class="heart-icon"><?php echo $heartIcon; ?></a>
                        <h3><a href="index.php?cocktail=<?php echo urlencode($recipe['titre']); ?>"><?php echo htmlspecialchars($recipe['titre']); ?></a></h3>
                        <img src="<?php echo $photoPath; ?>" alt="<?php echo htmlspecialchars($recipe['titre']); ?>" class="cocktail-img">
                        <ul>
                            <?php foreach ($recipe['index'] as $ingredient): ?>
                                <li><?php echo htmlspecialchars($ingredient); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
