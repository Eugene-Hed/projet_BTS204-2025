<?php
global $pdo;
session_start();
require '../config/database.php';
//$roleId = 2;
//if(!isset($_SESSION['user']) || $_SESSION['user']['RoleId'] != $roleId) {
//    echo "Vous n'avez pas les autorisations nécéssaires pour créer un compte.";
//    exit;
//}

function getNextUserCode($pdo) {
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(Codeutilisateur, 4) AS UNSIGNED)) as max_code FROM Utilisateurs");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxCode = $result['max_code'];
    return 'CNI' . str_pad($maxCode + 1, 6, '0', STR_PAD_LEFT);
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['Email']);
    $telephone = trim($_POST['NumeroTelephone']);
    $prenom = trim($_POST['Prenom']);
    $nom = trim($_POST['Nom']);
    $datenaissance = trim($_POST['DateNaissance']);
    $adresse = trim($_POST['Adresse']);
    $genre = trim($_POST['Genre']);
    $motdepasse = trim($_POST['MotDePasse']);
    $photoutilisateur = $_FILES['PhotoUtilisateur'];

    if(empty($email) || empty($telephone) || empty($prenom) || empty($nom) || empty($datenaissance) || empty($adresse) || empty($genre) || empty($motdepasse) || empty($photoutilisateur)):
        ?>
        <script>alert("Veillez remplir tous les champs.");</script>
    <?php
    exit;
    endif;

    $stmt = $pdo->prepare('SELECT * FROM Utilisateurs WHERE Email = :Email');
    $stmt->execute(['Email' => $email]);
    $existingUser =$stmt->fetch();

    if($existingUser): ?>
<script>alert("L'utilisateur existe dejà, veillez choisir une autre adresse email");</script>
<?php
    header("Loaction: inscription.php");
    exit;
    endif;

    $hashedPassword = password_hash($motdepasse, PASSWORD_DEFAULT);

    if ($photoutilisateur['error'] === 0) {
        $targetDir ="../uploads/profile_pictures/";
        if(!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileExtension = pathinfo($photoutilisateur['name'],PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if(!in_array(strtolower($fileExtension), $allowedExtensions)):
            ?>
<script>alert("Format de fichier non prise en charge, veillez télécharger une autre image");</script>
<?php
            header("Loaction: inscription.php");
        exit;
endif;

$profilePictureName = uniqid() . '.' . $fileExtension;
$targetFilePath = $targetDir . $profilePictureName;

if(move_uploaded_file($photoutilisateur['tmp_name'], $targetFilePath)) {
    $newUserCode = getNextUserCode($pdo);

    $stmt = $pdo->prepare('INSERT INTO Utilisateurs (Email, NumeroTelephone, Prenom, Nom, DateNaissance, Adresse, Genre, MotDePasse, RoleId, Codeutilisateur, PhotoUtilisateur)
    VALUES (:Email, :NumeroTelephone, :Prenom, :Nom, :DateNaissance, :Adresse, :Genre, :MotDePasse, :RoleId, :Codeutilisateur, :PhotoUtilisateur)');

    $stmt->execute([
        'Email' => $email,
        'NumeroTelephone' => $telephone,
        'Prenom' => $prenom,
        'Nom' => $nom,
        'DateNaissance' => $datenaissance,
        'Adresse' => $adresse,
        'Genre' => $genre,
        'MotDePasse' => $hashedPassword,
        'RoleId' => 2,
        'Codeutilisateur' => $newUserCode,  // Utilisation du nouveau code
        'PhotoUtilisateur' => $targetFilePath
    ]);
?>
<script>alert("Inscription Réussis, vous pouvez maintenant vous connecter.");</script>
<?php
    header("Location: ../index.php");
    exit;
}else{
    ?>
<script>alert("Une erreur s'est produite lors du téléchargement de la photo. Veillez réessayer.");</script>
<?php
    exit;
}
    }else{
        ?>
<script>alert("Veillez télécharger une photo de profil.");</script>
<?php
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="../Assets/css/style2.css" rel="stylesheet" type="text/css"/>
    <link href="../Assets/css/auth.css" rel="stylesheet" type="text/css"/>
    <title>CNIcm</title>
</head>
<body>
<div class="aurora-background"></div>
<div class="container">
    <div class="vh-100 d-flex justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center bg-primary">
                    Inscription
                </div>
                <div class="card-body">
                    <form form method="post" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="Nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" name="Nom" id="Nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="Prenom" class="form-label">Prenom</label>
                            <input type="text" class="form-control" name="Prenom" id="Prenom" required="">
                        </div>
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Telephone</label>
                            <input type="text" class="form-control" name="NumeroTelephone" id="NumeroTelephone" required>
                        </div>
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="Adresse" id="Adresse" required>
                        </div>
                        <div class="mb-3">
                            <label for="genre" class="form-label">Genre</label>
                            <input type="text" class="form-control" name="Genre" id="Genre" required>
                        </div>
                        <div class="mb-3">
                            <label for="datenaissance" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" name="DateNaissance" id="DateNaissance" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" name="Email" id="Email" required>
                        </div>
                        <div class="mb-3">
                            <label for="photoutilisateur" class="form-label">Photo</label>
                            <input type="file" class="form-control" name="PhotoUtilisateur" id="PhotoUtilisateur" required>
                        </div>
                        <div class="mb-3">
                            <label for="motdepasse" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="MotDePasse" id="MotDePasse" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Inscription</button>
                    </form>
                    <div class="card-footer text-muted text-center">
                        <p class="text-center">Vous avez dejà un de compte ? <a class="text-decoration-none" href="../index.php">Connectez vous !</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>