<?php
session_start();


require_once 'db_connexion.php';

 /** @var PDO $pdo */ //

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email_saisi = trim($_POST['email']); // C'est cette variable que vous avez définie
    $motdepasse_saisi = trim($_POST['password']);
   
    if (!empty($email_saisi) && !empty($motdepasse_saisi)) {
        try{
            
        $stmt = $pdo->prepare("SELECT id_school, nom_prenom, password FROM schools WHERE email = :email");
        $stmt->bindParam(':email', $email_saisi);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC); 

        // Important : Vérifier si un utilisateur a été trouvé ET si le mot de passe correspond.
        
        if ($user && $motdepasse_saisi == $user['password']) { 

            // STOCKER LES INFORMATIONS DE L'UTILISATEUR DANS LA SESSION
                $_SESSION['id_school'] = $user["id_school"];
                $_SESSION['nom_prenom'] = $user["nom_prenom"]; // 
                $_SESSION['logged_in'] = true; // Une variable pour confirmer que l'utilisateur est connecté
                $_SESSION['role'] = $user["titre"]; 
                
                 // >>> DEUXIÈME REQUÊTE : Récupérer le rôle (titre) depuis la table occupation <<<
                $stmt_role = $pdo->prepare("SELECT titre FROM occupation WHERE id_school = :id_school");
                $stmt_role->bindParam(':id_school', $user["id_school"]); // Utilisez l'id_school de l'utilisateur connecté
                $stmt_role->execute();
                $occupation_data = $stmt_role->fetch(PDO::FETCH_ASSOC);

                if ($occupation_data) {
                    $_SESSION['role'] = $occupation_data['titre']; // Stocke le rôle (admin, professeur, etc.) dans la session
                    $_SESSION['profession'] = $occupation_data['profession']; 
                } else {
                    // Gérer le cas où un utilisateur n'a pas d'entrée correspondante dans la table 'occupation'
                    // Tu peux définir un rôle par défaut, par exemple 'visiteur'
                    $_SESSION['role'] = 'visiteur';
                    $_SESSION['profession'] = 'Non spécifiée';
                }


            header("Location: calendar.php");
            exit();
        } else {
            echo "<script>alert('Email ou mot de passe incorrect');</script>";
            // Pas besoin de exit() ici, le script se terminera naturellement.
            header("Location: index.php");
            exit();
        }
      
          }

          catch (PDOException $e) {
            // Gérer les erreurs de la base de données
            echo "<script>alert('Erreur de connexion à la base de données : " . $e->getMessage() . "');</script>";
            // En production, tu enregistrerais l'erreur dans un fichier log plutôt que de l'afficher directement à l'utilisateur
        }
    } else {
        echo "<script>alert('Veuillez remplir tous les champs');</script>";
        exit();
    }
} else {
    echo "<script>alert('Accès interdit');</script>";
     exit();
}
?>