<?php
session_start();


$recipeTitle = isset($_GET['recipe']) ? $_GET['recipe'] : '';
$isLoggedIn = isset($_SESSION['login']);


$favorites = [];
if ($isLoggedIn) {
    $favoritesFile = 'favorites_' . $_SESSION['login'] . '.txt';
    if (file_exists($favoritesFile)) {
        $favorites = explode('|', file_get_contents($favoritesFile));
    }
} else {
    $favorites = isset($_SESSION['favorites']) ? $_SESSION['favorites'] : [];
}


if ($recipeTitle) {
    if (in_array($recipeTitle, $favorites)) {
        $favorites = array_diff($favorites, [$recipeTitle]);
    } else {
        $favorites[] = $recipeTitle;
    }
}


if ($isLoggedIn) {
    file_put_contents($favoritesFile, implode('|', $favorites)); // Save to the file
} else {
    $_SESSION['favorites'] = $favorites; 
}


header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
