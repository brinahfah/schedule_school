<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil du Calendrier</title>
    <link rel="stylesheet" href="tableaux.css">
    
</head>
<body>
    <div class="main">
        <?php
        session_start(); // Toujours au début de CHAQUE page qui utilise les sessions

       
        // Note: db_connexion.php n'est pas strictement nécessaire ici si tu ne fais pas de requêtes SQL.
        // Je le laisse pour le moment au cas où tu aurais d'autres logiques ici.
        // Si tu ne l'utilises que pour les sessions, tu peux le retirer.
        // require_once 'db_connexion.php';
         
         require_once 'logout.php';

        /** @var PDO $pdo */

        // --- Message de bienvenue ---
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['nom_prenom'])) {
            $prenom = htmlspecialchars($_SESSION['nom_prenom']); // Sécurise l'affichage du nom
            echo "<h1>Bonjour, " . $prenom . " !</h1>";
            echo "<p></p>";
        } else {
            // Si l'utilisateur n'est pas connecté
            echo "<h1>Bienvenue sur notre site !</h1>";
            echo "<p>Veuillez vous <a href='login.php'>connecter</a> pour accéder à votre espace.</p>";
        }
        
        // Gérer la déconnexion si le paramètre 'logout' est présent dans l'URL
        if (isset($_GET['logout'])) {
            logout(); // Appelle la fonction de déconnexion
        }

       

        
        // Vérifie si l'utilisateur est connecté ET s'il a le rôle "admin"
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role']) && $_SESSION['role'] == "admin") {
        ?>
            <a href="ajouter.php"><button>Ajouter</button></a>
            <a href="supprimer.php"><button>supprimer</button></a> 
            <a href="create.php"><button>Créer</button></a>
            <a href="read.php"><button>Voir le calendrier</button></a> 
            <a href="liste_school.php"><button>Voir la liste</button></a> 
            <a href="logout.php"><button>Déconnexion</button></a>
        <?php
        } else {
            // Bouton pour les utilisateurs non-admin (ou non connectés)
        ?>
            <a href="read.php"><button>Voir le calendrier</button></a>
            <a href="logout.php"><button>Déconnexion</button></a>
        <?php } ?>
    </div>
</body>
</html>