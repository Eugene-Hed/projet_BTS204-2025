
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Project/PHP/PHPProject.php to edit this template
-->
<?php
global$pdo;
session_start();
require 'includes/messages.php';
require 'config/database.php';

$message = '';
$messageType = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['Email']);
    $motdepasse = trim($_POST['MotDePasse']);

    if (empty($email) || empty($motdepasse)) {
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE Email = :Email');
    $stmt->execute(['Email' => $email]);
    $user = $stmt->fetch();

    if($user && password_verify($motdepasse, $user['MotDePasse'])){
        $_SESSION['user'] = [
            'UtilisateurID' => $user['UtilisateurID'],
            'Codeutilisateur' => $user['Codeutilisateur'],
            'Email' => $user['Email'],
            'NumeroTelephone' => $user['NumeroTelephone'],
            'Prenom' => $user['Prenom'],
            'Nom' => $user['Nom'],
            'DateNaissance' => $user['DateNaissance'],
            'Adresse' => $user['Adresse'],
            'RoleId' => $user['RoleId'],
            'Genre' => $user['Genre'],
            'PhotoUtilisateur' => $user['PhotoUtilisateur'],
        ];
        $_SESSION['message'] = [
                'text' => 'Connexion réussie ! Bienvenue ' . $user['Prenom'] . '!',
                'type' => 'alert-succes'
        ];
        switch ($user['RoleId']) {
            case 1 :
                header("Location: pages/admin/admin_dashboard.php");
                break;
            case 2:
                header("Location: pages/citoyen/citoyen_dashboard.php");
                break;
            case 3:
                header("Location: pages/officier/officier_dashboard.php");
                break;
            case 4:
                header("Location: pages/president/president_dashboard.php");
                break;
        }
        exit;
    }else {
        $_SESSION['message'] = [
                'text' => 'Email ou mot de passe incorrect.',
            'type' => 'alert-danger'
        ];
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="">
    <head>
        <meta charset="UTF-8">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
        <link href="Assets/css/style2.css" rel="stylesheet" type="text/css"/>
        <link href="Assets/css/auth.css" rel="stylesheet" type="text/css"/>
        <title>CNIcm</title>
        <script>
            $(document).ready(function () {
                <?php
                if(!empty($message)):
                ?>
                $('div.alert').show('slow').delay(3000).hide('slow');
                <?php endif; ?>
            });
        </script>
    </head>

    <body>
    <div class="aurora-background"></div>
        <div class="container mt-5">
            <?php
            if(isset($_SESSION['message'])):
            ?>
            <div class="alert <?php echo $_SESSION['message']['type'] ?> alert-block addok" style="display: none;">
                <?php echo $_SESSION['message']['text']; ?>
            </div>
                <?php
            unset($_SESSION['message']);
                ?>
            <?php endif; ?>
        <div class="vh-100 d-flex justify-content-center">
            <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center bg-primary">
                    Connexion
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" name="Email" id="Email" required>
                        </div>
                        <div class="mb-3">
                            <label for="motdepasse" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="MotDePasse" id="MotDePasse" required="">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Connexion</button>
                    </form>
                <div class="card-footer text-muted text-center">
                    <p class="text-center">Pas encor de compte ? <a class="text-decoration-none" href="pages/inscription.php">Créez en un</a></p>
                </div>
                </div>
        </div>
        </div>
        </div>
        </div>
            <script>
                setTimeout(function () {
                    ler alert = document.querySelector('.alert');
                    if (alert) {
                        alert.style.transition = "opacity 0.5s ease";
                        alert.style.opacity = "0";
                        setTimeout(() => alert(.remove(), 500);

                    }

                }, 3000);
            </script>
    </body>
</html>
