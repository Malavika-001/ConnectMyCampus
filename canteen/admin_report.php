<?php
$pageTitle = 'Admin Reports (Canteen)';
include __DIR__ . '/db.php';
include __DIR__ . '/header.php';
requireAuth(true); // Only Admin access allowed

$message = '';

// --- Handle Report Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_report_status') {
    $report_id = intval($_POST['report_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($report_id > 0 && in_array($status, ['pending', 'viewed', 'resolved'])) {
        try {
            // ✅ FIXED: Table name is `report` (canteen reports)
            $stmt = $pdo->prepare("UPDATE `report` SET `status` = ? WHERE `report_id` = ?");
            $stmt->execute([$status, $report_id]);
            $message = "✅ Report ID {$report_id} updated to: " . strtoupper($status);
        } catch (PDOException $e) {
            $message = "Error updating status: " . $e->getMessage();
        }
    } else {
        $message = "Error: Invalid report ID or status.";
    }
}

// --- Fetch All Reports ---
try {
    // ✅ FIXED: Use correct table `report` (canteen reports)
    $stmt = $pdo->prepare("SELECT `report_id`, `user_id`, `message`, `status`, `created_at` FROM `report` ORDER BY `created_at` DESC");
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reports = [];
    $message = ($message ? $message . ' | ' : '') . "Could not load reports: " . $e->getMessage();
}
?>

<main class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="text-danger">Canteen Reports & Suggestions <i class="fas fa-inbox"></i></h2>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo str_contains($message, 'Error') ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($reports)): ?>
        <div class="alert alert-info text-center">No reports or suggestions have been submitted yet.</div>
    <?php else: ?>
        <div class="accordion" id="reportsAccordion">
            <?php foreach ($reports as $report): ?>
                <?php
                    $report_id = $report['report_id'] ?? 0;
                    $user_id = htmlspecialchars($report['user_id'] ?? 'Unknown');
                    $message_text = htmlspecialchars($report['message'] ?? '(No message)');
                    $status = $report['status'] ?? 'pending';
                    $created = isset($report['created_at']) ? date('M d, H:i', strtotime($report['created_at'])) : 'Unknown Time';

                    $status_class = match ($status) {
                        'pending' => 'bg-warning text-dark',
                        'viewed' => 'bg-info text-dark',
                        'resolved' => 'bg-success text-white',
                        default => 'bg-secondary text-white'
                    };
                ?>
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header" id="heading<?php echo $report_id; ?>">
                        <button class="accordion-button collapsed <?php echo $status === 'pending' ? 'fw-bold text-danger' : ''; ?>" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?php echo $report_id; ?>" 
                                aria-expanded="false" 
                                aria-controls="collapse<?php echo $report_id; ?>">
                            <span class="me-3 badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                            Report ID: <strong>#<?php echo $report_id; ?></strong> |
                            User: <strong><?php echo $user_id; ?></strong> |
                            Time: <?php echo $created; ?>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $report_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $report_id; ?>" data-bs-parent="#reportsAccordion">
                        <div class="accordion-body">
                            <p><strong>Message:</strong></p>
                            <div class="p-3 mb-3 border rounded bg-light"><?php echo nl2br($message_text); ?></div>

                            <form method="POST" action="admin_report.php" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="action" value="update_report_status">
                                <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
                                <select name="status" class="form-select w-auto" required>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="viewed" <?php echo $status === 'viewed' ? 'selected' : ''; ?>>Viewed</option>
                                    <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
