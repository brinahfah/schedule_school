<?php
// Charger les informations de configuration depuis config.php
$config = require_once 'config.php';

// DÉCLARER $pdo ICI, AVANT LE BLOC TRY-CATCH
$pdo = null; // Initialise la variable à null, afin qu'elle soit toujours définie

try {
    // DSN (Data Source Name) pour PostgreSQL
    $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";

    // Création de la connexion PDO
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Lance des PDOException en cas d'erreur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Récupère les résultats sous forme de tableau associatif par défaut
        PDO::ATTR_EMULATE_PREPARES => false                 // Désactive l'émulation des requêtes préparées pour plus de sécurité et performance
    ]);

    // Aucun message de succès ici !

} catch (PDOException $e) {
    // Gestion des erreurs de connexion
    // En développement, tu peux afficher l'erreur pour débugger
    // En production, il est crucial de masquer les détails à l'utilisateur et de loguer l'erreur.
    error_log("Erreur de connexion à la base de données : " . $e->getMessage()); // Enregistre l'erreur dans les logs du serveur
    die("Désolé, une erreur technique est survenue lors de la connexion à la base de données."); // Message générique pour l'utilisateur
}
?>