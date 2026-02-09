<?php
$pageTitle = 'Today\'s Menu';
require_once __DIR__ . '/../db.php';

include 'header.php';

// Check if admin is managing the menu
$isAdmin = isAdmin();

// --- ADMIN LOGIC: Update Today's Menu and Add Special Item ---
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAuth(true); // Double-check authentication for POST

    if (isset($_POST['action']) && $_POST['action'] === 'set_today') {
        $today_items = $_POST['today_items'] ?? []; // Array of item_id's checked
        $special_item_name = trim($_POST['special_item_name'] ?? '');
        $special_item_price = floatval($_POST['special_item_price'] ?? 0);

        try {
            $pdo->beginTransaction();

            // 1. Reset all 'is_available_today' and clear all 'special_item' flags
            $pdo->exec("UPDATE menu SET is_available_today = FALSE, special_item = FALSE");

            // 2. Set 'is_available_today' for selected general items
            if (!empty($today_items)) {
                $placeholders = implode(',', array_fill(0, count($today_items), '?'));
                $stmt = $pdo->prepare("UPDATE menu SET is_available_today = TRUE WHERE item_id IN ($placeholders)");
                $stmt->execute($today_items);
            }

            // 3. Add Special Item (if provided)
            if (!empty($special_item_name) && $special_item_price > 0) {
                // Insert as a new item, marked as both available today AND special item
                $stmt = $pdo->prepare("INSERT INTO menu (item_name, price, is_available_today, special_item) VALUES (?, ?, TRUE, TRUE)");
                $stmt->execute([$special_item_name, $special_item_price]);
            }

            $pdo->commit();
            $message = "Today's menu updated successfully! Resetting items not selected.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Error updating menu: " . $e->getMessage();
        }
    }
}
// --- END ADMIN LOGIC ---

// --- USER/VIEW LOGIC: Fetch Today's Menu ---
try {
    // Select only items flagged as available today
    $stmt = $pdo->prepare("SELECT item_id, item_name, price, special_item FROM menu WHERE is_available_today = TRUE ORDER BY special_item DESC, item_name");
    $stmt->execute();
    $todays_menu = $stmt->fetchAll();
} catch (PDOException $e) {
    $todays_menu = [];
    $message = "Could not load today's menu: " . $e->getMessage();
}

// Fetch all general items for admin selection (excluding existing special items)
$general_menu = [];
if ($isAdmin) {
    try {
        $stmt = $pdo->prepare("SELECT item_id, item_name, is_available_today FROM menu WHERE special_item = FALSE ORDER BY item_name");
        $stmt->execute();
        $general_menu = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Silently fail if fetching general menu items for admin form
    }
}
?>

<main>
    <h2 class="mb-4">Today's Canteen Menu <i class="fas fa-utensils text-success"></i></h2>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <div class="card p-4 mb-4 border-primary">
        <h4 class="card-title text-primary">Admin: Set Today's Menu (Resets all non-selected items)</h4>
        <form method="POST" action="todays_menu.php">
            <input type="hidden" name="action" value="set_today">
            
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label"><strong>1. Select Regular Items Available Today:</strong></label>
                    <div class="row row-cols-md-3">
                        <?php foreach ($general_menu as $item): ?>
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="today_items[]" value="<?php echo $item['item_id']; ?>" id="item_<?php echo $item['item_id']; ?>" <?php echo $item['is_available_today'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="item_<?php echo $item['item_id']; ?>">
                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row g-3 mb-4">
                <label class="form-label"><strong>2. Add Special Item (Today Only):</strong></label>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="special_item_name" placeholder="Special Item Name (e.g., Kadhai Paneer)" value="">
                </div>
                <div class="col-md-4">
                    <input type="number" step="0.01" class="form-control" name="special_item_price" placeholder="Price (Rs.)" min="1" value="">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-calendar-alt me-2"></i> Update Today's Menu</button>
        </form>
    </div>
    <hr>
    <?php endif; ?>

    <?php if (empty($todays_menu)): ?>
        <div class="alert alert-warning text-center">
            The Canteen Menu for today has not been set yet. Please check back later!
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-success table-hover">
                <thead class="table-success">
                    <tr>
                        <th>Item Name</th>
                        <th>Price (Rs.)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todays_menu as $item): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($item['item_name']); ?>
                                <?php if ($item['special_item']): ?>
                                    <span class="badge bg-danger ms-2">Today's Special!</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo number_format($item['price'], 2); ?></strong></td>
                            <td>Available</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p class="mt-4 text-muted"><a href="general_menu.php">Go to General Menu</a></p>
</main>
<?php include 'footer.php'; ?>