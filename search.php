<?php
session_start();
include('Donnees.inc.php'); // Include the necessary data for recipes and tags

// Function to check if an ingredient is recognized
function isIngredientRecognized($ingredient) {
    require 'Donnees.inc.php'; // Ensure this has the necessary data
    // Normalize the ingredient to lower case for comparison
    $normalizedIngredient = strtolower($ingredient); 
    foreach ($Recettes as $item) {
        // Check for case-insensitive match
        foreach ($item['index'] as $indexIngredient) {
            if (strtolower($indexIngredient) === $normalizedIngredient) {
                return true; // Ingredient is recognized
            }
        }
    }
    return false; // Ingredient not recognized
}

// Function to parse the search string into tags
function getTags($searchString) {
    $output = [];
    $unrecognized = [];

    // Use regex to find phrases in quotes and standalone words
    preg_match_all('/"([^"]+)"|\+(\w+)|-(\w+)/', $searchString, $matches);
    
    foreach ($matches[0] as $match) {
        if (strpos($match, '"') === 0) {
            // Multi-word ingredient
            $tagName = trim($matches[1][0]);
            if ($tagName) {
                $output[$tagName] = true; // Considered wanted
            }
        } elseif (strpos($match, '+') === 0) {
            // Wanted ingredient
            $tagName = trim($matches[2][0]);
            if ($tagName) {
                $output[$tagName] = true; // Considered wanted
            }
        } elseif (strpos($match, '-') === 0) {
            // Not wanted ingredient
            $tagName = trim($matches[3][0]);
            if ($tagName) {
                $output[$tagName] = false; // Considered unwanted
            }
        }
    }

    // Check if tags are in hierarchy
    foreach ($output as $tag => $value) {
        if (!isIngredientRecognized($tag)) {
            $unrecognized[] = $tag; // Tag is not recognized
        }
    }

    return [
        'tags' => $output,
        'unrecognized' => $unrecognized
    ];
}

// Function to search for recipes based on the parsed tags
function search($tags) {
    require 'Donnees.inc.php'; // Make sure to include your data here
    $results = [];
    foreach ($Recettes as $item) {
        $valid = true;
        foreach ($tags['tags'] as $tag => $value) {
            if ($value === true && !in_array($tag, $item['index'])) {
                $valid = false; // Required tag not found in recipe
            }
            if ($value === false && in_array($tag, $item['index'])) {
                $valid = false; // Disallowed tag found in recipe
            }
        }
        if ($valid) {
            array_push($results, $item); // Add valid recipe to results
        }
    }
    return $results;
}

// Main execution starts here
$query = isset($_GET['query']) ? $_GET['query'] : ''; // Handle missing query
$response = [
    'desired' => [],
    'unwanted' => [],
    'recipes' => [],
    'unrecognized' => [],
];

if (!empty($query)) {
    try {
        // Process the search query
        $tags = getTags($query);

        // Set the desired and unwanted arrays based on tags
        $response['desired'] = array_keys(array_filter($tags['tags'], function($v) { return $v; }));
        $response['unwanted'] = array_keys(array_filter($tags['tags'], function($v) { return !$v; }));
        $response['unrecognized'] = $tags['unrecognized']; // Collect unrecognized tags

        // Perform the search
        if (!empty($response['desired']) || !empty($response['unwanted'])) {
            $recipes = search($tags);
            foreach ($recipes as $recipe) {
                $response['recipes'][] = $recipe; // Store the recipe directly
            }
        }
    } catch (Exception $e) {
        echo "<p>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
}

// Generate HTML output based on the results
?>
<h2>Résultats de recherche</h2>
<p>Liste des aliments souhaités : <?php echo implode(", ", $response['desired']); ?></p>
<p>Liste des aliments non souhaités : <?php echo implode(", ", $response['unwanted']); ?></p>

<?php if (!empty($response['unrecognized'])): ?>
    <p>Éléments non reconnus dans la requête : <?php echo implode(", ", $response['unrecognized']); ?></p>
<?php endif; ?>

<?php if (!empty($response['recipes'])): ?>
    <h3>Recettes trouvées :</h3>
    <ul>
        <?php foreach ($response['recipes'] as $recipe): ?>
            <li><?php echo htmlspecialchars($recipe['titre']); ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Aucune recette trouvée.</p>
<?php endif; ?>
