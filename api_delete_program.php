<?php
// --- LIGNES DE DÉBOGAGE : À RETIRER ABSOLUMENT EN PRODUCTION ! ---
// ini_set('display_errors', 1); // COMMENTER OU SUPPRIMER
// ini_set('display_startup_errors', 1); // COMMENTER OU SUPPRIMER
// error_reporting(E_ALL); // COMMENTER OU SUPPRIMER
// --- FIN LIGNES DE DÉBOGAGE ---

header('Content-Type: application/json'); // Indique que la réponse sera du JSON

session_start();
require_once 'db_connexion.php'; // Pour la connexion $pdo
require_once 'requete.php'; // Pour la fonction supprimerProgrammeSemaine

/** @var PDO $pdo */

$response = ['success' => false, 'message' => ''];

// --- Débogage : error_log est OK car ça n'affiche rien au navigateur ---
// error_log("api_delete_program.php: Session dump: " . print_r($_SESSION, true));

// Sécurité : Vérifier si l'utilisateur est admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    $response['message'] = "Accès non autorisé. Vous devez être connecté en tant qu'administrateur.";
    http_response_code(403); // Code HTTP 403 Forbidden
    echo json_encode($response);
    exit();
}

// --- Débogage : error_log est OK car ça n'affiche rien au navigateur ---
// error_log("api_delete_program.php: POST dump: " . print_r($_POST, true));

// Vérifier si la requête est bien un POST et si l'ID est présent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_programme'])) {
    $id_programme_a_supprimer = filter_var($_POST['id_programme'], FILTER_VALIDATE_INT);

    if ($id_programme_a_supprimer !== false && $id_programme_a_supprimer > 0) {
        try {
            if (supprimerProgrammeSemaine($pdo, $id_programme_a_supprimer)) {
                $response['success'] = true;
                $response['message'] = "Programme (ID Semaine: {$id_programme_a_supprimer}) supprimé avec succès.";
            } else {
                $response['message'] = "Échec de la suppression du programme (ID Semaine: {$id_programme_a_supprimer}). La fonction de suppression a retourné false.";
            }
        } catch (PDOException $e) {
            $response['message'] = "Erreur de base de données lors de la suppression : " . $e->getMessage();
            error_log("PDOException in api_delete_program.php: " . $e->getMessage()); // Log l'erreur
            http_response_code(500); // Code HTTP 500 Internal Server Error
        }
    } else {
        $response['message'] = "ID de programme invalide ou manquant pour la suppression.";
        http_response_code(400); // Code HTTP 400 Bad Request
    }
} else {
    $response['message'] = "Requête invalide. Seules les requêtes POST avec 'id_programme' sont acceptées.";
    http_response_code(400); // Code HTTP 400 Bad Request
}

echo json_encode($response);
exit();
?>