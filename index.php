<?php
include 'Donnees.inc.php';
session_start();

// Toggle favorite functionality
if (isset($_GET['toggleFavorite'])) {
    $recipeId = $_GET['toggleFavorite'];
    if (isset($_SESSION['favorites'][$recipeId])) {
        unset($_SESSION['favorites'][$recipeId]);
    } else {
        $_SESSION['favorites'][$recipeId] = true;
    }
    $category = isset($_GET['category']) ? $_GET['category'] : 'Aliment';
    header("Location: index.php?category=" . $category);
    exit();
}

// Display full navigation hierarchy with current category highlighted
function displayNavigation($hierarchy, $current = 'Aliment') {
    echo "<p><strong>Aliment courant</strong><br>";

    // Display breadcrumb navigation up to the current category
    $trail = [];
    $category = $current;
    while ($category !== 'Aliment' && isset($hierarchy[$category])) {
        array_unshift($trail, $category);
        $category = $hierarchy[$category]['super-categorie'][0];
    }
    array_unshift($trail, 'Aliment');

    // Display full hierarchy breadcrumb
    foreach ($trail as $index => $item) {
        if ($index > 0) echo " / ";
        echo "<a href='?category=" . urlencode($item) . "'>" . htmlspecialchars($item) . "</a>";
    }
    echo "</p>";

    // Display sub-categories if available
    echo "<p>Sous-catégories :</p><ul>";
    if (isset($hierarchy[$current]['sous-categorie'])) {
        foreach ($hierarchy[$current]['sous-categorie'] as $subCategory) {
            echo "<li><a href='?category=" . htmlspecialchars($subCategory) . "'> - " . htmlspecialchars($subCategory) . "</a></li>";
        }
    }
    echo "</ul>";
}

// Display recipes in a grid format
function displayRecipes($recipes, $currentCategory = null, $isFavoriteList = false, $searchTerm = null) {
    echo "<div class='cocktail-list'>";
    foreach ($recipes as $id => $recipe) {
        // Apply filtering by category if selected
        if (!$isFavoriteList && $currentCategory && !in_array($currentCategory, $recipe['index'])) {
            continue;
        }

        // Apply search filtering if a search term is provided
        if ($searchTerm && stripos($recipe['titre'], $searchTerm) === false) {
            continue;
        }

        $heart = isset($_SESSION['favorites'][$id]) ? '❤️' : '♡';
        $toggleFavoriteLink = "<a href='?toggleFavorite=$id&category=" . urlencode($currentCategory) . "' class='heart'>$heart</a>";

        echo "<div class='cocktail-card'>";
        echo "<h3>" . htmlspecialchars($recipe['titre']) . " $toggleFavoriteLink</h3>";
        $imgPath = "Photos/" . str_replace(' ', '_', strtolower($recipe['titre'])) . ".jpg";
        if (!file_exists($imgPath)) {
            $imgPath = "Photos/default.jpg";
        }
        echo "<img src='" . htmlspecialchars($imgPath) . "' alt='" . htmlspecialchars($recipe['titre']) . "' class='cocktail-img'>";
        echo "<ul>";
        foreach ($recipe['index'] as $ingredient) {
            echo "<li>" . htmlspecialchars($ingredient) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    echo "</div>";
}

$currentCategory = isset($_GET['category']) ? $_GET['category'] : null;
$searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
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
                <a href="index.php" class="nav-button">Navigation</a>
                <a href="index.php?favorites=true" class="nav-button">Recettes ❤️</a>
            </div>
            <div class="search-container">
                <form method="get" action="index.php" style="display:inline;">
                    <label for="search">Recherche :</label>
                    <input type="text" name="search" id="search" placeholder="Recherche..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit">🔍</button>
                </form>
            </div>
            <a href="identification.php" class="nav-button login-button">Zone de connexion</a>
        </nav>
    </header>

    <div id="content">
        <aside>
            <?php
                // Display the full navigation hierarchy
                displayNavigation($Hierarchie, $currentCategory ?: 'Aliment');
            ?>
        </aside>

        <main>
            <h2>Liste des cocktails</h2>
            <div id="recipe-list">
                <?php
                    if (isset($_GET['favorites']) && $_GET['favorites'] === 'true') {
                        // Show only favorite recipes if in the favorites section
                        $favoriteRecipes = array_filter($Recettes, function($recipeId) {
                            return isset($_SESSION['favorites'][$recipeId]);
                        }, ARRAY_FILTER_USE_KEY);
                        if (!empty($favoriteRecipes)) {
                            displayRecipes($favoriteRecipes, $currentCategory, true, $searchTerm);
                        } else {
                            echo "<p>Aucune recette préférée.</p>";
                        }
                    } else {
                        // Display all recipes, with optional category and search term filtering
                        displayRecipes($Recettes, $currentCategory, false, $searchTerm);
                    }
                ?>
            </div>
        </main>
    </div>

    <footer>
        <p>&copy; 2023 Cocktail Recipes. All rights reserved.</p>
    </footer>
</body>
</html>
