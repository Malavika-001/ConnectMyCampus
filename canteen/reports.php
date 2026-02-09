<?php
session_start();
$pageTitle = 'User Reports & Feedback';

include 'db.php';
include 'header.php';
requireAuth(); // Must be logged in

$user_id = $_SESSION['user_id'] ?? null;
$message = '';

/* ─────────────────────────────────────────────
   🧾 Handle Report Submission
───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_report') {
    $report_text = trim($_POST['message'] ?? '');

    if (empty($report_text)) {
        $message = "Error: Please enter a report or feedback message.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO report (user_id, message, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$user_id, $report_text]);
            $message = "✅ Report submitted successfully! Status: PENDING.";
        } catch (PDOException $e) {
            $message = "Database Error: Could not submit report. " . $e->getMessage();
        }
    }
}

/* ─────────────────────────────────────────────
   📋 Fetch User’s Reports
───────────────────────────────────────────── */
$reports = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM report WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching reports: " . $e->getMessage();
}
?>

<main>
    <h2 class="mb-4 text-danger"> Reports & Feedback <i class="fas fa-comments"></i></h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- 📝 Submit New Report -->
    <form method="POST" action="reports.php" class="card p-4 mb-4 border-danger">
        <input type="hidden" name="action" value="submit_report">
        <h5 class="mb-3">Submit a New Report or Suggestion</h5>
        <textarea name="message" class="form-control mb-3" rows="4" placeholder="Describe the issue or suggestion..." required></textarea>
        <button type="submit" class="btn btn-danger w-100">Submit Report</button>
    </form>

    <!-- 📄 User Reports Table -->
    <h4 class="mt-5 mb-3">Your Submitted Reports</h4>

    <?php if (empty($reports)): ?>
        <div class="alert alert-info text-center">You have not submitted any reports yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-danger">
                    <tr>
                        <th>Report ID</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                        <?php
                            $status_class = [
                                'pending' => 'bg-warning text-dark',
                                'viewed' => 'bg-info text-dark',
                                'resolved' => 'bg-success text-white'
                            ][$r['status']] ?? 'bg-secondary text-white';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($r['report_id']) ?></td>
                            <td><?= nl2br(htmlspecialchars($r['message'])) ?></td>
                            <td><span class="badge <?= $status_class ?>"><?= ucfirst($r['status']) ?></span></td>
                            <td><?= date('M d, Y H:i', strtotime($r['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
