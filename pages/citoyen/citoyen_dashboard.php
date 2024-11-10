<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$prenom = $_SESSION['user']['Prenom'];

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../Assets/css/style.css" rel="stylesheet" type="text/css"/>
    <link href="../../Assets/css/style2.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<?php $url = "citoyen_dashboard.php";
$pageTitle = 'Home';
if (isset($_REQUEST['page'])) {
    $requestedPage = base64_decode($_REQUEST['page']);

    switch ($requestedPage) {
        case '../profile':
            $pageTitle = 'Profile';
            break;
        case 'pagescitoyen/demande_cni':
            $pageTitle = 'Demande De CNI';
            break;
        case 'pagescitoyen/demande_certificat':
            $pageTitle = 'Demande De Certificat';
            break;
        case 'pagescitoyen/suivi_demande':
            $pageTitle = 'Suivi Demande';
            break;
        case 'pagescitoyen/telechargement_document' :
            $pageTitle = 'Telechargement Des Documents';
            break;
        case 'pagescitoyen/reclamation':
            $pageTitle = 'Reclamation';
            break;
        case '../change_password':
            $pageTitle = 'Changer Mot de passe';
            break;
        case 'details_demande':
            $pageTitle = "Details demande";
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
                    <a class="nav-link" id="back-button" data-bs-toggle="tooltip" title="Accueil" href="?page=<?php echo base64_encode('home'); ?>" >
                        <i class="bi bi-chevron-left me-2"></i>
                        <span class="navbar-text"><?php echo $pageTitle; ?></span>
                    </a>
                </li>

            </ul>
            <li class="px-5 p-2 d-inline">
                <a class="nav-link header-link btn btn-success" data-bs-toggle="tooltip" title="Profile" href="<?php echo $url; ?>?page=<?php echo base64_encode('../profile'); ?>" >
                    <i class="fab fa-500px mr-2"></i><?php echo "$prenom";?></a>
            </li>
            <ul class="d-flex">
                <li class="p-2 d-inline">
                    <a href="../../config/logout.php" class="btn btn-danger" data-bs-toggle="tooltip" title="Deconnexion"><i class="bi bi-box-arrow-in-right"></i></a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="sidebar">
    <div class="sidebar-icons">
        <!--<div class="sidebar-icon" data-bs-toggle="tooltip" title="Profile">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagescitoyen/profile'); ?>">
                <i class="bi bi-person"></i></a>
        </div>-->
        <div class="sidebar-icon" data-bs-toggle="tooltip" title="Demande De CNI">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagescitoyen/demande_cni'); ?>">
                <i class="bi bi-card-text"></i></a>
        </div>
        <div class="sidebar-icon" data-bs-toggle="tooltip" title="Demande De Certificat">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagescitoyen/demande_certificat'); ?>">
                <i class="bi bi-file-earmark-text"></i></a>
        </div>
        <div class="sidebar-icon" data-bs-toggle="tooltip" title="Suivi Demande">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagescitoyen/suivi_demande'); ?>">
                <i class="bi bi-clipboard-check"></i></a>
        </div>
        <!--<div class="sidebar-icon" data-bs-toggle="tooltip" title="Historique Demandes">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagescitoyen/historique_demande'); ?>">
                <i class="bi bi-clock-history"></i></a>
        </div>-->
        <div class="sidebar-icon" data-bs-toggle="tooltip" title="Telechargement Des Documents">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagescitoyen/telechargement_document'); ?>">
                <i class="bi bi-download"></i></a>
        </div>
        <div class="sidebar-icon" data-bs-toggle="tooltip" title="Reclamation">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagescitoyen/reclamation'); ?>">
                <i class="bi bi-exclamation-circle"></i></a>
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