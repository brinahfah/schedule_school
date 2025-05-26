<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Months_Calendar</title>
   
    <style>

        body {
            font-family: Arial, sans-serif;
            background-color:rgb(29, 87, 212);
            color: #333;
            margin: 0;
            width: 100%;
            height: 100vh;
        }

        .container {
          
           display: flex;
           flex: 1;
           flex-direction: row;
           
        }

        img{
            width: 50%;
            height: 50%;
            object-fit: cover;
        }

     </style>
</head>
<body>
    

    
    <main>
        <div class="container">
            
            <div class="login"> 
                <h1>Bonjour, Bienvenue sur le site ISDG!</h1>
                <form action="fonction.php" method="post">
                    <label for="nom_prenom">Nom et prénom:</label>
                    <input type="text" id="nom_prenom" name="nom_prenom"  placeholder="saisir un nom et un prénom" required>
                    <br>
                    <label for="email"> Email:</label>
                    <input type="text" id="email" name="email" placeholder="saisir un email" required>
                    <br>
                    <label for="password">Mot de passe:</label>
                    <input type="password" id="password" name="password" placeholder="saisir un mot d passe" required>
                    <br>
                    <input type="submit" value="Se connecter">
                </form>
                <div class="image">
                    <img src="image/image_ISDG.png"  alt="image d'un étudiant"> 
                </div>
            </div>
        </div>    
    </main>
        
   


</body>
</html>