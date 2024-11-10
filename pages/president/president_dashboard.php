<?php
session_start();
if(!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
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
</head>
<body>
<?php
$url = "president_dashboard.php";
$pageTitle = 'Home';
if (isset($_REQUEST['page'])) {
    $requestedPage = base64_decode($_REQUEST['page']);

    switch ($requestedPage) {
        case '../profile':
            $pageTitle = 'Profile';
            break;
        case 'pagespresident/traitement_demandes':
            $pageTitle = 'Traitement Demandes';
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
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
                    <i class="bi bi-person me-2"></i><?php echo "$prenom";?></a>
            </li>
            <ul class="d-flex">
                <li class="p-2 d-inline">
                    <a href="../../config/logout.php" class="btn btn-danger" data-bs-toggle="tooltip" title="Deconnexion"><i class="bi bi-box-arrow-right"></i></a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="sidebar">
    <div class="sidebar-icons">
        <div class="sidebar-icon" data-bs-toggle="tooltip" title="Profile">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagespresident/profile'); ?>">
                <i class="bi bi-person"></i></a>
        </div>
        <div class="sidebar-icon" data-bs-toggle="tooltip" title="Traitement Des Demandes">
            <a href="<?php echo $url; ?>?page=<?php echo base64_encode('pagespresident/traitement_demandes'); ?>">
                <i class="bi bi-list-check"></i></a>
        </div>
    </div>
</div>

<div class="main-content">
    <?php
    if (isset($_REQUEST["page"])) {
        $page = base64_decode($_REQUEST["page"]) . ".php";
        if (file_exists($page)) {
            include ($page);
        } else {
            echo 'Page non disponible sur le serveur';
        }
    } else {
        include 'home.php';
    }
    ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script>
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>
</body>
</html>