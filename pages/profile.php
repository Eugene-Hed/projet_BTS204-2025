<?php
global $pdo;
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Constantes
define('UPLOAD_DIR', '../uploads/profile_pictures/');
define('DEFAULT_IMAGE', '../Assets/images/default.jpg');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Fonction de nettoyage
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Récupération des données utilisateur
$user = $_SESSION['user'];
$imagePath = isset($user['PhotoUtilisateur']) && file_exists("../" . $user['PhotoUtilisateur'])
    ? "../" . $user['PhotoUtilisateur']
    : DEFAULT_IMAGE;
$email = isset($user['Email']) ? htmlspecialchars($user['Email']) : '';
$codeUtilisateur = isset($user['Codeutilisateur']) ? htmlspecialchars($user['Codeutilisateur']) : '';

$errors = [];
$success = false;

// Validation du formulaire
function validateFormData($data) {
    $errors = [];

    if (empty($data['Nom']) || strlen($data['Nom']) < 2) {
        $errors[] = "Le nom doit contenir au moins 2 caractères.";
    }
    if (empty($data['Prenom']) || strlen($data['Prenom']) < 2) {
        $errors[] = "Le prénom doit contenir au moins 2 caractères.";
    }
    if (empty($data['NumeroTelephone']) || !preg_match("/^[0-9]{9}$/", $data['NumeroTelephone'])) {
        $errors[] = "Le numéro de téléphone doit contenir 9 chiffres.";
    }
    if (empty($data['Adresse'])) {
        $errors[] = "L'adresse est requise.";
    }
    if (!in_array($data['Genre'], ['M', 'F', 'A'])) {
        $errors[] = "Le genre sélectionné n'est pas valide.";
    }
    if (empty($data['DateNaissance']) || !strtotime($data['DateNaissance'])) {
        $errors[] = "La date de naissance n'est pas valide.";
    }
    if (empty($data['Email']) || !filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    return $errors;
}

// Gestion de l'upload de photo
function handlePhotoUpload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Erreur lors de l\'upload du fichier.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'Le fichier est trop volumineux. Maximum 5MB.'];
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filetype = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($filetype, $allowed)) {
        return ['error' => 'Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $newname = uniqid() . '.' . $filetype;
    $uploadfile = UPLOAD_DIR . $newname;

    if (!move_uploaded_file($file['tmp_name'], $uploadfile)) {
        return ['error' => ' Erreur lors du déplacement du fichier.'];
    }

    return ['success' => true, 'path' => 'uploads/profile_pictures/' . $newname];
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    // Récupération des données du formulaire
    $formData = [
        'Nom' => sanitizeInput($_POST['Nom']),
        'Prenom' => sanitizeInput($_POST['Prenom']),
        'NumeroTelephone' => sanitizeInput($_POST['NumeroTelephone']),
        'Adresse' => sanitizeInput($_POST['Adresse']),
        'Genre' => sanitizeInput($_POST['Genre']),
        'DateNaissance' => sanitizeInput($_POST['DateNaissance']),
        'Email' => filter_var($_POST['Email'], FILTER_SANITIZE_EMAIL)
    ];

    // Validation
    $errors = validateFormData($formData);

    // Vérification de l'unicité de l'email
    $checkEmailStmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE Email = :email AND Codeutilisateur != :code");
    $checkEmailStmt->execute([':email' => $formData['Email'], ':code' => $user['Codeutilisateur']]);
    if ($checkEmailStmt->fetchColumn() > 0) {
        $errors[] = "Cet email est déjà utilisé par un autre utilisateur.";
    }

    // Traitement de la photo
    $photoPath = null;
    if (isset($_FILES['PhotoUtilisateur']) && $_FILES['PhotoUtilisateur']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handlePhotoUpload($_FILES['PhotoUtilisateur']);
        if (isset($uploadResult['error'])) {
            $errors[] = $uploadResult['error'];
        } else {
            $photoPath = $uploadResult['path'];
        }
    }

    // Mise à jour si pas d'erreurs
    if (empty($errors)) {
        try {
            // Début de la transaction
            $pdo->beginTransaction();

            // Préparation de la requête de mise à jour
            $sql = "UPDATE utilisateurs SET 
        Nom = :nom,
        Prenom = :prenom,
        NumeroTelephone = :telephone,
        Adresse = :adresse,
        Genre = :genre,
        DateNaissance = :dateNaissance,
        Email = :newEmail";

            if ($photoPath) {
                $sql .= ", PhotoUtilisateur = :photo";
            }

            $sql .= " WHERE Email = :currentEmail";

            $stmt = $pdo->prepare($sql);

            // Paramètres de base
            $params = [
                ':nom' => $formData['Nom'],
                ':prenom' => $formData['Prenom'],
                ':telephone' => $formData['NumeroTelephone'],
                ':adresse' => $formData['Adresse'],
                ':genre' => $formData['Genre'],
                ':dateNaissance' => $formData['DateNaissance'],
                ':newEmail' => $formData['Email'],
                ':currentEmail' => $user['Email']  // L'email actuel de l'utilisateur
            ];

            if ($photoPath) {
                $params[':photo'] = $photoPath;
            }

            // Exécution de la requête
            $stmt->execute($params);

            // Si la mise à jour a réussi
            if ($stmt->rowCount() > 0) {
                // Mise à jour de la session
                $_SESSION['user'] = array_merge($_SESSION['user'], $formData);
                if ($photoPath) {
                    $_SESSION['user']['PhotoUtilisateur'] = $photoPath;
                    // Suppression de l'ancienne photo
                    if (isset($user['PhotoUtilisateur'])) {
                        $oldPhoto = "../" . $user['PhotoUtilisateur'];
                        if (file_exists($oldPhoto) && is_file($oldPhoto) && $oldPhoto !== DEFAULT_IMAGE) {
                            unlink($oldPhoto);
                        }
                    }
                }

                $pdo->commit();
                $success = true;
                $_SESSION['flash_message'] = "Profil mis à jour avec succès !";
                $_SESSION['flash_type'] = "success";

                // Rafraîchissement des variables
                $user = $_SESSION['user'];
                $imagePath = isset($user['PhotoUtilisateur']) ? "../" . $user['PhotoUtilisateur'] : DEFAULT_IMAGE;
                $email = htmlspecialchars($user['Email']);
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Erreur de base de données : " . $e->getMessage();
        }
    }
}
?>
<style>
    .profile-image {
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 50%;
    }
    .form-label {
        font-weight: bold;
    }
</style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-5 mb-5">
                <div class="card-header text-center bg-primary text-white">
                    <h3>Profil Utilisateur</h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Votre profil a été mis à jour avec succès !
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php foreach($errors as $error): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Photo de profil" class="profile-image mb-3">
                            <div class="mb-3">
                                <label for="PhotoUtilisateur" class="form-label d-block">Changer la photo de profil</label>
                                <input type="file" class="form-control" name="PhotoUtilisateur" id="PhotoUtilisateur">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="Nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" name="Nom" id="Nom" value="<?php echo htmlspecialchars($user['Nom']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="Prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="Prenom" id="Prenom" value="<?php echo htmlspecialchars($user['Prenom']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="Email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="Email" id="Email" value="<?php echo $email; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="NumeroTelephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="NumeroTelephone" id="NumeroTelephone" value="<?php echo htmlspecialchars($user['NumeroTelephone']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="Adresse" class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="Adresse" id="Adresse" value="<?php echo htmlspecialchars($user['Adresse']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="Genre" class="form-label">Genre</label>
                                <select class="form-control" name="Genre" id="Genre" required>
                                    <option value="M" <?php echo $user['Genre'] == 'M' ? 'selected' : ''; ?>>Masculin</option>
                                    <option value="F" <?php echo $user['Genre'] == 'F' ? 'selected' : ''; ?>>Féminin</option>
                                    <option value="A" <?php echo $user['Genre'] == 'A' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="DateNaissance" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" name="DateNaissance" id="DateNaissance" value="<?php echo htmlspecialchars($user['DateNaissance']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="CodeUtilisateur" class="form-label">Code Utilisateur</label>
                            <input type="text" class="form-control" id="CodeUtilisateur" value="<?php echo $codeUtilisateur; ?>" disabled>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Mettre à jour le profil
                            </button>
                            <a href="?page=<?php echo base64_encode('../change_password'); ?>" class="btn btn-secondary">
                                <i class="fas fa-key me-2"></i>Changer le mot de passe
                            </a>
                            <a href="?page=<?php echo base64_encode('home'); ?>" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Retour à l'acceuil
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>