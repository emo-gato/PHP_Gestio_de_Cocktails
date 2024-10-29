<?php

include('Donnees.inc.php');  // Adjust the path if necessary


if (isset($Recettes) && !empty($Recettes)) {

    $photoDir = 'Photos/';


    foreach ($Recettes as $id => $recette) {
        $titre = $recette['titre'];
        $ingredients = explode('|', $recette['ingredients']);
        
        $photoName = str_replace(' ', '_', strtolower($titre)) . '.jpg';
        $photoPath = $photoDir . $photoName;

 
        if (!file_exists($photoPath)) {
            $photoPath = $photoDir . 'default.jpg';
        }


        echo "<h2>$titre</h2>";

        echo "<img src='$photoPath' alt='$titre' style='width:200px;height:200px;'>";

        echo "<h4>Ingredients:</h4><ul>";
        for ($i = 0; $i < min(2, count($ingredients)); $i++) {
            echo "<li>" . htmlspecialchars($ingredients[$i]) . "</li>";
        }
        echo "</ul><hr>";
    }
} else {
    echo "No recipes found.";
}
?>
