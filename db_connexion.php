<?php

// Fichier: db_supabase_connexion.php

// --- Informations de connexion Supabase ---
// 
$db_host = "db.mtamvxsqjgqieeciyhjh.supabase.co"; 
$db_port = "5432";                       
$db_user = "postgres";                   
$db_password = "brinahfah23"; 
$db_name = "postgres";

try {
    // Chaîne de connexion DSN pour PostgreSQL
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";

    // Options PDO pour la connexion
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mode d'erreur pour lancer des exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Récupérer les résultats sous forme de tableau associatif par défaut
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Désactiver l'émulation des requêtes préparées (meilleure sécurité et performance)
    ];

    // Créer une nouvelle instance PDO
    $pdo = new PDO($dsn, $db_user, $db_password, $options);

   

} catch (PDOException $e) {
    // En cas d'erreur de connexion, affiche un message d'erreur et arrête le script
    // En production, vous voudriez logger l'erreur et afficher un message générique à l'utilisateur.
    die("Erreur de connexion à la base de données Supabase : " . $e->getMessage());
}

?>