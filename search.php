<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('Donnees.inc.php'); // Include the necessary data for recipes and tags

// Renamed function to avoid conflict with the index.php function
function getSubcategoriesForSearch($category, $hierarchy) {
    $subcategories = [$category];
    if (isset($hierarchy[$category]['sous-categorie']) && is_array($hierarchy[$category]['sous-categorie'])) {
        foreach ($hierarchy[$category]['sous-categorie'] as $subcategory) {
            $subcategories = array_merge($subcategories, getSubcategoriesForSearch($subcategory, $hierarchy));
        }
    }
    return array_unique($subcategories);
}

// Function to check if an ingredient is recognized
function isIngredientRecognized($ingredient, $recipes = []) {
    if (!is_array($recipes)) {
        return false; // Ensure $recipes is an array
    }
    $normalizedIngredient = strtolower($ingredient);
    foreach ($recipes as $item) {
        foreach ($item['index'] as $indexIngredient) {
            if (strtolower($indexIngredient) === $normalizedIngredient) {
                return true;
            }
        }
    }
    return false;
}

// Function to parse the search query
function parseSearchQuery($query, $hierarchy, $recipes = []) {
    $tags = ['desired' => [], 'undesired' => [], 'unrecognized' => []];
    if (!is_array($recipes)) {
        return $tags; // Return empty tags if $recipes is invalid
    }

    preg_match_all('/(\+|-)?"([^"]+)"|(\+|-)?"?([\w]+)"?/', $query, $matches, PREG_SET_ORDER);
    file_put_contents('debug_log.txt', print_r($matches , true));
    foreach ($matches as $match) {

        

        if (isset($match[1]) && $match[1] !== '')       $operator = $match[1];
        elseif (isset($match[3]) && $match[3] !== '')   $operator = $match[3];
        else                        $operator = '+';

        if(isset($match[2]) && $match[2] !== '')        $tag = $match[2];
        elseif (isset($match[4]) && $match[4] !== '')   $tag = $match[4];
        else                        $tag = 'peepeepoopoo';

        $tag = strtolower($tag);
        $tag[0] = strtoupper($tag[0]);

        if (array_key_exists($tag, $hierarchy) || isIngredientRecognized($tag, $recipes)) {
            $descendants = getSubcategoriesForSearch($tag, $hierarchy);
            if ($operator === '+') {
                $tags['desired'] = array_merge($tags['desired'], $descendants);
            } elseif ($operator === '-') {
                $tags['undesired'] = array_merge($tags['undesired'], $descendants);
            }
        } else {
            $tags['unrecognized'][] = $tag;
        }
    }

    return $tags;
}

// Function to filter recipes
function searchRecipes($tags, $recipes = []) {
    $results = [];
    if (!is_array($recipes)) {
        return $results; // Return empty results if $recipes is invalid
    }

    foreach ($recipes as $recipe) {
        $ingredients = $recipe['index'];
        $matchesDesired = empty($tags['desired']) || count(array_intersect($tags['desired'], $ingredients)) > 0;
        $matchesUndesired = empty($tags['undesired']) || count(array_intersect($tags['undesired'], $ingredients)) === 0;

        if ($matchesDesired && $matchesUndesired) {
            $results[] = $recipe;
        }
    }
    return $results;
}

// Main logic
$query = isset($_POST['searchString']) ? $_POST['searchString'] : ''; // Query from form submission
$response = [
    'desired' => [],
    'undesired' => [],
    'recipes' => [],
    'unrecognized' => [],
];

if (!empty($query)) {
    $tags = parseSearchQuery($query, $Hierarchie, $Recettes);
    $response['desired'] = array_unique($tags['desired']);
    $response['undesired'] = array_unique($tags['undesired']);
    $response['unrecognized'] = $tags['unrecognized'];

    if (!empty($response['desired']) || !empty($response['undesired'])) {
        $response['recipes'] = searchRecipes($tags, $Recettes);
    }

    // Write results to a log file
    $logData = [
        'Query' => $query,
        'Desired Tags' => $response['desired'],
        'Undesired Tags' => $response['undesired'],
        'Unrecognized Tags' => $response['unrecognized'],
        'Filtered Recipes' => array_map(function ($recipe) {
            return $recipe['titre'];
        }, $response['recipes']),
    ];

    file_put_contents('debug_log.txt', print_r($logData, true));
}