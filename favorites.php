<?php
function getFavorites($isLoggedIn) {
    $favorites = [];

    if ($isLoggedIn) {
        $favoritesFile = 'favorites_' . $_SESSION['login'] . '.txt';
        if (file_exists($favoritesFile)) {
            $favorites = explode('|', file_get_contents($favoritesFile));
        }
    } else {
        $favorites = isset($_SESSION['favorites']) ? $_SESSION['favorites'] : [];
    }

    return $favorites;
}
?>
