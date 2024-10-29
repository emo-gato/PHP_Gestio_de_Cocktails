<?php
include('Donnees.inc.php');

$favorites = isset($_COOKIE['favorites']) ? explode('|', $_COOKIE['favorites']) : [];
$showFavoritesOnly = isset($_GET['favorites']) && $_GET['favorites'] == 'true';

$ingredientFilter = isset($_GET['ingredient']) ? $_GET['ingredient'] : '';

echo "<!-- Debug: Ingredient filter is set to '$ingredientFilter' -->";  // Debugging line


if (isset($Recettes) && !empty($Recettes)) {
    $photoDir = 'Photos/';
    
    echo '<div class="cocktail-list">';

    foreach ($Recettes as $recette) {
        $titre = $recette['titre'];
        $ingredients = explode('|', $recette['ingredients']);
        $indexIngredients = $recette['index'];

       
        if ($showFavoritesOnly && !in_array($titre, $favorites)) {
            continue;
        }


        if ($ingredientFilter && !in_array($ingredientFilter, $indexIngredients)) {
            continue; 
        }


        $photoName = str_replace(' ', '_', strtolower($titre)) . '.jpg';
        $photoPath = $photoDir . $photoName;

        if (!file_exists($photoPath)) {
            $photoPath = $photoDir . 'default.jpg';
        }

        $isFavorite = in_array($titre, $favorites);
        $heartIcon = $isFavorite ? '❤️' : '🤍';


        echo "<div class='cocktail-card'>";
        echo "<h3>$titre <a href='toggle_favorite.php?recipe=" . urlencode($titre) . "' class='heart'>$heartIcon</a></h3>";
        echo "<img src='$photoPath' alt='$titre' class='cocktail-img'>";
        
        echo "<h4>Ingredients:</h4><ul>";
        foreach ($ingredients as $ingredient) {
            echo "<li>" . htmlspecialchars($ingredient) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo '</div>';
} else {
    echo "No recipes found.";
}
?>
