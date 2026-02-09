<?php
session_start();
include_once 'db.php';
include_once 'authentication.php';

requireAuth(); // Must be logged in to prebook

$user_id = $_SESSION['user_id'] ?? null;
$user_details = fetchUserDetails($user_id); // Fetch details from users table
$pageTitle = 'Prebooking';
include_once 'header.php';

$message = '';

// --- Handle Prebooking Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_order') {
    $item_name = trim($_POST['item_name'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);

    if (empty($item_name) || $quantity <= 0) {
        $message = "Error: Please select a valid item and quantity.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO prebooking (user_id, item_name, quantity, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $item_name, $quantity]);
            $message = "Your order for {$quantity} × {$item_name} has been submitted successfully!<br>Status: <strong>PENDING</strong>.";
        } catch (PDOException $e) {
            $message = "Database Error: Could not submit order. " . htmlspecialchars($e->getMessage());
        }
    }
}

// --- Fetch available food items ---
try {
    $stmt = $pdo->prepare("SELECT item_name FROM menu WHERE special_item = FALSE OR is_available_today = TRUE ORDER BY item_name");
    $stmt->execute();
    $available_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $available_items = [];
}

// --- Fetch user's existing prebookings ---
try {
    $stmt = $pdo->prepare("SELECT * FROM prebooking WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}
?>

<main class="container my-4">
    <h2 class="mb-4">
        Prebooking 
        <i class="fas fa-concierge-bell text-warning"></i>
    </h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo str_contains($message, 'Error') ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!$user_details): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> User ID <strong><?php echo htmlspecialchars($user_id); ?></strong> not found in the centralized registration system. Cannot prebook.
        </div>
    <?php else: ?>
        <!-- ✅ User Details Card -->
        <div class="card p-3 mb-4 bg-light shadow-sm">
            <h4 class="card-title text-primary mb-3">Your Details</h4>
            <div class="row">
                <div class="col-md-6"><strong>Name:</strong> <?php echo htmlspecialchars($user_details['name']); ?></div>
                <div class="col-md-6"><strong>Department / Year:</strong> <?php echo htmlspecialchars(($user_details['department'] ?? '-') . ' / ' . ($user_details['year'] ?? '-')); ?></div>
                <div class="col-md-6"><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></div>
                <div class="col-md-6"><strong>Phone:</strong> <?php echo htmlspecialchars($user_details['phone'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <!-- ✅ New Prebooking Form -->
        <h4 class="mb-3">New Prebooking Order</h4>
        <form method="POST" action="prebooking.php" class="card p-4 border-warning shadow-sm">
            <input type="hidden" name="action" value="submit_order">

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="item_name" class="form-label fw-semibold">Select Food Item</label>
                    <select class="form-select" id="item_name" name="item_name" required>
                        <option value="">-- Select an item --</option>
                        <?php if (!empty($available_items)): ?>
                            <optgroup label="Available Items">
                                <?php foreach ($available_items as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php else: ?>
                            <option disabled>No items available</option>
                        <?php endif; ?>
                    </select>
                    <small class="form-text text-muted">Includes all general menu items and today's specials.</small>
                </div>

                <div class="col-md-2">
                    <label for="quantity" class="form-label fw-semibold">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Book Now</button>
                </div>
            </div>
        </form>

        <hr class="my-5">

        <!-- ✅ User Orders Table -->
        <h3 class="mb-4">Your Recent Prebookings</h3>
        <?php if (empty($orders)): ?>
            <div class="alert alert-info text-center">You have no recent prebookings.</div>
        <?php else: ?>
            <div class="table-responsive shadow-sm">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Order ID</th>
                            <th>Item & Quantity</th>
                            <th>Status</th>
                            <th>Booked At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                                $status_class = match ($order['status']) {
                                    'pending' => 'bg-warning text-dark',
                                    'confirmed' => 'bg-success text-white',
                                    'rejected' => 'bg-danger text-white',
                                    default => 'bg-secondary text-white'
                                };
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($order['item_name']); ?></strong> × <?php echo htmlspecialchars($order['quantity']); ?></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include_once 'footer.php'; ?>
