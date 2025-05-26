<?php
// Garde ces lignes de débogage pour l'instant si tu es en développement, mais retire-les en production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connexion.php';
require_once 'requete.php'; // Correction : j'ai changé 'requete.php' en 'requete_ajout.php' comme dans tes fichiers précédents

/** @var PDO $pdo */

// Sécurité : Rediriger si l'utilisateur n'est pas admin ou non connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: unauthorized.php");
    exit();
}

$message_status = ''; // Pour stocker les messages de succès/erreur

// Récupérer la liste des personnes (écoles) pour le champ de sélection
$personnes_list = [];
try {
    $stmt_personnes = $pdo->query("SELECT id_school, nom_prenom FROM schools ORDER BY nom_prenom");
    $personnes_list = $stmt_personnes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des personnes : " . $e->getMessage());
    $message_status .= "<p class='message error'>Impossible de charger la liste des personnes pour l'assignation.</p>";
}

// --- Traitement du formulaire lorsque des données sont envoyées en POST ---
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id_school_selectionne = filter_var(trim($_POST['id_school_assign']), FILTER_VALIDATE_INT);
    $jours_array = $_POST['jours'] ?? [];
    $cours_array = $_POST['cours'] ?? [];
    $heure_array = $_POST['heure'] ?? [];

    // Validation de base (au moins un programme et une personne sélectionnée)
    if ($id_school_selectionne === false || $id_school_selectionne <= 0 || empty($jours_array)) {
        $message_status = "<p class='message error'>Veuillez sélectionner une personne et ajouter au moins un programme.</p>";
    } else {
        $all_success = true; // Indicateur global de succès
        try {
            // S'assurer que la transaction est bien démarrée avant toute opération
            // et avant de tenter un rollback.
            if (!$pdo->inTransaction()) { // Vérifie si une transaction n'est pas déjà active
                $pdo->beginTransaction(); // Démarre une transaction
            }

            foreach ($jours_array as $index => $jour_saisi) {
                $jour = trim($jour_saisi);
                $cours = trim($cours_array[$index] ?? '');
                $heure = trim($heure_array[$index] ?? '');

                if (empty($jour) || empty($cours) || empty($heure)) {
                    $message_status .= "<p classt='message error'>Ligne de programme incomplète à l'index " . ($index + 1) . ".</p>";
                    $all_success = false;
                    break; // Arrête le traitement si une ligne est invalide
                }

                // APPEL DE LA FONCTION POUR AJOUTER LE PROGRAMME
                if (!ajouterProgrammeSemaine($pdo, $jour, $cours, $heure, [$id_school_selectionne])) {
                    $all_success = false;
                    $message_status .= "<p class='message error'>Erreur lors de l'ajout du programme à l'index " . ($index + 1) . ".</p>";
                    break; // Arrête le traitement si une insertion échoue
                }
            }

            if ($all_success) {
                $pdo->commit(); // Valide toutes les insertions
                $nom_personne_ajoute = "la personne sélectionnée";
                foreach ($personnes_list as $p) {
                    if ($p['id_school'] == $id_school_selectionne) {
                        $nom_personne_ajoute = htmlspecialchars($p['nom_prenom']);
                        break;
                    }
                }
                $message_status = "<p class='message success'>L'emploi du temps a été créé avec succès pour **" . $nom_personne_ajoute . "** !</p>";
            } else {
                // Si all_success est false, annuler la transaction SEULEMENT SI ELLE EST ACTIVE
                if ($pdo->inTransaction()) {
                    $pdo->rollBack(); 
                }
                // Le message d'erreur spécifique aura déjà été ajouté par la boucle
                if (strpos($message_status, "Erreur lors de l'ajout du programme") === false) {
                     $message_status .= "<p class='message error'>La création de l'emploi du temps a échoué. Aucune modification n'a été enregistrée.</p>";
                }
            }
        } catch (PDOException $e) {
            // Si une PDOException est levée, annuler la transaction SEULEMENT SI ELLE EST ACTIVE
            if ($pdo->inTransaction()) {
                $pdo->rollBack(); 
            }
            error_log("PDOException in create.php: " . $e->getMessage());
            $message_status = "<p class='message error'>Erreur de base de données : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Emploi du Temps</title>
    <link rel="stylesheet" href="tableaux.css">
    <style>
        /* Vos styles CSS existants ici */
        form { background-color: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 700px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-end; }
        .form-row label { flex: 1; min-width: 80px; }
        .form-row input, .form-row select { flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-row .input-wrapper { flex: 1; }
        .form-row .input-wrapper label { display: block; margin-bottom: 5px; font-weight: bold; flex: none; }
        .form-row .remove-btn { background-color: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .add-entry-btn { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .add-entry-btn:hover { background-color: #0056b3; }
        form button[type="submit"] { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 20px; }
        form button[type="submit"]:hover { background-color: #218838; }
        .message { margin-top: 15px; padding: 10px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="main">
        <h1>Créer un Emploi du Temps</h1>

        <?= $message_status ?>

        <form action="create.php" method="post">
            <div class="form-group">
                <label for="id_school_assign">Assigner cet emploi du temps à :</label>
                <select id="id_school_assign" name="id_school_assign" required>
                    <option value="">-- Sélectionnez une personne --</option>
                    <?php
                    if (!empty($personnes_list)) {
                        foreach ($personnes_list as $personne) {
                            echo '<option value="' . htmlspecialchars($personne['id_school']) . '">' . htmlspecialchars($personne['nom_prenom']) . '</option>';
                        }
                    } else {
                        echo '<option value="">Aucune personne trouvée</option>';
                    }
                    ?>
                </select>
            </div>

            <h2>Programmes de la semaine :</h2>
            <div id="programmes-container">
                </div>

            <button type="button" id="add-entry-btn" class="add-entry-btn">Ajouter une autre entrée</button>

            <button type="submit">Enregistrer l'emploi du temps</button>
        </form>

        <p><a href="calendar.php">Retour à l'accueil</a></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const programmesContainer = document.getElementById('programmes-container');
            const addEntryBtn = document.getElementById('add-entry-btn');
            let entryCount = 0; // Pour donner des IDs uniques aux champs

            function addProgramEntry() {
                const newRow = document.createElement('div');
                newRow.classList.add('form-row');
                newRow.setAttribute('data-entry-id', entryCount); // Pour faciliter la suppression

                newRow.innerHTML = `
                    <div class="input-wrapper">
                        <label for="jours_${entryCount}">Jour :</label>
                        <select id="jours_${entryCount}" name="jours[]" required>
                            <option value="">Sélectionnez</option>
                            <option value="Lundi">Lundi</option>
                            <option value="Mardi">Mardi</option>
                            <option value="Mercredi">Mercredi</option>
                            <option value="Jeudi">Jeudi</option>
                            <option value="Vendredi">Vendredi</option>
                            <option value="Samedi">Samedi</option>
                            <option value="Dimanche">Dimanche</option>
                        </select>
                    </div>
                    <div class="input-wrapper">
                        <label for="cours_${entryCount}">Cours :</label>
                        <input type="text" id="cours_${entryCount}" name="cours[]" required>
                    </div>
                    <div class="input-wrapper">
                        <label for="heure_${entryCount}">Heure :</label>
                        <input type="time" id="heure_${entryCount}" name="heure[]" required>
                    </div>
                    <button type="button" class="remove-btn">X</button>
                `;

                programmesContainer.appendChild(newRow);

                // Ajouter l'écouteur d'événement pour le bouton de suppression
                newRow.querySelector('.remove-btn').addEventListener('click', function() {
                    newRow.remove();
                });

                entryCount++;
            }

            // Ajouter une première ligne au chargement de la page
            addProgramEntry();

            // Écouteur pour le bouton "Ajouter une autre entrée"
            addEntryBtn.addEventListener('click', addProgramEntry);
        });
    </script>
</body>
</html>