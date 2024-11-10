<?php
    require_once('../../config/database.php');

    // Handle AJAX requests
    if(isset($_POST['action'])) {
        $response = ['success' => false, 'message' => ''];
        
        switch($_POST['action']) {
            case 'updateStatus':
                try {
                    $sql = "UPDATE commande SET statutcommande = :statut WHERE id_commande = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        'statut' => $_POST['statut'],
                        'id' => $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
                    exit;
                } catch(PDOException $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
                break;
                case 'details':
                    try {
                        $sql = "SELECT c.*, u.nom, u.prenom, u.email, u.telephone, 
                                dc.quantite, dc.totalcommande, p.nomproduit, p.prixunitaire
                                FROM commande c
                                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                                JOIN detailscommande dc ON c.id_commande = dc.id_commande
                                JOIN produit p ON dc.id_produit = p.id_produit
                                WHERE c.id_commande = :id";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute(['id' => $_POST['id']]);
                        $details = $stmt->fetchAll();
                        echo json_encode(['success' => true, 'data' => $details]);
                        exit;
                    } catch(PDOException $e) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                        exit;
                    }
                    break;

                case 'genererFacture':
                    try {
                        // Get order details
                        $sql = "SELECT c.*, u.nom, u.prenom, u.email, u.telephone, u.adresse,
                                dc.quantite, dc.totalcommande, p.nomproduit, p.prixunitaire
                                FROM commande c
                                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                                JOIN detailscommande dc ON c.id_commande = dc.id_commande
                                JOIN produit p ON dc.id_produit = p.id_produit
                                WHERE c.id_commande = :id";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute(['id' => $_POST['id']]);
                        $details = $stmt->fetchAll();

                        // Generate invoice number
                        $invoiceNumber = 'FACT-' . date('Y') . '-' . str_pad($_POST['id'], 6, '0', STR_PAD_LEFT);

                        // Create invoice in database
                        $sql = "INSERT INTO facture (id_commande, id_utilisateur, montanttotal, statutfacture) 
                                VALUES (:id_commande, :id_utilisateur, :montanttotal, 'non payée')";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([
                            'id_commande' => $_POST['id'],
                            'id_utilisateur' => $details[0]['id_utilisateur'],
                            'montanttotal' => $details[0]['total']
                        ]);

                        // Generate HTML for invoice
                        $html = '
                            <div class="invoice-header">
                                <h2>FACTURE N° ' . $invoiceNumber . '</h2>
                                <p>Date: ' . date('d/m/Y') . '</p>
                            </div>
                            <div class="client-info">
                                <h4>Client:</h4>
                                <p>' . $details[0]['nom'] . ' ' . $details[0]['prenom'] . '</p>
                                <p>' . $details[0]['adresse'] . '</p>
                                <p>' . $details[0]['email'] . '</p>
                                <p>' . $details[0]['telephone'] . '</p>
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Prix unitaire</th>
                                        <th>Quantité</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>';

                        foreach($details as $item) {
                            $html .= '<tr>
                                        <td>' . $item['nomproduit'] . '</td>
                                        <td>' . number_format($item['prixunitaire'], 2) . ' €</td>
                                        <td>' . $item['quantite'] . '</td>
                                        <td>' . number_format($item['totalcommande'], 2) . ' €</td>
                                    </tr>';
                        }

                        $html .= '</tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>' . number_format($details[0]['total'], 2) . ' €</strong></td>
                                    </tr>
                                </tfoot>
                            </table>';

                        echo json_encode([
                            'success' => true, 
                            'html' => $html,
                            'invoiceNumber' => $invoiceNumber
                        ]);
                        exit;
                    } catch(PDOException $e) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                        exit;
                    }
                    break;
            }
    }

    // Fetch all orders
    $sql = "SELECT c.*, u.nom, u.prenom 
            FROM commande c 
            JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
            ORDER BY c.datecommande DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $commandes = $stmt->fetchAll();
    ?>

    <div class="container-fluid px-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-shopping-cart me-1"></i>
                Liste des commandes
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Mode d'achat</th>
                            <th>Statut</th>
                            <th>Paiement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($commandes as $commande): ?>
                        <tr>
                            <td><?php echo $commande['id_commande']; ?></td>
                            <td><?php echo $commande['nom'] . ' ' . $commande['prenom']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($commande['datecommande'])); ?></td>
                            <td><?php echo number_format($commande['total'], 2) . ' €'; ?></td>
                            <td><?php echo $commande['modeachat']; ?></td>
                            <td>
                                <select class="form-select form-select-sm status-select" 
                                        onchange="updateStatus(<?php echo $commande['id_commande']; ?>, this.value)">
                                    <option value="en attente" <?php echo $commande['statutcommande'] == 'en attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="en cours" <?php echo $commande['statutcommande'] == 'en cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="terminée" <?php echo $commande['statutcommande'] == 'terminée' ? 'selected' : ''; ?>>Terminée</option>
                                    <option value="annulée" <?php echo $commande['statutcommande'] == 'annulée' ? 'selected' : ''; ?>>Annulée</option>
                                </select>
                            </td>
                            <td><?php echo $commande['methode_paiement']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="voirDetails(<?php echo $commande['id_commande']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="genererFacture(<?php echo $commande['id_commande']; ?>)">
                                    <i class="fas fa-file-invoice"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Détails Commande -->
    <div class="modal fade" id="detailsCommandeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la commande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Content will be dynamically loaded -->
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateStatus(id, newStatus) {
        const formData = new FormData();
        formData.append('action', 'updateStatus');
        formData.append('id', id);
        formData.append('statut', newStatus);

        fetch('gestioncommandes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour du statut');
            }
        });
    }

    function voirDetails(id) {
        const formData = new FormData();
        formData.append('action', 'details');
        formData.append('id', id);

        fetch('gestioncommandes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                let detailsHtml = `
                    <div class="container">
                        <h6>Informations client</h6>
                        <p>Nom: ${data.data[0].nom} ${data.data[0].prenom}</p>
                        <p>Email: ${data.data[0].email}</p>
                        <p>Téléphone: ${data.data[0].telephone}</p>
                        
                        <h6 class="mt-4">Produits commandés</h6>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.data.forEach(item => {
                    detailsHtml += `
                        <tr>
                            <td>${item.nomproduit}</td>
                            <td>${item.prixunitaire} €</td>
                            <td>${item.quantite}</td>
                            <td>${item.totalcommande} €</td>
                        </tr>
                    `;
                });

                detailsHtml += `
                            </tbody>
                        </table>
                    </div>
                `;

                document.querySelector('#detailsCommandeModal .modal-body').innerHTML = detailsHtml;
                const modal = new bootstrap.Modal(document.getElementById('detailsCommandeModal'));
                modal.show();
            }
        });
    }

    function genererFacture(id) {
        const formData = new FormData();
        formData.append('action', 'genererFacture');
        formData.append('id', id);

        fetch('gestioncommandes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Create a new window for the invoice
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Facture ${data.invoiceNumber}</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            .invoice-header { text-align: center; margin-bottom: 30px; }
                            .client-info { margin-bottom: 30px; }
                            @media print {
                                .no-print { display: none; }
                                body { padding: 20px; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <button onclick="window.print()" class="btn btn-primary no-print mb-3">Imprimer</button>
                            ${data.html}
                        </div>
                    </body>
                    </html>
                `);
                printWindow.document.close();
            } else {
                alert('Erreur lors de la génération de la facture');
            }
        });
    }    </script>
