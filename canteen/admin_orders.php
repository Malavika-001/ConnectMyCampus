<?php
$pageTitle = 'Admin Orders';

// ✅ Use the correct DB connection
include_once 'db.php';
include_once 'authentication.php';
include_once 'header.php';

requireAuth(true); // Only Admin access allowed

$message = '';

// --- Handle Order Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($order_id > 0 && in_array($status, ['pending', 'confirmed', 'rejected'])) {
        try {
            $stmt = $pdo->prepare("UPDATE prebooking SET status = ? WHERE order_id = ?");
            $stmt->execute([$status, $order_id]);
            $message = "✅ Order ID {$order_id} status updated to: " . strtoupper($status);
        } catch (PDOException $e) {
            $message = "Error updating status: " . $e->getMessage();
        }
    } else {
        $message = "Error: Invalid order ID or status.";
    }
}

// --- Fetch All Prebookings ---
try {
    $stmt = $pdo->prepare("SELECT * FROM prebooking ORDER BY created_at DESC");
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $message = ($message ? $message . ' | ' : '') . "Could not load orders: " . $e->getMessage();
}
?>

<main class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-warning">Admin: Manage Prebookings <i class="fas fa-clipboard-check"></i></h2>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo str_contains($message, 'Error') ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center">No prebookings have been submitted yet.</div>
    <?php else: ?>
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-warning text-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>User ID</th>
                        <th>Item & Quantity</th>
                        <th>User Contact</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php 
                            // Fetch real user info from users table
                            $user_details = fetchUserDetails($order['user_id']);
                            $contact_info = $user_details 
                                ? htmlspecialchars("{$user_details['name']} ({$user_details['phone']})") 
                                : 'Details unavailable';

                            $status_class = match ($order['status']) {
                                'pending'   => 'badge bg-warning text-dark',
                                'confirmed' => 'badge bg-success text-white',
                                'rejected'  => 'badge bg-danger text-white',
                                default     => 'badge bg-secondary text-white'
                            };
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['user_id']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($order['item_name']); ?></strong>
                                × <?php echo htmlspecialchars($order['quantity']); ?>
                            </td>
                            <td><?php echo $contact_info; ?></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            <td>
                                <form method="POST" action="admin_orders.php" class="d-flex gap-1">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="status" class="form-select form-select-sm me-1 w-auto" required>
                                        <option value="pending"   <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="rejected"  <?php echo $order['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php include_once 'footer.php'; ?>
