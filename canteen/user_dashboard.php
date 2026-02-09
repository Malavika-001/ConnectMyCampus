<?php
$pageTitle = 'Canteen User Dashboard';
$current_module = 'canteen';

include_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/authentication.php';
include_once __DIR__ . '/db.php';
include_once __DIR__ . '/header.php';
requireUser();
?>

<main class="container text-center">
    <h2 class="mt-4 mb-3 text-dark">Canteen Dashboard 🍽️ (User)</h2>

    <div class="row justify-content-center g-4 mt-4">
        <div class="col-md-3">
            <div class="card service-card" onclick="location.href='general_menu.php'">
                <i class="fas fa-book icon-lg text-primary"></i>
                <h5>General Menu</h5>
                <p class="text-muted small">Explore weekly menu and prices.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card service-card" onclick="location.href='todays_menu.php'">
                <i class="fas fa-calendar-day icon-lg text-success"></i>
                <h5>Today's Menu</h5>
                <p class="text-muted small">See what's fresh today.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card service-card" onclick="location.href='prebooking.php'">
                <i class="fas fa-clipboard-list icon-lg text-warning"></i>
                <h5>Prebooking</h5>
                <p class="text-muted small">Reserve meals and skip the line.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card service-card" onclick="location.href='reports.php'">
                <i class="fas fa-comment-dots icon-lg text-danger"></i>
                <h5>Reports & Suggestions</h5>
                <p class="text-muted small">Send feedback or report issues.</p>
            </div>
        </div>
    </div>

    <section class="mt-5 mx-auto" style="max-width: 900px;">
        <div class="about-box text-start">
            <h4 class="section-title">About This Platform</h4>
            <p>The <strong>College Canteen Service</strong> helps you view menus, prebook meals, and send feedback easily.</p>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>

