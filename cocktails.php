<?php
// Start the session if it hasn't already been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('Donnees.inc.php');
include('favorites.php');

// Retrieve favorites from session or cookie
$isLoggedIn = isset($_SESSION['login']);
$favorites = getFavorites($isLoggedIn);

// Check if displaying only favorites
$showFavoritesOnly = isset($_GET['favorites']) && $_GET['favorites'] === 'true';

// Initialize an array to hold filtered recipes
$filteredRecipes = [];

// Get the current tag/category from the URL
$currentTag = isset($_GET['category']) ? $_GET['category'] : '';

?>