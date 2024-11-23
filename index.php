<?php
session_start();

$isLoggedIn = isset($_SESSION['login']);
include('Donnees.inc.php'); // Load hierarchy and recipes
include('search.php'); // Include search functionality

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
            <h2>Liste des cocktails</h2>
            <div id="recipe-list" class="cocktail-list">
                <?php
                if (!empty($filteredRecipes)) {
                    foreach ($filteredRecipes as $recipe) {
                        $photoName = str_replace(' ', '_', strtolower($recipe['titre'])) . '.jpg';
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
        </main>
    </div>
</body>
</html>
