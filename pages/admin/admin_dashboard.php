<?php
require "../../config/database.php";
session_start();
if(!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit;
}
function redirectTo($page, $params = []) {
    $url = "?page=" . base64_encode($page);
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }
    }
    echo "<script>window.location.href='$url';</script>";
    exit;
}

$prenom = $_SESSION['user']['Prenom'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="../../Assets/css/style.css" rel="stylesheet" type="text/css"/>
    <link href="../../Assets/css/style2.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<?php $url = "admin_dashboard.php";
$pageTitle = 'Home';
if (isset($_REQUEST['page'])) {
    $requestedPage = base64_decode($_REQUEST['page']);

    switch ($requestedPage) {
        case 'pagesadmin/indexadmin':
            $pageTitle = 'Dashboard Admin';
            break;
        case 'pagesadmin/gestion_utilisateurs':
            $pageTitle = 'Gestion Des Utilisateurs';
            break;
        case 'pagesadmin/gestion_demandes':
            $pageTitle = 'Gestion Des Demandes';
            break;
        case 'pagesadmin/rapport_activites':
            $pageTitle = 'Rapport D\'activites';
            break;
        case 'pagesadmin/gestion_reclamations':
            $pageTitle = 'Gestion Des Reclamations';
            break;
        case 'pagesadmin/administration_notifications' :
            $pageTitle = 'Administration Des Notifications';
            break;
        case 'pagesadmin/addUser':
            $pageTitle = 'Ajoue utilisateur';
            break;
            case 'pagesadmin/dashboard_admin' :
                $pageTitle = 'Dashboard Admin';
        case '../profile':
            $pageTitle = 'Profile';
            break;
        default:
            $pageTitle = 'Home';
            break;
    }
}
?>
<div class="aurora-background"></div>
<nav class="navbar navbar-expand-lg navbar-light bg-white main-navbar">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tooltip" title="Accueil" href="?page=<?php echo base64_encode('home'); ?>" >
                        <i class="bi bi-chevron-left me-2"></i>
                        <span class="navbar-text"><?php echo $pageTitle; ?></span>
                    </a>
                </li>
            </ul>
            <ul class="d-flex">
                        <li class="px-5 p-2 d-inline">
                            <a class="nav-link header-link btn badge-info" data-bs-toggle="tooltip" title="Add User" href="<?php echo $url; ?>?page=<?php echo base64_encode('pagesadmin/addUser'); ?>">
                                <i class="fas fa-user-plus mr-2"></i>Ajouter utilisateur</a>
                        </li>
                <li class="p-2 d-inline">
                    <a class="nav-link header-link btn btn-success" data-bs-toggle="tooltip" title="Profile" href="<?php echo $url; ?>?page=<?php echo base64_encode('../profile'); ?>" >
                        <i class="fab fa-500px mr-2"></i><?php echo "$prenom";?></a>
                </li>
                    <li class="p-2 d-inline">
                        <a href="../../config/logout.php" class="btn btn-danger" data-bs-toggle="tooltip" title="Deconnexion"><i class="bi bi-box-arrow-in-right"></i></a>
                    </li>
            </ul>
        </div>
    </div>
</nav>
<div class="sidebar">
    <div class="sidebar-icons">
                <div class="sidebar-icon" data-bs-toggle="tooltip" title="Dashboard Admin">
                    <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagesadmin/dashboard_admin'); ?>">
                        <i class="bi bi-speedometer2"></i></a>
                </div>
                <div class="sidebar-icon" data-bs-toggle="tooltip" title="Gestion Utilisateurs">
                    <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagesadmin/gestion_utilisateurs'); ?>">
                        <i class="bi bi-people"></i></a>
                </div>
                <div class="sidebar-icon" data-bs-toggle="tooltip" title="Gestion Demandes">
                    <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagesadmin/gestion_demandes'); ?>">
                        <i class="bi bi-folder2-open"></i></a>
                </div>
                <div class="sidebar-icon" data-bs-toggle="tooltip" title="Rapport Activités">
                    <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagesadmin/rapport_activites'); ?>">
                        <i class="bi bi-bar-chart"></i></a>
                </div>
                <div class="sidebar-icon" data-bs-toggle="tooltip" title="Gestion Réclamations">
                    <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagesadmin/gestion_reclamations'); ?>">
                        <i class="bi bi-exclamation-diamond"></i></a>
                </div>
                <div class="sidebar-icon" data-bs-toggle="tooltip" title="Administration Notifications">
                        <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagesadmin/administration_notifications'); ?>">
                        <i class="bi bi-bell"></i></a>
                </div>
    </div>
</div>

<div class="main-content">
        <!-- Le contenu des pages externes sera chargé ici -->
        <?php
        if (isset($_REQUEST["page"])) {
            $page = base64_decode($_REQUEST["page"]) . ".php";
            if (file_exists($page)) {
                include ($page);
            } else {
                echo 'Page nom disponible sur le serveur';
            }
        } else {
            include 'home.php';
        }
        ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>
</body>
</html>



<script>
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '1';
                let fadeEffect = setInterval(function () {
                    if (!alert.style.opacity) {
                        alert.style.opacity = 1;
                    }
                    if (alert.style.opacity > 0) {
                        alert.style.opacity -= 0.1;
                    } else {
                        clearInterval(fadeEffect);
                        alert.remove();
                    }
                }, 50);
            }, 5000);
        });
    });
</script>
