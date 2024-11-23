<?php
header('Content-Type: text/html; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('Donnees.inc.php'); // Include the necessary data for recipes and tags

// Function to normalize tags (remove accents, handle case, etc.)
function normalizeTag($tag) {
    $tag = mb_strtolower(trim($tag), 'UTF-8');
    $tag = preg_replace('/\s+/', ' ', $tag);
    return ucfirst($tag);
}

// Function to get subcategories for a tag
function getSubcategoriesForSearch($category, $hierarchy) {
    $subcategories = [$category];
    $normalizedCategory = normalizeTag($category);

    foreach ($hierarchy as $key => $value) {
        if (normalizeTag($key) === $normalizedCategory && isset($value['sous-categorie'])) {
            foreach ($value['sous-categorie'] as $subcategory) {
                $subcategories = array_merge($subcategories, getSubcategoriesForSearch($subcategory, $hierarchy));
            }
        }
    }
    return array_unique($subcategories);
}

// Parse the search query
function parseSearchQuery($query, $hierarchy, $recipes = []) {
    $tags = [
        'explicitDesired' => [], // Tags directly mentioned in the query
        'desired' => [], // Tags including their descendants
        'explicitUndesired' => [], // Explicit undesired tags
        'undesired' => [], // Undesired tags including their descendants
        'unrecognized' => []
    ];

    // Updated regex to capture compound tags like Sel-Citron
    preg_match_all('/(\+|-)?"([^"]+)"|(\+|-)?"?([\p{L}\p{N}-]+)"?/u', $query, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        // Determine the operator
        if (isset($match[1]) && $match[1] !== '') {
            $operator = $match[1];
        } elseif (isset($match[3]) && $match[3] !== '') {
            $operator = $match[3];
        } else {
            $operator = '+';
        }

        // Determine the tag
        if (isset($match[2]) && $match[2] !== '') {
            $tag = $match[2];
        } elseif (isset($match[4]) && $match[4] !== '') {
            $tag = $match[4];
        } else {
            $tag = '';
        }

        $tag = normalizeTag($tag);

        if (array_key_exists($tag, $hierarchy)) {
            $descendants = getSubcategoriesForSearch($tag, $hierarchy);
            if ($operator === '+') {
                $tags['explicitDesired'][] = $tag; // Keep track of explicit tags
                $tags['desired'] = array_merge($tags['desired'], $descendants); // Include descendants
            } elseif ($operator === '-') {
                $tags['explicitUndesired'][] = $tag; // Track explicitly undesired tags
                $tags['undesired'] = array_merge($tags['undesired'], $descendants); // Include descendants
            }
        } else {
            $tags['unrecognized'][] = $tag;
        }
    }

    return $tags;
}

// Search for recipes
function searchRecipes($tags, $recipes = []) {
    $results = [];
    $desiredGroups = [$tags['desired']]; // Grouped desired tags
    $undesiredTags = array_map('normalizeTag', $tags['undesired']);

    foreach ($recipes as $recipe) {
        $ingredients = array_map('normalizeTag', $recipe['index']);

        // Check if at least one tag from each desired group is present
        $matchesAllDesiredGroups = true;
        foreach ($desiredGroups as $group) {
            if (count(array_intersect($group, $ingredients)) === 0) {
                $matchesAllDesiredGroups = false;
                break;
            }
        }

        // Check if none of the undesired tags are present
        $matchesUndesired = empty($undesiredTags) || count(array_intersect($undesiredTags, $ingredients)) === 0;

        if ($matchesAllDesiredGroups && $matchesUndesired) {
            $results[] = $recipe;
        }
    }

    return $results;
}

// Main logic
$query = isset($_POST['searchString']) ? $_POST['searchString'] : ''; // Query from form submission
$response = [
    'explicitDesired' => [],
    'desired' => [],
    'explicitUndesired' => [],
    'undesired' => [],
    'recipes' => [],
    'unrecognized' => [],
];

if (!empty($query)) {
    $tags = parseSearchQuery($query, $Hierarchie, $Recettes);
    $response['explicitDesired'] = $tags['explicitDesired'];
    $response['desired'] = array_unique($tags['desired']);
    $response['explicitUndesired'] = $tags['explicitUndesired'];
    $response['undesired'] = array_unique($tags['undesired']);
    $response['unrecognized'] = $tags['unrecognized'];

    if (!empty($tags['desired']) || !empty($tags['undesired'])) {
        $response['recipes'] = searchRecipes($tags, $Recettes);
    }

    // Generate feedback for the user
    $feedback = [];
    if (!empty($response['explicitDesired'])) {
        $feedback[] = "Liste des aliments souhaités : " . implode(', ', $response['explicitDesired']);
    }
    if (!empty($response['explicitUndesired'])) {
        $feedback[] = "Liste des aliments non souhaités : " . implode(', ', $response['explicitUndesired']);
    }
    if (!empty($response['unrecognized'])) {
        $feedback[] = "Éléments non reconnus dans la requête : " . implode(', ', $response['unrecognized']);
    }

    // Display feedback
    $response['feedback'] = implode('<br>', $feedback);

    // Write results to a log file
    $logData = [
        'Query' => $query,
        'Explicit Desired Tags' => $response['explicitDesired'],
        'Explicit Undesired Tags' => $response['explicitUndesired'],
        'Unrecognized Tags' => $response['unrecognized'],
        'Filtered Recipes' => array_map(function ($recipe) {
            return $recipe['titre'];
        }, $response['recipes']),
        'Feedback' => $response['feedback'],
    ];

    file_put_contents('debug_log.txt', print_r($logData, true), FILE_APPEND);
}

?>
