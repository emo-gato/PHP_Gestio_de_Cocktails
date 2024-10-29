<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cocktail Recipes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-buttons">
                <a href="index.php" class="nav-button">Navigation</a>
                <a href="index.php?favorites=true" class="nav-button">Recettes ❤️</a>
            </div>
            <div class="search-container">
                <label for="search">Recherche :</label>
                <input type="text" name="search" id="search" placeholder="">
                <button type="submit">🔍</button>
            </div>
            <a href="identification.php" class="nav-button login-button">Zone de connexion</a>
        </nav>
    </header>
    
    <div id="content">
        <aside>
            <h3>Aliment courant</h3>
            <ul>
                <li><a href="#">Aliment</a> / <a href="#">Fruit</a> / <a href="#">Agrume</a></li>
                <ul class="sub-categories">
                    <h4>Sous-categories</h4>
                    <li><a href="index.php?ingredient=Citron">Citron</a></li>
                    <li><a href="index.php?ingredient=Citron vert">Citron vert</a></li>
                    <li><a href="index.php?ingredient=Kumquat">Kumquat</a></li>
                    <li><a href="index.php?ingredient=Mandarine">Mandarine</a></li>
                    <li><a href="index.php?ingredient=Orange">Orange</a></li>
                    <li><a href="index.php?ingredient=Pamplemousse">Pamplemousse</a></li>
                    <li><a href="index.php?ingredient=Partie d'agrumes">Partie d'agrumes</a></li>
                </ul>
            </ul>
        </aside>
        
        <main>
            <h2>Liste des cocktails</h2>
            <div id="recipe-list">
                <?php include('cocktails.php'); ?>
            </div>
        </main>
    </div>

    <footer>
        <p>&copy; 2023 Cocktail Recipes. All rights reserved.</p>
    </footer>
</body>
</html>
