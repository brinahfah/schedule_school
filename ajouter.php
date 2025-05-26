<?php
session_start();
require_once 'db_connexion.php';
require_once 'requete.php';

/** @var PDO $pdo */

// Sécurité : Rediriger si l'utilisateur n'est pas admin ou non connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: unauthorized.php");
    exit();
}

// Récupérer la liste des personnes (écoles) pour le champ de sélection
$personnes_list = [];
try {
    $stmt_personnes = $pdo->query("SELECT id_school, nom_prenom FROM schools ORDER BY nom_prenom");
    $personnes_list = $stmt_personnes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des personnes : " . $e->getMessage());
    echo "<p class='message error'>Impossible de charger la liste des personnes.</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Programme</title>
    <link rel="stylesheet" href="tableaux.css">
    <style>
        /* Styles pour le formulaire */
        form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 20px auto;
        }
        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        form input[type="text"],
        form input[type="time"],
        form select { /* Pas de input[type="number"] pour id_week */
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        form select[multiple] { /* Style spécifique pour le select multiple */
            height: 100px; /* Hauteur pour afficher plusieurs options */
            padding: 5px;
        }
        form button {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        form button:hover {
            background-color: #218838;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="main">
        <h1>Ajouter un Programme au Calendrier</h1>

        <?php
        // Traitement du formulaire lorsque des données sont envoyées en POST
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            // Nettoyage des entrées utilisateur
            $jour_saisi = trim($_POST['jours']);
            $cours_saisi = trim($_POST['cours']);
            $heure_saisi = trim($_POST['heure']);

            // Récupération des IDs des personnes sélectionnées (sera un tableau)
            $id_schools_selectionnes = $_POST['id_school_assign'] ?? []; // Récupère un tableau

            // Validation de base
            // Vérifier si $id_schools_selectionnes est bien un tableau et non vide
            if (empty($jour_saisi) || empty($cours_saisi) || empty($heure_saisi) || !is_array($id_schools_selectionnes) || empty($id_schools_selectionnes)) {
                echo "<p class='message error'>Veuillez remplir tous les champs et sélectionner au moins une personne.</p>";
            } elseif (count($id_schools_selectionnes) > 3) { // Limite à 3 personnes
                echo "<p class='message error'>Vous ne pouvez sélectionner qu'un maximum de 3 personnes.</p>";
            } else {
                // Filtrer les IDs pour s'assurer qu'ils sont des entiers valides
                $valid_id_schools = array_filter($id_schools_selectionnes, function($id) {
                    return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0;
                });

                if (empty($valid_id_schools)) {
                    echo "<p class='message error'>Les IDs des personnes sélectionnées sont invalides.</p>";
                } else {
                    try {
                        // APPEL DE LA FONCTION AVEC LE TABLEAU D'IDS
                        if (ajouterProgrammeSemaine($pdo, $jour_saisi, $cours_saisi, $heure_saisi, $valid_id_schools)) {
                            $noms_assignes = [];
                            foreach ($personnes_list as $p) {
                                if (in_array($p['id_school'], $valid_id_schools)) {
                                    $noms_assignes[] = htmlspecialchars($p['nom_prenom']);
                                }
                            }
                            $message_status = "<p class='message success'>Le programme a été ajouté avec succès pour : **" . implode(", ", $noms_assignes) . "** !</p>";
                        } else {
                            $message_status = "<p class='message error'>Erreur lors de l'ajout du programme. Veuillez réessayer.</p>";
                        }
                    } catch (PDOException $e) {
                        $message_status = "<p class='message error'>Erreur de base de données : " . htmlspecialchars($e->getMessage()) . "</p>";
                        error_log("PDOException in ajouter.php: " . $e->getMessage());
                    }
                }
            }
        }
        ?>

        <form action="ajouter.php" method="post">
            <label for="jours">Jour :</label>
            <select id="jours" name="jours" required>
                <option value="">Sélectionnez un jour</option>
                <option value="Lundi">Lundi</option>
                <option value="Mardi">Mardi</option>
                <option value="Mercredi">Mercredi</option>
                <option value="Jeudi">Jeudi</option>
                <option value="Vendredi">Vendredi</option>
                <option value="Samedi">Samedi</option>
                <option value="Dimanche">Dimanche</option>
            </select>

            <label for="cours">Cours/Événement :</label>
            <input type="text" id="cours" name="cours" required>

            <label for="heure">Heure :</label>
            <input type="time" id="heure" name="heure" required>

            <label for="id_school_assign">Assigner ce programme à (max 3) :</label>
            <select id="id_school_assign" name="id_school_assign[]" multiple required> <?php
                if (!empty($personnes_list)) {
                    foreach ($personnes_list as $personne) {
                        echo '<option value="' . htmlspecialchars($personne['id_school']) . '">' . htmlspecialchars($personne['nom_prenom']) . '</option>';
                    }
                } else {
                    echo '<option value="">Aucune personne trouvée</option>';
                }
                ?>
            </select>
            <small>Maintenez Ctrl (ou Cmd sur Mac) pour sélectionner plusieurs personnes.</small>

            <button type="submit">Ajouter au Calendrier</button>
        </form>

        <p><a href="calendar.php">Retour à l'accueil</a></p>
    </div>
</body>
</html>