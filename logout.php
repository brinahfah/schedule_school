<?php

function logout() {
    // Démarre la session si elle n'est pas déjà active
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

  
    // Finalement, détruit la session.
    session_destroy();

    // Redirige l'utilisateur vers la page de connexion (par exemple, index.php)
    header("Location: index.php");
    exit();
}

?>