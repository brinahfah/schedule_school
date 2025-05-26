<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste de profession</title>
    <link rel="stylesheet" href="tableaux.css">
    <style>
        /* Styles pour le tableau du calendrier */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 8px;
        }
        .filter-section label, .filter-section select, .filter-section button {
            margin-right: 10px;
        }
        .action-button {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
        }
        .action-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="main">
        <?php
        //ini_set('display_errors', 1);
        //ini_set('display_startup_errors', 1);
        //error_reporting(E_ALL);
        session_start();
        require_once 'db_connexion.php';

        /** @var PDO $pdo */

        $id_personne_a_afficher = null;
        $nom_personne_a_afficher = "du calendrier"; // Texte par défaut pour l'affichage

        // --- Récupérer l'ID de la personne à afficher ---
        // 1. Si un ID est passé dans l'URL (pour les admins qui veulent filtrer)
        if (isset($_GET['id_personne']) && is_numeric($_GET['id_personne'])) {
            $id_personne_a_afficher = (int)$_GET['id_personne'];
        }
        // 2. Si aucun ID n'est passé dans l'URL, utiliser l'ID de l'utilisateur connecté
        elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['id_school'])) {
            $id_personne_a_afficher = $_SESSION['id_school'];
        }
        // 3. Si aucun ID n'est disponible (non connecté et pas de filtre), rediriger ou afficher un message
        else {
            echo "<h1>Veuillez vous connecter ou sélectionner une personne.</h1>";
            echo "<p>Retour à l'<a href='calendar.php'>accueil</a>.</p>";
            exit(); // Arrête l'exécution si pas d'ID à afficher
        }

        // --- Récupérer le nom de la personne à afficher (pour le titre) ---
        if ($id_personne_a_afficher) {
            try {
                $stmt_nom = $pdo->prepare("SELECT nom_prenom FROM schools WHERE id_school = :id_school");
                $stmt_nom->bindParam(':id_school', $id_personne_a_afficher, PDO::PARAM_INT);
                $stmt_nom->execute();
                $resultat_nom = $stmt_nom->fetch(PDO::FETCH_ASSOC);
                if ($resultat_nom) {
                    $nom_personne_a_afficher = htmlspecialchars($resultat_nom['nom_prenom']);
                } else {
                    $nom_personne_a_afficher = "Personne inconnue";
                }
            } catch (PDOException $e) {
                error_log("Erreur lors de la récupération du nom : " . $e->getMessage());
                $nom_personne_a_afficher = "Erreur de récupération du nom";
            }
        }

        echo "<h1>Calendrier de la Semaine de " . $nom_personne_a_afficher . "</h1>";
        ?>

        <?php
        // --- Section de filtre pour les administrateurs ---
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role']) && $_SESSION['role'] === "admin") {
            $personnes_list = [];
            try {
                $stmt_personnes = $pdo->query("SELECT id_school, nom_prenom FROM schools ORDER BY nom_prenom");
                $personnes_list = $stmt_personnes->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Erreur lors de la récupération des personnes : " . $e->getMessage());
                echo "<p style='color: red;'>Impossible de charger la liste des personnes pour le filtre.</p>";
            }
        ?>
            <div class="filter-section">
                <form action="read.php" method="get">
                    <label for="filter_person">Filtrer par personne :</label>
                        <?php foreach ($personnes_list as $personne) { ?>
                            <option value="<?= htmlspecialchars($personne['id_school']) ?>"
                                <?= ($id_personne_a_afficher == $personne['id_school']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($personne['nom_prenom']) ?>
                            </option>
                        <?php } ?>
                </form>
            </div>
        <?php } ?>

        <?php
        $schedule_data = [];
        if ($id_personne_a_afficher) { // S'assurer qu'on a un ID valide pour la requête
            try {
                // REQUÊTE MODIFIÉE : Jointure avec program_assignments et schools pour obtenir les noms assignés
                // STRING_AGG est la fonction PostgreSQL équivalente à GROUP_CONCAT en MySQL
                $stmt_select_schedule = $pdo->prepare(
                    "SELECT 
                                s.nom_prenom, 
                                s.email as mail,
                                o.profession as role
                            from 
                                schools as s
                            inner join 
                                occupation as o on s.id_school = o.id_school;"
                );
                $stmt_select_schedule->bindParam(':id_school', $id_personne_a_afficher, PDO::PARAM_INT);
                $stmt_select_schedule->execute();
                $schedule_data = $stmt_select_schedule->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Erreur lors de la récupération du calendrier : " . htmlspecialchars($e->getMessage()) . "</p>";
                error_log("Erreur de récupération calendrier pour ID " . ($id_personne_a_afficher ?? 'N/A') . ": " . $e->getMessage());
            }
        }
        ?>

        <table border="1">
            <thead>
                <tr>
                    <th>Nom_Prenom</th>
                    <th>Email</th>
                    <th>Profession</th>
                   
        
            </thead>
            <tbody>
                <?php if (!empty($schedule_data)) {
                    foreach ($schedule_data as $row) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nom_prenom']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['profession']) ?></td>
                            
                    <?php }
                } else { ?>
                    <tr><td colspan="4">Cette personne n'est pas sur la liste.</td></tr>
                <?php } ?>
            </tbody>
        </table>

        < href="calendar.php"><button>Retour à l'accueil</button></a>
    </div>
</body>
</html>