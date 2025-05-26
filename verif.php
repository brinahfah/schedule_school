<?php
//Charger les informations de configurations
$config = require_once 'config.php';

try{
    //DSN pour PostgreSQL
    $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";

    //Création de la connexion PDO
    $pdo = new PDO($dsn,$config['user'], $config['password']);

    //Configuration du mode d'erreur de PDO
    $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}
catch (PDOException $e){
    //Gestion des erreurs de connexion
    die("Erreur de connexion : " . $e->getMessage());
}
?>