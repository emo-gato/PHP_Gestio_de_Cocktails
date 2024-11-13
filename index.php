<?php
session_start();

$isLoggedIn = isset($_SESSION['login']);
include('Donnees.inc.php');

$favorites = [];
if ($isLoggedIn) {
    $favoritesFile = 'favorites_' . $_SESSION['login'] . '.txt';
    if (file_exists($favoritesFile)) {
        $favorites = explode('|', file_get_contents($favoritesFile));
    }
} else {
    $favorites = isset($_SESSION['favorites']) ? $_SESSION['favorites'] : [];
}


$showFavoritesOnly = isset($_GET['favorites']) && $_GET['favorites'] === 'true';


if (isset($_GET['reset'])) {
    unset($_SESSION['category_path']);
    header("Location: index.php");
    exit;
}

$currentCategory = isset($_GET['category']) ? $_GET['category'] : 'Aliment';
if (!isset($_SESSION['category_path'])) {
    $_SESSION['category_path'] = [];
}

// Handle breadcrumb navigation reset
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


function getSubcategories($category, $hierarchy) {
    return isset($hierarchy[$category]['sous-categorie']) ? $hierarchy[$category]['sous-categorie'] : [];
}


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


function parseSearchTags($searchString) {
    $desired = [];
    $undesired = [];
    preg_match_all('/\+?("[^"]+"|\S+)/', $searchString, $matches);
    foreach ($matches[0] as $match) {
        if (strpos($match, '-') === 0) {
            $undesired[] = trim($match, '- "');
        } else {
            $desired[] = trim($match, '+ "');
        }
    }
    return ['desired' => $desired, 'undesired' => $undesired];
}


$searchQuery = isset($_POST['searchString']) ? $_POST['searchString'] : '';
$searchTags = parseSearchTags($searchQuery);
$desiredIngredients = $searchTags['desired'];
$undesiredIngredients = $searchTags['undesired'];


$allRelatedCategories = array_merge([$currentCategory], getAllSubcategories($currentCategory, $Hierarchie));

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
            <h2>Liste des cocktails</h2>
            <div id="recipe-list" class="cocktail-list">
                <?php
                    
                    $filteredRecipes = [];
                    foreach ($Recettes as $recipe) {
                        $recipeIngredients = $recipe['index'];
                        $matchesCategory = count(array_intersect($allRelatedCategories, $recipeIngredients)) > 0;
                        $matchesDesired = empty($desiredIngredients) || count(array_intersect($recipeIngredients, $desiredIngredients)) > 0;
                        $matchesUndesired = empty($undesiredIngredients) || count(array_intersect($recipeIngredients, $undesiredIngredients)) === 0;

                        // Include recipe if it matches all conditions
                        if ($matchesCategory && $matchesDesired && $matchesUndesired) {
                            if (!$showFavoritesOnly || in_array($recipe['titre'], $favorites)) {
                                $filteredRecipes[] = $recipe;
                            }
                        }
                    }

                    if (!empty($filteredRecipes)) {
                        foreach ($filteredRecipes as $recipe) {
                            $photoName = str_replace(' ', '_', strtolower($recipe['titre'])) . '.jpg';
                            $photoPath = 'Photos/' . $photoName;

                            if (!file_exists($photoPath)) {
                                $photoPath = 'Photos/default.jpg';
                            }

                            $isFavorite = in_array($recipe['titre'], $favorites);
                            $heartIcon = $isFavorite ? '❤️' : '🤍';

                            echo "<div class='cocktail-card'>";
                            echo "<h3>" . htmlspecialchars($recipe['titre']) . " <a href='toggle_favorite.php?recipe=" . urlencode($recipe['titre']) . "' class='heart-icon'>$heartIcon</a></h3>";
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
