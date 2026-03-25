<?php
/**
 * pages/management.php
 * Produktverwaltung (nur für Admins) - Vollständige korrigierte Version
 */

if (!isAdmin()) {
    echo '<script>window.location.href = "?page=403";</script>';
    return;
}

$action = $_GET['action'] ?? 'overview';
$vehicleId = $_GET['vehicle_id'] ?? null;
$containerId = $_GET['container_id'] ?? null;
$compartmentId = $_GET['compartment_id'] ?? null;

$db = getDB();
$redirectScript = null;
$error = null;
$successMessage = null;

// POST-Requests verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    switch ($postAction) {
        case 'add_vehicle':
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? '';
            $description = trim($_POST['description'] ?? '');
            
            if ($name && $type) {
                try {
                    $db->query(
                        "INSERT INTO vehicles (name, type, description) VALUES (?, ?, ?)",
                        [$name, $type, $description]
                    );
                    $redirectScript = '<script>window.location.href = "?page=management&success=vehicle_added";</script>';
                } catch (Exception $e) {
                    $error = 'Fehler beim Hinzufügen des Fahrzeugs.';
                }
            } else {
                $error = 'Name und Typ sind erforderlich.';
            }
            break;
            
        case 'add_container':
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? '';
            $colorCode = $_POST['color_code'] ?? '';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            
            if ($vehicleId && $name && $type) {
                try {
                    $db->query(
                        "INSERT INTO containers (vehicle_id, name, type, color_code, sort_order) VALUES (?, ?, ?, ?, ?)",
                        [$vehicleId, $name, $type, $colorCode, $sortOrder]
                    );
                    $redirectScript = '<script>window.location.href = "?page=management&action=edit_vehicle&id=' . $vehicleId . '&success=container_added";</script>';
                } catch (Exception $e) {
                    $error = 'Fehler beim Hinzufügen des Behälters.';
                }
            } else {
                $error = 'Fahrzeug-ID, Name und Typ sind erforderlich.';
            }
            break;
            
        case 'add_compartment':
            $containerId = (int)($_POST['container_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $colorCode = $_POST['color_code'] ?? '';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            
            if ($containerId && $name) {
                try {
                    $db->query(
                        "INSERT INTO compartments (container_id, name, color_code, sort_order) VALUES (?, ?, ?, ?)",
                        [$containerId, $name, $colorCode, $sortOrder]
                    );
                    $container = $db->fetchOne("SELECT vehicle_id FROM containers WHERE id = ?", [$containerId]);
                    $redirectScript = '<script>window.location.href = "?page=management&action=edit_container&id=' . $containerId . '&vehicle_id=' . $container['vehicle_id'] . '&success=compartment_added";</script>';
                } catch (Exception $e) {
                    $error = 'Fehler beim Hinzufügen des Fachs.';
                }
            } else {
                $error = 'Container-ID und Name sind erforderlich.';
            }
            break;
            
        case 'add_product':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $hasExpiry = isset($_POST['has_expiry']) ? 1 : 0;
            
            if ($name) {
                try {
                    $db->query(
                        "INSERT INTO products (name, description, has_expiry) VALUES (?, ?, ?)",
                        [$name, $description, $hasExpiry]
                    );
                    $redirectScript = '<script>window.location.href = "?page=management&action=products&success=product_added";</script>';
                } catch (Exception $e) {
                    $error = 'Fehler beim Hinzufügen des Produkts.';
                }
            } else {
                $error = 'Produktname ist erforderlich.';
            }
            break;
            
        case 'add_target_product':
            $compartmentId = (int)($_POST['compartment_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            $containerId = (int)($_POST['container_id'] ?? 0);
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            
            if ($compartmentId && $productId && $quantity > 0) {
                try {
                    // Prüfen ob schon vorhanden
                    $existing = $db->fetchOne(
                        "SELECT id FROM compartment_products_target WHERE compartment_id = ? AND product_id = ?",
                        [$compartmentId, $productId]
                    );
                    
                    if ($existing) {
                        // Menge aktualisieren
                        $db->query(
                            "UPDATE compartment_products_target SET quantity = ? WHERE compartment_id = ? AND product_id = ?",
                            [$quantity, $compartmentId, $productId]
                        );
                    } else {
                        // Neu hinzufügen
                        $db->query(
                            "INSERT INTO compartment_products_target (compartment_id, product_id, quantity) VALUES (?, ?, ?)",
                            [$compartmentId, $productId, $quantity]
                        );
                    }
                    
                    $redirectScript = '<script>window.location.href = "?page=management&action=edit_compartment&id=' . $compartmentId . '&container_id=' . $containerId . '&vehicle_id=' . $vehicleId . '&success=product_added_to_target";</script>';
                } catch (Exception $e) {
                    $error = 'Fehler beim Hinzufügen des Produkts.';
                }
            } else {
                $error = 'Ungültige Daten.';
            }
            break;
            
        case 'remove_target_product':
            $compartmentId = (int)($_POST['compartment_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            $containerId = (int)($_POST['container_id'] ?? 0);
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            
            if ($compartmentId && $productId) {
                try {
                    $db->query(
                        "DELETE FROM compartment_products_target WHERE compartment_id = ? AND product_id = ?",
                        [$compartmentId, $productId]
                    );
                    $redirectScript = '<script>window.location.href = "?page=management&action=edit_compartment&id=' . $compartmentId . '&container_id=' . $containerId . '&vehicle_id=' . $vehicleId . '&success=product_removed_from_target";</script>';
                } catch (Exception $e) {
                    $error = 'Fehler beim Entfernen des Produkts.';
                }
            } else {
                $error = 'Ungültige Daten.';
            }
            break;
            
        case 'update_target_quantity':
            $compartmentId = (int)($_POST['compartment_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            $containerId = (int)($_POST['container_id'] ?? 0);
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            
            if ($compartmentId && $productId && $quantity > 0) {
                try {
                    $db->query(
                        "UPDATE compartment_products_target SET quantity = ? WHERE compartment_id = ? AND product_id = ?",
                        [$quantity, $compartmentId, $productId]
                    );
                    $redirectScript = '<script>window.location.href = "?page=management&action=edit_compartment&id=' . $compartmentId . '&container_id=' . $containerId . '&vehicle_id=' . $vehicleId . '&success=quantity_updated";</script>';
                } catch (Exception $e) {
                    $error = 'Fehler beim Aktualisieren der Menge.';
                }
            } else {
                $error = 'Ungültige Daten.';
            }
            break;
        
        case 'edit_product':
            $productId = (int)($_POST['product_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $hasExpiry = isset($_POST['has_expiry']) ? 1 : 0;
            
            if (!$productId || empty($name)) {
                $error = 'Ungültige Daten.';
            } else {
                try {
                    $db->query(
                        "UPDATE products SET name = ?, description = ?, has_expiry = ? WHERE id = ?",
                        [$name, $description, $hasExpiry, $productId]
                    );
                    $redirectScript = '<script>window.location.href = "?page=management&action=products&success=product_updated";</script>';
                } catch (Exception $e) {
                    $error = 'Fehler beim Aktualisieren des Produkts.';
                }
            }
            break;

        case 'delete_product':
            $productId = (int)($_POST['product_id'] ?? 0);
            
            if (!$productId) {
                $error = 'Ungültige Produkt-ID.';
            } else {
                try {
                    // Prüfen ob Produkt in SOLL-Bestückungen verwendet wird
                    $usageCount = $db->fetchColumn(
                        "SELECT COUNT(*) FROM compartment_products_target WHERE product_id = ?",
                        [$productId]
                    );
                    
                    if ($usageCount > 0) {
                        $error = "Produkt kann nicht gelöscht werden - es wird in $usageCount Fächern verwendet.";
                    } else {
                        $db->query("DELETE FROM products WHERE id = ?", [$productId]);
                        $redirectScript = '<script>window.location.href = "?page=management&action=products&success=product_deleted";</script>';
                    }
                } catch (Exception $e) {
                    $error = 'Fehler beim Löschen des Produkts.';
                }
            }
            break;
            
        case 'delete_compartment':
            $compartmentId = (int)($_POST['compartment_id'] ?? 0);
            $containerId = (int)($_POST['container_id'] ?? 0);
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            
            if (!$compartmentId) {
                $error = 'Ungültige Fach-ID.';
            } else {
                try {
                    // Prüfen ob Fach verwendet wird
                    $targetUsage = $db->fetchColumn(
                        "SELECT COUNT(*) FROM compartment_products_target WHERE compartment_id = ?",
                        [$compartmentId]
                    );
                    
                    $actualUsage = $db->fetchColumn(
                        "SELECT COUNT(*) FROM compartment_products_actual WHERE compartment_id = ?",
                        [$compartmentId]
                    );
                    
                    $inspectionUsage = $db->fetchColumn(
                        "SELECT COUNT(*) FROM inspection_items WHERE compartment_id = ?",
                        [$compartmentId]
                    );
                    
                    $totalUsage = $targetUsage + $actualUsage + $inspectionUsage;
                    
                    if ($totalUsage > 0) {
                        // Fach nur deaktivieren
                        $db->query("UPDATE compartments SET active = 0 WHERE id = ?", [$compartmentId]);
                        $successMessage = 'Fach wurde deaktiviert (Daten vorhanden).';
                    } else {
                        // Fach löschen
                        $db->query("DELETE FROM compartments WHERE id = ?", [$compartmentId]);
                        $successMessage = 'Fach wurde gelöscht.';
                    }
                    
                    $redirectScript = '<script>window.location.href = "?page=management&action=edit_container&id=' . $containerId . '&vehicle_id=' . $vehicleId . '&success=compartment_deleted";</script>';
                    
                } catch (Exception $e) {
                    $error = 'Fehler beim Löschen des Fachs.';
                }
            }
            break;
            
        case 'delete_container':
            $containerId = (int)($_POST['container_id'] ?? 0);
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            
            if (!$containerId) {
                $error = 'Ungültige Behälter-ID.';
            } else {
                try {
                    // Prüfen ob Behälter Fächer mit Daten hat
                    $compartmentUsage = $db->fetchColumn(
                        "SELECT COUNT(*) FROM (
                            SELECT DISTINCT comp.id 
                            FROM compartments comp 
                            LEFT JOIN compartment_products_target cpt ON comp.id = cpt.compartment_id
                            LEFT JOIN compartment_products_actual cpa ON comp.id = cpa.compartment_id  
                            LEFT JOIN inspection_items ii ON comp.id = ii.compartment_id
                            WHERE comp.container_id = ? 
                            AND (cpt.id IS NOT NULL OR cpa.id IS NOT NULL OR ii.id IS NOT NULL)
                        ) as used_compartments",
                        [$containerId]
                    );
                    
                    // Prüfen ob Container in Prüfungen verwendet wird
                    $containerInspectionUsage = $db->fetchColumn(
                        "SELECT COUNT(*) FROM container_inspection_items WHERE container_id = ?",
                        [$containerId]
                    );
                    
                    if ($compartmentUsage > 0 || $containerInspectionUsage > 0) {
                        // Behälter nur deaktivieren
                        $db->query("UPDATE containers SET active = 0 WHERE id = ?", [$containerId]);
                        $successMessage = 'Behälter wurde deaktiviert (wird in Prüfungen/Daten verwendet).';
                    } else {
                        // Alle Fächer des Behälters löschen (kaskadierend)
                        $db->query("DELETE FROM compartments WHERE container_id = ?", [$containerId]);
                        // Behälter löschen
                        $db->query("DELETE FROM containers WHERE id = ?", [$containerId]);
                        $successMessage = 'Behälter wurde gelöscht.';
                    }
                    
                    $redirectScript = '<script>window.location.href = "?page=management&action=edit_vehicle&id=' . $vehicleId . '&success=container_deleted";</script>';
                    
                } catch (Exception $e) {
                    $error = 'Fehler beim Löschen des Behälters: ' . $e->getMessage();
                    error_log("Container delete error: " . $e->getMessage());
                }
            }
            break;
    }
}

// JavaScript Redirect ausgeben wenn gesetzt
if ($redirectScript) {
    echo $redirectScript;
    return;
}

$vehicles = getVehicles(false); // Auch inaktive laden
?>

<div class="container-fluid">
    <div class="page-header">
        <h1>Produktverwaltung</h1>
        <p>Fahrzeuge, Behälter, Fächer und Produkte verwalten</p>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="success-message">
        <?php
        $messages = [
            'vehicle_added' => 'Fahrzeug erfolgreich hinzugefügt',
            'container_added' => 'Behälter erfolgreich hinzugefügt',
            'container_deleted' => 'Behälter erfolgreich gelöscht',
            'compartment_added' => 'Fach erfolgreich hinzugefügt',
            'compartment_deleted' => 'Fach erfolgreich gelöscht',
            'product_added' => 'Produkt erfolgreich hinzugefügt',
            'product_updated' => 'Produkt erfolgreich aktualisiert',
            'product_deleted' => 'Produkt erfolgreich gelöscht',
            'target_updated' => 'SOLL-Bestückung erfolgreich aktualisiert',
            'product_added_to_target' => 'Produkt zur SOLL-Bestückung hinzugefügt',
            'product_removed_from_target' => 'Produkt aus SOLL-Bestückung entfernt',
            'quantity_updated' => 'Menge aktualisiert'
        ];
        echo h($messages[$_GET['success']] ?? 'Aktion erfolgreich');
        ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="error-message">
        <?= h($error) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($successMessage): ?>
    <div class="success-message">
        <?= h($successMessage) ?>
    </div>
    <?php endif; ?>
    
    <!-- Navigation Tabs -->
    <div class="management-tabs">
        <a href="?page=management&action=overview" class="tab-btn <?= $action === 'overview' ? 'active' : '' ?>">
            Übersicht
        </a>
        <a href="?page=management&action=products" class="tab-btn <?= $action === 'products' ? 'active' : '' ?>">
            Produkte
        </a>
    </div>
    
    <?php switch ($action):
        case 'overview': ?>
        <!-- Fahrzeugübersicht -->
        <div class="management-section">
            <div class="section-header">
                <h2>Fahrzeuge</h2>
                <button class="btn btn-primary" onclick="showAddVehicleModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Fahrzeug hinzufügen
                </button>
            </div>
            
            <div class="vehicles-management-grid">
                <?php foreach ($vehicles as $vehicle): ?>
                <div class="management-card">
                    <div class="card-header">
                        <h3><?= h($vehicle['name']) ?></h3>
                        <span class="vehicle-type-badge"><?= h($vehicle['type']) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if ($vehicle['description']): ?>
                        <p><?= h($vehicle['description']) ?></p>
                        <?php endif; ?>
                        
                        <?php
                        $containerCount = $db->fetchColumn(
                            "SELECT COUNT(*) FROM containers WHERE vehicle_id = ? AND active = 1", 
                            [$vehicle['id']]
                        );
                        $compartmentCount = $db->fetchColumn(
                            "SELECT COUNT(*) FROM compartments comp 
                             JOIN containers cont ON comp.container_id = cont.id 
                             WHERE cont.vehicle_id = ? AND comp.active = 1 AND cont.active = 1", 
                            [$vehicle['id']]
                        );
                        ?>
                        
                        <div class="vehicle-stats">
                            <span><?= $containerCount ?> Behälter</span>
                            <span><?= $compartmentCount ?> Fächer</span>
                        </div>
                    </div>
                    <div class="card-actions">
                        <a href="?page=management&action=edit_vehicle&id=<?= $vehicle['id'] ?>" 
                           class="btn btn-outline btn-small">
                            Bearbeiten
                        </a>
                        <?php if (!$vehicle['active']): ?>
                        <span class="badge badge-danger">Inaktiv</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php break;
        case 'edit_vehicle': 
            $vehicleId = (int)($_GET['id'] ?? 0);
            $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE id = ?", [$vehicleId]);
            if (!$vehicle) {
                echo '<div class="error-message">Fahrzeug nicht gefunden.</div>';
                break;
            }
            
            $containers = $db->fetchAll(
                "SELECT * FROM containers WHERE vehicle_id = ? AND active = 1 ORDER BY sort_order, name",
                [$vehicleId]
            );
        ?>
        
        <div class="breadcrumb">
            <a href="?page=management">Produktverwaltung</a> › 
            <span><?= h($vehicle['name']) ?></span>
        </div>
        
        <div class="management-section">
            <div class="section-header">
                <h2><?= h($vehicle['name']) ?> - Behälter</h2>
                <button class="btn btn-primary" onclick="showAddContainerModal(<?= $vehicleId ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Behälter hinzufügen
                </button>
            </div>
            
            <div class="containers-grid">
                <?php foreach ($containers as $container): ?>
                <div class="management-card container-card">
                    <div class="card-header">
                        <div class="container-header-content">
                            <?php if ($container['color_code']): ?>
                            <div class="color-indicator" style="background-color: <?= h($container['color_code']) ?>"></div>
                            <?php endif; ?>
                            <h3><?= h($container['name']) ?></h3>
                            <span class="container-type-badge"><?= h($container['type']) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($container['photo_path']): ?>
                    <div class="container-photo">
                        <img src="<?= h($container['photo_path']) ?>" alt="<?= h($container['name']) ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <?php
                        $compartmentCount = $db->fetchColumn(
                            "SELECT COUNT(*) FROM compartments WHERE container_id = ? AND active = 1", 
                            [$container['id']]
                        );
                        ?>
                        <div class="container-stats">
                            <span><?= $compartmentCount ?> Fächer</span>
                            <span>Reihenfolge: <?= $container['sort_order'] ?></span>
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <a href="?page=management&action=edit_container&id=<?= $container['id'] ?>&vehicle_id=<?= $vehicleId ?>" 
                           class="btn btn-outline btn-small">
                            Fächer bearbeiten
                        </a>
                        <button class="btn btn-secondary btn-small" onclick="uploadContainerPhoto(<?= $container['id'] ?>)">
                            Foto
                        </button>
                        <button class="btn btn-secondary btn-small" onclick="renameContainer(<?= $container['id'] ?>, '<?= addslashes($container['name']) ?>')">
                            Umbenennen
                        </button>
                        <button class="btn btn-danger btn-small" onclick="deleteContainer(<?= $container['id'] ?>, <?= $vehicleId ?>, '<?= h($container['name']) ?>')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                <polyline points="3,6 5,6 21,6"/>
                                <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                            </svg>
                            Löschen
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php break;
        case 'edit_container':
            $containerId = (int)($_GET['id'] ?? 0);
            $vehicleId = (int)($_GET['vehicle_id'] ?? 0);
            
            $container = $db->fetchOne("SELECT * FROM containers WHERE id = ?", [$containerId]);
            $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE id = ?", [$vehicleId]);
            
            if (!$container || !$vehicle) {
                echo '<div class="error-message">Container oder Fahrzeug nicht gefunden.</div>';
                break;
            }
            
            $compartments = $db->fetchAll(
                "SELECT * FROM compartments WHERE container_id = ? AND active = 1 ORDER BY sort_order, name",
                [$containerId]
            );
        ?>
        
        <div class="breadcrumb">
            <a href="?page=management">Produktverwaltung</a> › 
            <a href="?page=management&action=edit_vehicle&id=<?= $vehicleId ?>"><?= h($vehicle['name']) ?></a> › 
            <span><?= h($container['name']) ?></span>
        </div>
        
        <div class="management-section">
            <div class="section-header">
                <h2><?= h($container['name']) ?> - Fächer</h2>
                <button class="btn btn-primary" onclick="showAddCompartmentModal(<?= $containerId ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Fach hinzufügen
                </button>
            </div>
            
            <div class="compartments-grid">
                <?php foreach ($compartments as $compartment): ?>
                <div class="management-card compartment-card">
                    <div class="card-header">
                        <div class="compartment-header-content">
                            <?php if ($compartment['color_code']): ?>
                            <div class="color-indicator" style="background-color: <?= h($compartment['color_code']) ?>"></div>
                            <?php endif; ?>
                            <h3><?= h($compartment['name']) ?></h3>
                        </div>
                    </div>
                    
                    <?php if ($compartment['photo_path']): ?>
                    <div class="compartment-photo">
                        <img src="<?= h($compartment['photo_path']) ?>" alt="<?= h($compartment['name']) ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <?php
                        $targetCount = $db->fetchColumn(
                            "SELECT COUNT(*) FROM compartment_products_target WHERE compartment_id = ?", 
                            [$compartment['id']]
                        );
                        ?>
                        <div class="compartment-stats">
                            <span><?= $targetCount ?> SOLL-Produkte</span>
                            <span>Reihenfolge: <?= $compartment['sort_order'] ?></span>
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <a href="?page=management&action=edit_compartment&id=<?= $compartment['id'] ?>&container_id=<?= $containerId ?>&vehicle_id=<?= $vehicleId ?>" 
                           class="btn btn-outline btn-small">
                            SOLL-Bestückung
                        </a>
                        <button class="btn btn-secondary btn-small" onclick="uploadCompartmentPhoto(<?= $compartment['id'] ?>)">
                            Foto
                        </button>
                        <button class="btn btn-secondary btn-small" onclick="renameCompartment(<?= $compartment['id'] ?>, '<?= addslashes($compartment['name']) ?>')">
                            Umbenennen
                        </button>
                        <button class="btn btn-danger btn-small" onclick="deleteCompartment(<?= $compartment['id'] ?>, <?= $containerId ?>, <?= $vehicleId ?>, '<?= h($compartment['name']) ?>')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                <polyline points="3,6 5,6 21,6"/>
                                <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                            </svg>
                            Löschen
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php break;
        case 'edit_compartment':
            $compartmentId = (int)($_GET['id'] ?? 0);
            $containerId = (int)($_GET['container_id'] ?? 0);
            $vehicleId = (int)($_GET['vehicle_id'] ?? 0);
            
            $compartment = $db->fetchOne("SELECT * FROM compartments WHERE id = ?", [$compartmentId]);
            $container = $db->fetchOne("SELECT * FROM containers WHERE id = ?", [$containerId]);
            $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE id = ?", [$vehicleId]);
            
            if (!$compartment || !$container || !$vehicle) {
                echo '<div class="error-message">Fach, Container oder Fahrzeug nicht gefunden.</div>';
                break;
            }
            
            // Alle verfügbaren Produkte
            $allProducts = $db->fetchAll("SELECT * FROM products ORDER BY name");
            
            // Aktuelle SOLL-Bestückung
            $targetProducts = $db->fetchAll(
                "SELECT cpt.*, p.name as product_name 
                 FROM compartment_products_target cpt 
                 JOIN products p ON cpt.product_id = p.id 
                 WHERE cpt.compartment_id = ?",
                [$compartmentId]
            );
            
            $targetMap = [];
            foreach ($targetProducts as $target) {
                $targetMap[$target['product_id']] = $target['quantity'];
            }
        ?>
        
        <div class="breadcrumb">
            <a href="?page=management">Produktverwaltung</a> › 
            <a href="?page=management&action=edit_vehicle&id=<?= $vehicleId ?>"><?= h($vehicle['name']) ?></a> › 
            <a href="?page=management&action=edit_container&id=<?= $containerId ?>&vehicle_id=<?= $vehicleId ?>"><?= h($container['name']) ?></a> › 
            <span><?= h($compartment['name']) ?></span>
        </div>
        
        <div class="management-section">
            <div class="section-header">
                <h2><?= h($compartment['name']) ?> - SOLL-Bestückung</h2>
                <button type="button" class="btn btn-primary" onclick="showAddTargetProductModal(<?= $compartmentId ?>, <?= $containerId ?>, <?= $vehicleId ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Produkt hinzufügen
                </button>
            </div>
            
            <?php if (empty($targetProducts)): ?>
            <div class="empty-state">
                <p>Noch keine Produkte in der SOLL-Bestückung</p>
                <button type="button" class="btn btn-primary" onclick="showAddTargetProductModal(<?= $compartmentId ?>, <?= $containerId ?>, <?= $vehicleId ?>)">
                    Erstes Produkt hinzufügen
                </button>
            </div>
            <?php else: ?>
            <div class="target-products-list">
                <?php foreach ($targetProducts as $target): ?>
                <div class="target-product-item">
                    <div class="product-info-extended">
                        <h4><?= h($target['product_name']) ?></h4>
                        <form method="POST" class="inline-quantity-form">
                            <input type="hidden" name="action" value="update_target_quantity">
                            <input type="hidden" name="compartment_id" value="<?= $compartmentId ?>">
                            <input type="hidden" name="container_id" value="<?= $containerId ?>">
                            <input type="hidden" name="vehicle_id" value="<?= $vehicleId ?>">
                            <input type="hidden" name="product_id" value="<?= $target['product_id'] ?>">
                            
                            <div class="quantity-control">
                                <label>Menge:</label>
                                <input type="number" 
                                       name="quantity" 
                                       value="<?= $target['quantity'] ?>" 
                                       min="1" 
                                       max="999" 
                                       class="form-input quantity-field-inline"
                                       onchange="this.form.submit()">
                            </div>
                        </form>
                    </div>
                    <div class="product-actions">
                        <form method="POST" class="inline-form" onsubmit="return confirm('Produkt aus SOLL-Bestückung entfernen?');">
                            <input type="hidden" name="action" value="remove_target_product">
                            <input type="hidden" name="compartment_id" value="<?= $compartmentId ?>">
                            <input type="hidden" name="container_id" value="<?= $containerId ?>">
                            <input type="hidden" name="vehicle_id" value="<?= $vehicleId ?>">
                            <input type="hidden" name="product_id" value="<?= $target['product_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                    <polyline points="3,6 5,6 21,6"/>
                                    <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                                </svg>
                                Entfernen
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php break;
        case 'products':
            $products = $db->fetchAll("SELECT * FROM products ORDER BY name");
        ?>
        
        <div class="management-section">
            <div class="section-header">
                <h2>Produktkatalog</h2>
                <button class="btn btn-primary" onclick="showAddProductModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Produkt hinzufügen
                </button>
            </div>
            
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="management-card product-card">
                    <div class="card-header">
                        <h3><?= h($product['name']) ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($product['description']): ?>
                        <p><?= h($product['description']) ?></p>
                        <?php endif; ?>
                        
                        <?php
                        $usageCount = $db->fetchColumn(
                            "SELECT COUNT(DISTINCT compartment_id) FROM compartment_products_target WHERE product_id = ?",
                            [$product['id']]
                        );
                        ?>
                        <div class="product-stats">
                            <span>Verwendet in <?= $usageCount ?> Fächern</span>
                        </div>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-outline btn-small" onclick="editProduct(<?= $product['id'] ?>)">
                            Bearbeiten
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php break; ?>
    <?php endswitch; ?>
</div>

<!-- Modals -->
<!-- Add Vehicle Modal -->
<div class="modal" id="addVehicleModal">
    <div class="modal-content">
        <h3>Fahrzeug hinzufügen</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_vehicle">
            
            <div class="form-group">
                <label for="vehicle_name" class="form-label">Name *</label>
                <input type="text" id="vehicle_name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="vehicle_type" class="form-label">Typ *</label>
                <select id="vehicle_type" name="type" class="form-select" required>
                    <option value="">Bitte wählen</option>
                    <option value="RTW">RTW</option>
                    <option value="KTW">KTW</option>
                    <option value="GW-SAN">GW-SAN</option>
                    <option value="LAGER">Lager</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="vehicle_description" class="form-label">Beschreibung</label>
                <textarea id="vehicle_description" name="description" class="form-textarea"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Hinzufügen</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Container Modal -->
<div class="modal" id="addContainerModal">
    <div class="modal-content">
        <h3>Behälter hinzufügen</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_container">
            <input type="hidden" name="vehicle_id" id="container_vehicle_id">
            
            <div class="form-group">
                <label for="container_name" class="form-label">Name *</label>
                <input type="text" id="container_name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="container_type" class="form-label">Typ *</label>
                <select id="container_type" name="type" class="form-select" required>
                    <option value="">Bitte wählen</option>
                    <option value="schrank">Schrank</option>
                    <option value="rucksack">Rucksack</option>
                    <option value="koffer">Koffer</option>
                    <option value="kiste">Kiste</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="container_color" class="form-label">Farbcode</label>
                <input type="color" id="container_color" name="color_code" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="container_sort" class="form-label">Reihenfolge</label>
                <input type="number" id="container_sort" name="sort_order" class="form-input" value="0" min="0">
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Hinzufügen</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Compartment Modal -->
<div class="modal" id="addCompartmentModal">
    <div class="modal-content">
        <h3>Fach hinzufügen</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_compartment">
            <input type="hidden" name="container_id" id="compartment_container_id">
            
            <div class="form-group">
                <label for="compartment_name" class="form-label">Name *</label>
                <input type="text" id="compartment_name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="compartment_color" class="form-label">Farbcode</label>
                <input type="color" id="compartment_color" name="color_code" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="compartment_sort" class="form-label">Reihenfolge</label>
                <input type="number" id="compartment_sort" name="sort_order" class="form-input" value="0" min="0">
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Hinzufügen</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal" id="addProductModal">
    <div class="modal-content">
        <h3>Produkt hinzufügen</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_product">
            
            <div class="form-group">
                <label for="product_name" class="form-label">Produktname *</label>
                <input type="text" id="product_name" name="name" class="form-input" required 
                       placeholder="z.B. Mullbinde 10cm">
                <div class="form-help">Eindeutiger Name für das Medizinprodukt</div>
            </div>
            
            <div class="form-group">
                <label for="product_description" class="form-label">Beschreibung</label>
                <textarea id="product_description" name="description" class="form-textarea" 
                          placeholder="z.B. Sterile Mullbinde 10cm x 4m, einzeln verpackt"></textarea>
                <div class="form-help">Zusätzliche Informationen zum Produkt</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" id="product_has_expiry" name="has_expiry" value="1" checked>
                    MHD-pflichtig (Mindesthaltbarkeitsdatum erforderlich)
                </label>
                <small style="display: block; margin-top: 0.25rem; color: var(--gray-600);">
                    Wenn aktiviert: Beim Prüfen muss ein MHD eingegeben werden.<br>
                    Wenn deaktiviert: Nur Vorhanden/Nicht-Vorhanden wird geprüft.
                </small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Produkt hinzufügen</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Target Product Modal -->
<div class="modal" id="addTargetProductModal">
    <div class="modal-content">
        <h3>Produkt zur SOLL-Bestückung hinzufügen</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_target_product">
            <input type="hidden" name="compartment_id" id="target_compartment_id">
            <input type="hidden" name="container_id" id="target_container_id">
            <input type="hidden" name="vehicle_id" id="target_vehicle_id">
            
            <div class="form-group">
                <label for="target_product_id" class="form-label">Produkt *</label>
                <select id="target_product_id" name="product_id" class="form-select" required>
                    <option value="">Bitte wählen</option>
                    <?php
                    $allProducts = $db->fetchAll("SELECT * FROM products ORDER BY name");
                    foreach ($allProducts as $product):
                    ?>
                    <option value="<?= $product['id'] ?>"><?= h($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="target_quantity" class="form-label">Menge *</label>
                <input type="number" id="target_quantity" name="quantity" class="form-input" 
                       value="1" min="1" max="999" required>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Hinzufügen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal" id="editProductModal">
    <div class="modal-content">
        <h3>Produkt bearbeiten</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit_product_id">
            
            <div class="form-group">
                <label for="edit_product_name" class="form-label">Produktname *</label>
                <input type="text" id="edit_product_name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="edit_product_description" class="form-label">Beschreibung</label>
                <textarea id="edit_product_description" name="description" class="form-textarea"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" id="edit_product_has_expiry" name="has_expiry" value="1" checked>
                    MHD-pflichtig (Mindesthaltbarkeitsdatum erforderlich)
                </label>
                <small style="display: block; margin-top: 0.25rem; color: var(--gray-600);">
                    Wenn aktiviert: Beim Prüfen muss ein MHD eingegeben werden.<br>
                    Wenn deaktiviert: Nur Vorhanden/Nicht-Vorhanden wird geprüft.
                </small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Änderungen speichern</button>
                <button type="button" class="btn btn-danger" onclick="deleteProduct()">Produkt löschen</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteConfirmModal">
    <div class="modal-content">
        <h3>Produkt löschen</h3>
        <p id="deleteConfirmMessage">Möchtest du dieses Produkt wirklich löschen?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Löschen</button>
        </div>
    </div>
</div>

<style>
.management-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    border-bottom: 2px solid var(--border-color);
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--dark-gray);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition-fast);
}

.tab-btn.active,
.tab-btn:hover {
    color: var(--primary-red);
    border-bottom-color: var(--primary-red);
}

.management-section {
    margin-bottom: 32px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 24px;
    font-weight: 600;
    color: var(--primary-black);
    margin: 0;
}

.vehicles-management-grid,
.containers-grid,
.compartments-grid,
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.management-card {
    background: var(--primary-white);
    border-radius: 12px;
    box-shadow: var(--shadow-light);
    overflow: hidden;
    transition: var(--transition-fast);
}

.management-card:hover {
    box-shadow: var(--shadow-medium);
}

.management-card .card-header {
    padding: 16px 20px;
    background: var(--light-gray);
    border-bottom: 1px solid var(--border-color);
}

.management-card .card-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--primary-black);
    margin: 0;
}

.vehicle-type-badge,
.container-type-badge {
    display: inline-block;
    background: var(--primary-red);
    color: var(--primary-white);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    float: right;
}

.container-header-content,
.compartment-header-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.color-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid var(--border-color);
}

.vehicle-stats,
.container-stats,
.compartment-stats,
.product-stats {
    display: flex;
    gap: 16px;
    font-size: 14px;
    color: var(--dark-gray);
}

.container-photo,
.compartment-photo {
    height: 150px;
    overflow: hidden;
}

.container-photo img,
.compartment-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-actions {
    padding: 16px 20px;
    display: flex;
    gap: 8px;
    border-top: 1px solid var(--border-color);
}

.breadcrumb {
    margin-bottom: 20px;
    font-size: 14px;
    color: var(--dark-gray);
}

.breadcrumb a {
    color: var(--primary-red);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.target-products-form {
    max-width: 800px;
}

.products-list {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
}

.product-item:last-child {
    border-bottom: none;
}

.product-info h4 {
    font-size: 16px;
    font-weight: 500;
    color: var(--primary-black);
    margin-bottom: 4px;
}

.product-info p {
    font-size: 14px;
    color: var(--dark-gray);
    margin: 0;
}

.quantity-input {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-field {
    width: 80px;
    padding: 8px 12px;
    text-align: center;
}

.target-products-list {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    background: var(--primary-white);
}

.target-product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    gap: 20px;
}

.target-product-item:last-child {
    border-bottom: none;
}

.product-info-extended {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 20px;
}

.product-info-extended h4 {
    flex: 1;
    font-size: 16px;
    font-weight: 500;
    color: var(--primary-black);
    margin: 0;
}

.inline-quantity-form {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-control label {
    font-size: 14px;
    color: var(--dark-gray);
    margin: 0;
}

.quantity-field-inline {
    width: 70px;
    padding: 6px 10px;
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 14px;
}

.inline-form {
    display: inline;
    margin: 0;
}

.product-actions {
    display: flex;
    gap: 8px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--light-gray);
    border-radius: 12px;
    border: 2px dashed var(--border-color);
}

.empty-state p {
    font-size: 16px;
    color: var(--dark-gray);
    margin-bottom: 20px;
}

.form-actions {
    margin-top: 24px;
    text-align: center;
}

.error-message {
    background: rgba(220, 20, 60, 0.1);
    border: 1px solid var(--danger-red);
    color: var(--danger-red);
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.success-message {
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid var(--success-green);
    color: var(--success-green);
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .vehicles-management-grid,
    .containers-grid,
    .compartments-grid,
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .product-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .quantity-input {
        align-self: flex-end;
    }
}
</style>

<script>
function showAddVehicleModal() {
    $('#addVehicleModal').addClass('show');
}

function showAddContainerModal(vehicleId) {
    $('#container_vehicle_id').val(vehicleId);
    $('#addContainerModal').addClass('show');
}

function showAddCompartmentModal(containerId) {
    $('#compartment_container_id').val(containerId);
    $('#addCompartmentModal').addClass('show');
}

function showAddProductModal() {
    $('#addProductModal').addClass('show');
}

function showAddTargetProductModal(compartmentId, containerId, vehicleId) {
    $('#target_compartment_id').val(compartmentId);
    $('#target_container_id').val(containerId);
    $('#target_vehicle_id').val(vehicleId);
    $('#addTargetProductModal').addClass('show');
}

function closeModal() {
    $('.modal').removeClass('show');
    // Formulare zurücksetzen
    $('.modal form').each(function() {
        if (this.reset) this.reset();
    });
    // Hidden Fields leeren
    $('.modal input[type="hidden"]').val('');
}

function uploadContainerPhoto(containerId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', 'container');
            formData.append('id', containerId);
            
            if (typeof App !== 'undefined' && App.showLoading) {
                App.showLoading('Foto wird hochgeladen...');
            }
            
            $.ajax({
                url: 'api/upload-image.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (typeof App !== 'undefined' && App.showToast) {
                            App.showToast('Foto erfolgreich hochgeladen', 'success');
                        }
                        location.reload();
                    } else {
                        if (typeof App !== 'undefined' && App.showToast) {
                            App.showToast('Fehler: ' + response.message, 'error');
                        } else {
                            alert('Fehler: ' + response.message);
                        }
                    }
                },
                error: function() {
                    if (typeof App !== 'undefined' && App.showToast) {
                        App.showToast('Fehler beim Upload', 'error');
                    } else {
                        alert('Fehler beim Upload');
                    }
                },
                complete: function() {
                    if (typeof App !== 'undefined' && App.hideLoading) {
                        App.hideLoading();
                    }
                }
            });
        }
    };
    input.click();
}

function uploadCompartmentPhoto(compartmentId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', 'compartment');
            formData.append('id', compartmentId);
            
            if (typeof App !== 'undefined' && App.showLoading) {
                App.showLoading('Foto wird hochgeladen...');
            }
            
            $.ajax({
                url: 'api/upload-image.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (typeof App !== 'undefined' && App.showToast) {
                            App.showToast('Foto erfolgreich hochgeladen', 'success');
                        }
                        location.reload();
                    } else {
                        if (typeof App !== 'undefined' && App.showToast) {
                            App.showToast('Fehler: ' + response.message, 'error');
                        } else {
                            alert('Fehler: ' + response.message);
                        }
                    }
                },
                error: function() {
                    if (typeof App !== 'undefined' && App.showToast) {
                        App.showToast('Fehler beim Upload', 'error');
                    } else {
                        alert('Fehler beim Upload');
                    }
                },
                complete: function() {
                    if (typeof App !== 'undefined' && App.hideLoading) {
                        App.hideLoading();
                    }
                }
            });
        }
    };
    input.click();
}

function editProduct(productId) {
    // App.ajax verwenden wenn verfügbar, sonst jQuery direkt
    const ajaxCall = (typeof App !== 'undefined' && App.ajax) ? 
        App.ajax('api/get-product.php', { id: productId }, 'GET') :
        $.get('api/get-product.php', { id: productId });
    
    ajaxCall.done(function(response) {
        if (response.success) {
            $('#edit_product_id').val(productId);
            $('#edit_product_name').val(response.data.name);
            $('#edit_product_description').val(response.data.description);
            $('#edit_product_has_expiry').prop('checked', response.data.has_expiry == 1);
            $('#editProductModal').addClass('show');
        } else {
            if (typeof App !== 'undefined' && App.showToast) {
                App.showToast('Fehler beim Laden des Produkts: ' + response.message, 'error');
            } else {
                alert('Fehler beim Laden des Produkts: ' + response.message);
            }
        }
    }).fail(function() {
        if (typeof App !== 'undefined' && App.showToast) {
            App.showToast('Fehler beim Laden des Produkts', 'error');
        } else {
            alert('Fehler beim Laden des Produkts');
        }
    });
}

function deleteProduct() {
    const productId = $('#edit_product_id').val();
    const productName = $('#edit_product_name').val();
    
    $('#deleteConfirmMessage').text(`Möchtest du das Produkt "${productName}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`);
    
    // Altes Modal schließen, neues öffnen
    $('#editProductModal').removeClass('show');
    $('#deleteConfirmModal').addClass('show');
    
    // Delete-Button Event
    $('#confirmDeleteBtn').off('click').on('click', function() {
        const ajaxCall = (typeof App !== 'undefined' && App.ajax) ? 
            App.ajax('api/delete-product.php', { id: productId }, 'POST') :
            $.post('api/delete-product.php', { id: productId });
            
        ajaxCall.done(function(response) {
            if (response.success) {
                if (typeof App !== 'undefined' && App.showToast) {
                    App.showToast('Produkt erfolgreich gelöscht', 'success');
                }
                closeModal();
                location.reload(); // Seite neu laden
            } else {
                if (typeof App !== 'undefined' && App.showToast) {
                    App.showToast('Fehler beim Löschen: ' + response.message, 'error');
                } else {
                    alert('Fehler beim Löschen: ' + response.message);
                }
            }
        }).fail(function() {
            if (typeof App !== 'undefined' && App.showToast) {
                App.showToast('Fehler beim Löschen', 'error');
            } else {
                alert('Fehler beim Löschen');
            }
        });
    });
}

function deleteContainer(containerId, vehicleId, containerName) {
    if (confirm(`Möchtest du den Behälter "${containerName}" wirklich löschen?\n\nAlle enthaltenen Fächer werden ebenfalls gelöscht!\n\nDiese Aktion kann nicht rückgängig gemacht werden.`)) {
        // Hidden Form erstellen und absenden
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_container';
        
        const containerIdInput = document.createElement('input');
        containerIdInput.type = 'hidden';
        containerIdInput.name = 'container_id';
        containerIdInput.value = containerId;
        
        const vehicleIdInput = document.createElement('input');
        vehicleIdInput.type = 'hidden';
        vehicleIdInput.name = 'vehicle_id';
        vehicleIdInput.value = vehicleId;
        
        form.appendChild(actionInput);
        form.appendChild(containerIdInput);
        form.appendChild(vehicleIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteCompartment(compartmentId, containerId, vehicleId, compartmentName) {
    if (confirm(`Möchtest du das Fach "${compartmentName}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`)) {
        // Hidden Form erstellen und absenden
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_compartment';
        
        const compartmentIdInput = document.createElement('input');
        compartmentIdInput.type = 'hidden';
        compartmentIdInput.name = 'compartment_id';
        compartmentIdInput.value = compartmentId;
        
        const containerIdInput = document.createElement('input');
        containerIdInput.type = 'hidden';
        containerIdInput.name = 'container_id';
        containerIdInput.value = containerId;
        
        const vehicleIdInput = document.createElement('input');
        vehicleIdInput.type = 'hidden';
        vehicleIdInput.name = 'vehicle_id';
        vehicleIdInput.value = vehicleId;
        
        form.appendChild(actionInput);
        form.appendChild(compartmentIdInput);
        form.appendChild(containerIdInput);
        form.appendChild(vehicleIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// ESC-Key für Modal schließen
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Click außerhalb Modal schließen
$(document).on('click', '.modal', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Rename functions
function renameContainer(containerId, currentName) {
    const newName = prompt('Neuer Name für Container:', currentName);
    if (newName && newName !== currentName) {
        $.ajax({
            url: 'api/rename-container.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                id: containerId,
                name: newName
            }),
            success: function(response) {
                if (response.success) {
                    if (typeof App !== 'undefined' && App.showToast) {
                        App.showToast('Container umbenannt', 'success');
                    }
                    location.reload();
                } else {
                    alert('Fehler: ' + response.error);
                }
            },
            error: function() {
                alert('Fehler beim Umbenennen');
            }
        });
    }
}

function renameCompartment(compartmentId, currentName) {
    const newName = prompt('Neuer Name für Fach:', currentName);
    if (newName && newName !== currentName) {
        $.ajax({
            url: 'api/rename-compartment.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                id: compartmentId,
                name: newName
            }),
            success: function(response) {
                if (response.success) {
                    if (typeof App !== 'undefined' && App.showToast) {
                        App.showToast('Fach umbenannt', 'success');
                    }
                    location.reload();
                } else {
                    alert('Fehler: ' + response.error);
                }
            },
            error: function() {
                alert('Fehler beim Umbenennen');
            }
        });
    }
}
</script>
    