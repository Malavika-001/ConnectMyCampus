<?php
$pageTitle = 'Canteen Admin Dashboard';
$current_module = 'canteen';

include_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/authentication.php';
include_once __DIR__ . '/db.php';
include_once __DIR__ . '/header.php';
requireAdmin();
?>

<main class="container text-center">
    <h2 class="mt-4 mb-3 text-dark">Canteen Dashboard 🍽️ (Admin)</h2>

    <div class="row justify-content-center g-4 mt-4">
        <div class="col-md-3">
            <div class="card service-card" onclick="location.href='general_menu.php?action=admin'">
                <i class="fas fa-cogs icon-lg text-primary"></i>
                <h5>Manage Menu</h5>
                <p class="text-muted small">Add or update menu items.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card service-card" onclick="location.href='todays_menu.php?action=admin'">
                <i class="fas fa-calendar-check icon-lg text-success"></i>
                <h5>Set Today’s Menu</h5>
                <p class="text-muted small">Update today’s specials.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card service-card" onclick="location.href='admin_orders.php'">
                <i class="fas fa-receipt icon-lg text-warning"></i>
                <h5>View Orders</h5>
                <p class="text-muted small">Check prebooked & ongoing orders.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card service-card" onclick="location.href='admin_report.php'">
                <i class="fas fa-envelope-open-text icon-lg text-danger"></i>
                <h5>Handle Reports</h5>
                <p class="text-muted small">Review feedback and issues.</p>
            </div>
        </div>
    </div>

    <section class="mt-5 mx-auto" style="max-width: 900px;">
        <div class="about-box text-start">
            <h4 class="section-title">Admin Tools</h4>
            <p>Manage menu items, orders, and user feedback easily.</p>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
