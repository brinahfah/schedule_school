<?php



/**
 * Ajoute un nouveau programme (cours ou événement) à la table week_schedule
 * et l'assigne à plusieurs personnes via la table program_assignments.
 *
 * @param PDO    $pdo        L'objet PDO de connexion à la base de données.
 * @param string $jour       Le jour du programme (ex: 'Lundi', 'Mardi').
 * @param string $cours      Le nom du cours/événement.
 * @param string $heure      L'heure du programme (format 'HH:MM').
 * @param array  $id_schools Tableau des IDs des écoles/personnes à qui assigner le programme.
 * @return bool True si toutes les insertions réussissent, False sinon.
 */
function ajouterProgrammeSemaine(PDO $pdo, string $jour, string $cours, string $heure, array $id_schools): bool
{
    try {
        $pdo->beginTransaction(); // Démarre une transaction

        // 1. Insertion dans week_schedule (id_week est SERIAL PRIMARY KEY)
        $stmt_program = $pdo->prepare(
            "INSERT INTO week_schedule (jours, cours, heure)
             VALUES (:jours, :cours, :heure)"
        );
        $stmt_program->bindParam(':jours', $jour, PDO::PARAM_STR);
        $stmt_program->bindParam(':cours', $cours, PDO::PARAM_STR);
        $stmt_program->bindParam(':heure', $heure, PDO::PARAM_STR);
        $stmt_program->execute();

        // Récupérer l'ID auto-généré du programme nouvellement inséré
        $new_id_week = $pdo->lastInsertId('week_schedule_id_week_seq'); // Nom de la séquence par défaut pour SERIAL

        // 2. Insertion dans program_assignments pour chaque id_school
        $stmt_assign = $pdo->prepare(
            "INSERT INTO program_assignments (id_week, id_school)
             VALUES (:id_week, :id_school)"
        );

        foreach ($id_schools as $id_school) {
            // Assurez-vous que l'ID de l'école est un entier valide
            $valid_id_school = filter_var($id_school, FILTER_VALIDATE_INT);
            if ($valid_id_school === false || $valid_id_school <= 0) {
                throw new PDOException("ID d'école invalide fourni pour l'assignation : " . $id_school);
            }
            $stmt_assign->bindParam(':id_week', $new_id_week, PDO::PARAM_INT);
            $stmt_assign->bindParam(':id_school', $valid_id_school, PDO::PARAM_INT);
            $stmt_assign->execute();
        }

        $pdo->commit(); // Valide la transaction
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack(); // Annule la transaction en cas d'erreur
        error_log("Erreur d'insertion dans week_schedule ou program_assignments: " . $e->getMessage());
        throw $e; // Relance l'exception
    }
}

/**
 * Supprime un programme de la table week_schedule par son ID unique (id_week)
 * et toutes ses assignations associées dans program_assignments.
 *
 * @param PDO $pdo L'objet PDO de connexion à la base de données.
 * @param int $id_programme L'ID unique du programme à supprimer (qui est id_week).
 * @return bool True si la suppression réussit, False sinon.
 */
function supprimerProgrammeSemaine(PDO $pdo, int $id_programme): bool
{
    try {
        $pdo->beginTransaction(); // Démarre une transaction pour la suppression

        // 1. Supprimer d'abord toutes les assignations liées à ce programme
        $stmt_delete_assignments = $pdo->prepare(
            "DELETE FROM program_assignments WHERE id_week = :id_programme"
        );
        $stmt_delete_assignments->bindParam(':id_programme', $id_programme, PDO::PARAM_INT);
        $stmt_delete_assignments->execute();

        // 2. Ensuite, supprimer le programme de la table week_schedule
        $stmt_delete_program = $pdo->prepare(
            "DELETE FROM week_schedule WHERE id_week = :id_programme"
        );
        $stmt_delete_program->bindParam(':id_programme', $id_programme, PDO::PARAM_INT);
        $stmt_delete_program->execute();

        $pdo->commit(); // Valide la transaction
        return true; // Tout a réussi
    } catch (PDOException $e) {
        $pdo->rollBack(); // Annule la transaction en cas d'erreur
        error_log("Erreur de suppression dans week_schedule ou program_assignments: " . $e->getMessage());
        throw $e; // Relance l'exception
    }
}


?>