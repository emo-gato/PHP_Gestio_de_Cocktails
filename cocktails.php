<?php
// Start the session if it hasn't already been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('Donnees.inc.php');
include('favorites.php');
include('search_functions.php');

// Retrieve favorites from session or cookie
$isLoggedIn = isset($_SESSION['login']);
$favorites = getFavorites($isLoggedIn);

// Check if displaying only favorites
$showFavoritesOnly = isset($_GET['favorites']) && $_GET['favorites'] === 'true';

// Initialize an array to hold filtered recipes
$filteredRecipes = [];

// Get the current tag/category from the URL
$currentTag = isset($_GET['category']) ? $_GET['category'] : '';

// Handle search input
if (isset($_POST["searchString"])) {
    handleSearch($_POST["searchString"], $favorites);
} else {
    // If no search is made, proceed with normal category filtering
    handleCategoryFilter($currentTag, $favorites, $showFavoritesOnly, $filteredRecipes);
}

