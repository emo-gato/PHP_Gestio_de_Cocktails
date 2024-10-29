<?php

$recipeTitle = isset($_GET['recipe']) ? $_GET['recipe'] : '';


$favorites = isset($_COOKIE['favorites']) ? explode('|', $_COOKIE['favorites']) : [];

if ($recipeTitle) {
    if (in_array($recipeTitle, $favorites)) {
    
        $favorites = array_diff($favorites, [$recipeTitle]);
    } else {
        $favorites[] = $recipeTitle;
    }
    setcookie('favorites', implode('|', $favorites), time() + (86400 * 30), "/"); // Cookie expires in 30 days
}

if (isset($_GET['return_to_favorites']) && $_GET['return_to_favorites'] == 'true') {
    header("Location: index.php?favorites=true");
} else {
    header("Location: index.php");
}
exit;
?>
