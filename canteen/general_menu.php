<?php
$pageTitle = 'General Menu';
require_once __DIR__ . '/../db.php';

include 'header.php';

// Check if admin is managing the menu
$isAdminView = isAdmin() && isset($_GET['action']) && $_GET['action'] === 'admin';

// --- ADMIN CRUD LOGIC ---
if ($isAdminView) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $item_name = trim($_POST['item_name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $item_id = intval($_POST['item_id'] ?? 0);

        try {
            if ($action === 'add') {
                if (!empty($item_name) && $price > 0) {
                    $stmt = $pdo->prepare("INSERT INTO menu (item_name, price) VALUES (?, ?)");
                    $stmt->execute([$item_name, $price]);
                    $message = "Item '{$item_name}' added successfully.";
                } else {
                    $message = "Error: Invalid input for adding item.";
                }
            } elseif ($action === 'edit' && $item_id > 0) {
                if (!empty($item_name) && $price > 0) {
                    $stmt = $pdo->prepare("UPDATE menu SET item_name = ?, price = ? WHERE item_id = ?");
                    $stmt->execute([$item_name, $price, $item_id]);
                    $message = "Item ID {$item_id} updated successfully.";
                } else {
                    $message = "Error: Invalid input for editing item.";
                }
            } elseif ($action === 'delete' && $item_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM menu WHERE item_id = ?");
                $stmt->execute([$item_id]);
                $message = "Item ID {$item_id} deleted successfully.";
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
        }
    }
}
// --- END ADMIN CRUD LOGIC ---

// Fetch all general menu items (special items are excluded from general view)
try {
    $stmt = $pdo->prepare("SELECT item_id, item_name, price FROM menu WHERE special_item = FALSE ORDER BY item_name");
    $stmt->execute();
    $menu_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $menu_items = [];
    $message = "Could not load menu: " . $e->getMessage();
}
?>

<main>
    <h2 class="mb-4"><?php echo $isAdminView ? 'Admin: Manage General Menu' : 'General Canteen Menu'; ?></h2>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($isAdminView): ?>
    <div class="card p-3 mb-4 border-success">
        <h4 class="card-title text-success">Add New Item</h4>
        <form method="POST" action="general_menu.php?action=admin">
            <input type="hidden" name="action" value="add">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="new_item_name" class="form-label">Item Name</label>
                    <input type="text" class="form-control" id="new_item_name" name="item_name" required>
                </div>
                <div class="col-md-3">
                    <label for="new_item_price" class="form-label">Price (Rs.)</label>
                    <input type="number" step="0.01" class="form-control" id="new_item_price" name="price" required min="1">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100">Add Item</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <hr>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <?php if ($isAdminView): ?><th>ID</th><?php endif; ?>
                    <th>Item Name</th>
                    <th>Price (Rs.)</th>
                    <?php if ($isAdminView): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menu_items)): ?>
                    <tr>
                        <td colspan="<?php echo $isAdminView ? '4' : '2'; ?>" class="text-center">No general menu items found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($menu_items as $item): ?>
                        <tr>
                            <?php if ($isAdminView): ?><td><?php echo htmlspecialchars($item['item_id']); ?></td><?php endif; ?>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo number_format($item['price'], 2); ?></td>
                            <?php if ($isAdminView): ?>
                            <td>
                                <button type="button" class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $item['item_id']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" action="general_menu.php?action=admin" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?php echo addslashes($item['item_name']); ?>?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                                
                                <div class="modal fade" id="editModal<?php echo $item['item_id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $item['item_id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel<?php echo $item['item_id']; ?>">Edit Item: <?php echo htmlspecialchars($item['item_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="general_menu.php?action=admin">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="item_name_<?php echo $item['item_id']; ?>" class="form-label">Item Name</label>
                                                        <input type="text" class="form-control" id="item_name_<?php echo $item['item_id']; ?>" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="price_<?php echo $item['item_id']; ?>" class="form-label">Price (Rs.)</label>
                                                        <input type="number" step="0.01" class="form-control" id="price_<?php echo $item['item_id']; ?>" name="price" value="<?php echo htmlspecialchars($item['price']); ?>" required min="1">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($isAdminView): ?>
        <p class="mt-4"><a href="general_menu.php" class="btn btn-outline-secondary"><i class="fas fa-eye me-2"></i> View User Menu</a></p>
    <?php endif; ?>
</main>
<?php include 'footer.php'; ?>