<?php
// --- LIGNES DE DÉBOGAGE : À RETIRER ABSOLUMENT EN PRODUCTION ! ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- FIN LIGNES DE DÉBOGAGE ---

session_start();
require_once 'db_connexion.php'; // Pour la connexion $pdo
require_once 'requete.php'; // Pour les fonctions CRUD (ajouterProgrammeSemaine, supprimerProgrammeSemaine)

// Annotation PHPDoc pour aider l'éditeur à reconnaître $pdo
/** @var PDO $pdo */

// Sécurité : Rediriger si l'utilisateur n'est pas admin ou non connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: unauthorized.php");
    exit();
}

$message_status = ''; // Pour stocker les messages de succès/erreur

// --- Débogage : Afficher le contenu de la session ---
// echo "<p><strong>Contenu de la session :</strong></p><pre>";
// var_dump($_SESSION);
// echo "</pre>";
// echo "<hr>";

// --- Traitement de la suppression si un ID est reçu via POST (via JS Fetch) ---
// Ce bloc est géré par api_delete_program.php, mais on le garde pour la cohérence si tu le soumets directement
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    // --- Débogage : Afficher les données POST reçues pour la suppression ---
    // echo "<p><strong>Données POST reçues pour suppression :</strong></p><pre>";
    // var_dump($_POST);
    // echo "</pre>";
    // echo "<hr>";

    $id_programme_a_supprimer = filter_var($_POST['id_programme'], FILTER_VALIDATE_INT);

    if ($id_programme_a_supprimer !== false && $id_programme_a_supprimer > 0) {
        try {
            if (supprimerProgrammeSemaine($pdo, $id_programme_a_supprimer)) {
                $message_status = "<p class='message success'>Le programme (ID Semaine: {$id_programme_a_supprimer}) a été supprimé avec succès !</p>";
            } else {
                $message_status = "<p class='message error'>Erreur lors de la suppression du programme (ID Semaine: {$id_programme_a_supprimer}).</p>";
            }
        } catch (PDOException $e) {
            $message_status = "<p class='message error'>Erreur de base de données lors de la suppression : " . htmlspecialchars($e->getMessage()) . "</p>";
            error_log("PDOException in supprimer.php (delete): " . $e->getMessage()); // Log l'erreur
        }
    } else {
        $message_status = "<p class='message error'>ID de programme invalide pour la suppression.</p>";
    }
}

// --- Récupérer tous les programmes pour l'affichage (avec les noms des personnes assignées) ---
$all_schedules = [];
try {
    $sql_select_query = "SELECT ws.id_week, ws.jours, ws.cours, ws.heure,
                                STRING_AGG(s.nom_prenom, ', ') AS assigned_people_names
                         FROM week_schedule ws
                         LEFT JOIN program_assignments pa ON ws.id_week = pa.id_week
                         LEFT JOIN schools s ON pa.id_school = s.id_school
                         GROUP BY ws.id_week, ws.jours, ws.cours, ws.heure
                         ORDER BY ws.jours, ws.heure";

    // --- Débogage : Afficher la requête SQL exécutée ---
    // echo "<p><strong>Requête SQL SELECT exécutée :</strong></p><pre>" . htmlspecialchars($sql_select_query) . "</pre><hr>";

    $stmt_all_schedules = $pdo->prepare($sql_select_query);
    // echo "<p><strong>Statement PDO préparé :</strong></p><pre>";
    // var_dump($stmt_all_schedules);
    // echo "</pre><hr>";

    $execute_success = $stmt_all_schedules->execute();
    // echo "<p><strong>Exécution du statement réussie ? </strong>" . ($execute_success ? 'Oui' : 'Non') . "</p><hr>";

    if ($execute_success) {
        $all_schedules = $stmt_all_schedules->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error_info = $stmt_all_schedules->errorInfo();
        // Ces messages ne devraient normalement pas s'afficher si PDO est en mode exception
        // echo "<p style='color: red; font-weight: bold;'>ERREUR D'EXÉCUTION DU SELECT :</p>";
        // echo "<p style='color: red;'>Code SQLSTATE : " . htmlspecialchars($error_info[0]) . "</p>";
        // echo "<p style='color: red;'>Code d'erreur pilote : " . htmlspecialchars($error_info[1]) . "</p>";
        // echo "<p style='color: red;'>Message d'erreur pilote : " . htmlspecialchars($error_info[2]) . "</p>";
        error_log("Erreur d'exécution du SELECT dans supprimer.php: " . implode(" | ", $error_info));
        $message_status .= "<p class='message error'>Erreur lors du chargement des programmes: " . htmlspecialchars($error_info[2]) . "</p>";
    }

    // --- Débogage : Afficher les données récupérées de la DB ---
    // echo "<p><strong>Données récupérées de week_schedule :</strong></p><pre>";
    // var_dump($all_schedules);
    // echo "</pre>";
    // echo "<hr>";

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération de tous les programmes : " . $e->getMessage());
    $message_status .= "<p class='message error'>Erreur critique lors du chargement des programmes : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer un Programme</title>
    <link rel="stylesheet" href="tableaux.css">
    <style>
        /* Styles (inchangés) */
        .message { margin-top: 15px; padding: 10px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .delete-button { background-color: #dc3545; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .delete-button:hover { background-color: #c82333; }
        .action-button { display: inline-block; padding: 10px 15px; margin: 5px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; text-align: center; }
        .action-button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="main">
        <h1>Supprimer un Programme du Calendrier</h1>

        <?= $message_status ?>

        <?php if (!empty($all_schedules)) { ?>
            <table border="1" id="program-table">
                <thead>
                    <tr>
                        <th>ID Semaine</th>
                        <th>Jour</th>
                        <th>Cours</th>
                        <th>Heure</th>
                        <th>Assigné(s) à</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_schedules as $programme) { ?>
                        <tr id="row-<?= htmlspecialchars($programme['id_week']) ?>">
                            <td><?= htmlspecialchars($programme['id_week']) ?></td>
                            <td><?= htmlspecialchars($programme['jours']) ?></td>
                            <td><?= htmlspecialchars($programme['cours']) ?></td>
                            <td><?= htmlspecialchars($programme['heure']) ?></td>
                            <td><?= htmlspecialchars($programme['assigned_people_names'] ?? 'Non assigné') ?></td>
                            <td>
                                <button type="button" class="delete-button" data-id="<?= htmlspecialchars($programme['id_week']) ?>">Supprimer</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p>Aucun programme n'est actuellement enregistré.</p>
            <p style="color: blue;">Vérifiez les messages de débogage ci-dessus pour comprendre pourquoi.</p>
        <?php } ?>

        <p><a href="calendar.php" class="action-button">Retour à l'accueil</a></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-button');
            const messageContainer = document.querySelector('.main');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const programId = this.dataset.id;
                    
                    if (confirm(`Êtes-vous sûr de vouloir supprimer le programme avec l'ID Semaine: ${programId} ?`)) {
                        fetch('api_delete_program.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id_programme=${programId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const rowToRemove = document.getElementById(`row-${programId}`);
                                if (rowToRemove) {
                                    rowToRemove.remove();
                                }
                                const successMessage = document.createElement('p');
                                successMessage.className = 'message success';
                                successMessage.textContent = data.message;
                                messageContainer.prepend(successMessage);
                                setTimeout(() => successMessage.remove(), 5000);
                            } else {
                                const errorMessage = document.createElement('p');
                                errorMessage.className = 'message error';
                                errorMessage.textContent = `Erreur: ${data.message}`;
                                messageContainer.prepend(errorMessage);
                                setTimeout(() => errorMessage.remove(), 7000);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la requête Fetch:', error);
                            const errorMessage = document.createElement('p');
                            errorMessage.className = 'message error';
                            errorMessage.textContent = 'Une erreur réseau est survenue. Impossible de supprimer le programme.';
                            messageContainer.prepend(errorMessage);
                            setTimeout(() => errorMessage.remove(), 7000);
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>