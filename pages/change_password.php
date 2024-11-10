<?php
global $pdo;
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Récupérer le mot de passe haché de l'utilisateur depuis la base de données
    $stmt = $pdo->prepare("SELECT MotDePasse FROM utilisateurs WHERE Email = :email");
    $stmt->execute(['email' => $_SESSION['user']['Email']]);
    $user = $stmt->fetch();

    if ($user) {
        // Vérifier si le mot de passe actuel est correct
        if (password_verify($current_password, $user['MotDePasse'])) {
            // Vérifier si le nouveau mot de passe et la confirmation correspondent
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    // Hasher le nouveau mot de passe
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Mettre à jour le mot de passe dans la base de données
                    $update_stmt = $pdo->prepare("UPDATE utilisateurs SET MotDePasse = :password WHERE Email = :email");
                    if ($update_stmt->execute(['password' => $hashed_password, 'email' => $_SESSION['user']['Email']])) {
                        $success = true;
                        $_SESSION['flash_message'] = "Votre mot de passe a été changé avec succès.";
                        $_SESSION['flash_type'] = "success";
                    } else {
                        $errors[] = "Une erreur est survenue lors de la mise à jour du mot de passe.";
                    }
                } else {
                    $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
                }
            } else {
                $errors[] = "Le nouveau mot de passe et la confirmation ne correspondent pas.";
            }
        } else {
            $errors[] = "Le mot de passe actuel est incorrect.";
        }
    } else {
        $errors[] = "Utilisateur non trouvé.";
    }
}
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5 mb-5">
                <div class="card-header text-center bg-primary text-white">
                    <h3>Changer le mot de passe</h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            Votre mot de passe a été changé avec succès !
                            <button type="button" class="btn-close" data-bs-dismiss ="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php foreach($errors as $error): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Mot de passe actuel
                            </label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <i class="fas fa-key me-2"></i>Nouveau mot de passe
                            </label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-check-double me-2"></i>Confirmer le nouveau mot de passe
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Changer le mot de passe
                            </button>
                            <a href="?page=<?php echo base64_encode('../profile'); ?>" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Retour au profil
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>