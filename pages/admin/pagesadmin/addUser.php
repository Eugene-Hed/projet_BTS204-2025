<?php
global $pdo;
require_once('../../config/database.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user']) || $_SESSION['user']['RoleId'] != 1) {
    header('Location: ../../index.php');
    exit();
}

$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données du formulaire
    $codeUtilisateur = $_POST['codeUtilisateur'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $telephone = $_POST['telephone'];
    $prenom = $_POST['prenom'];
    $nom = $_POST['nom'];
    $dateNaissance = $_POST['dateNaissance'];
    $adresse = $_POST['adresse'];
    $role = $_POST['role'];
    $genre = $_POST['genre'];

    // Validation des données
    $errors = [];

    // Validation du code utilisateur
    if (empty($codeUtilisateur)) {
        $errors[] = "Le code utilisateur est requis";
    }

    // Validation de l'email
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    }

    // Validation du mot de passe
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Cet email est déjà utilisé";
    }

    // Vérifier si le code utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE Codeutilisateur = ?");
    $stmt->execute([$codeUtilisateur]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Ce code utilisateur est déjà utilisé";
    }

    if (empty($errors)) {
        try {
            // Hashage du mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Préparation de la requête
            $sql = "INSERT INTO utilisateurs (Codeutilisateur, Email, MotDePasse, NumeroTelephone, 
                    Prenom, Nom, DateNaissance, Adresse, RoleId, Genre, IsActive) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $codeUtilisateur,
                $email,
                $hashedPassword,
                $telephone,
                $prenom,
                $nom,
                $dateNaissance,
                $adresse,
                $role,
                $genre
            ]);

            $success_message = "Utilisateur ajouté avec succès!";
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Récupérer la liste des rôles
$stmt = $pdo->query("SELECT * FROM role");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Reste du code PHP inchangé -->

<div class="main-content">
    <div class="container">
        <div class="card">
            <div class="card-header">
                Ajouter un nouvel utilisateur
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="grid-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Code utilisateur</label>
                            <input type="text" name="codeUtilisateur" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="confirmPassword" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="prenom" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" name="dateNaissance" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Rôle</label>
                            <select name="role" required class="form-control">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo $role['role']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Genre</label>
                            <select name="genre" required class="form-control">
                                <option value="Homme">Homme</option>
                                <option value="Femme">Femme</option>
                            </select>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Adresse</label>
                            <textarea name="adresse" required class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group" style="grid-column: span 2; display: flex; justify-content: space-between;">
                            <a href="admin_dashboard.php" class="btn btn-secondary">Retour</a>
                            <button type="submit" class="btn btn-success">Ajouter l'utilisateur</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .grid-container {
        margin: 0 auto;
        max-width: 800px;
    }

    .form-control {
        background-color: white;
        border: 1px solid #ddd;
        transition: border-color 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }

    .btn {
        padding: 10px 20px;
        font-weight: bold;
    }

    .btn-secondary {
        background-color: var(--secondary-color);
        color: white;
        text-decoration: none;
    }

    .alert {
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 4px;
    }

    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }
</style>