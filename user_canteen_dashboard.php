
<?php
$pageTitle = 'Home';
include 'header.php';
?>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
    body {
        background-color: #f7f9fc;
        color: #222;
        font-family: 'Poppins', sans-serif;
    }

    h1, h2, h3, h4, h5 {
        font-weight: 600;
    }

    a {
        color: inherit;
        text-decoration: none;
    }

    .card {
        background: #ffffff;
        border: 1px solid #e4e9f0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 24px rgba(0, 170, 255, 0.15);
    }

    .icon-lg {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .section-title {
        font-size: 1.5rem;
        color: #007bff;
        border-bottom: 2px solid #007bff;
        padding-bottom: 0.4rem;
        margin-bottom: 2rem;
        display: inline-block;
    }

    .about-box {
        background-color: #ffffff;
        border-left: 4px solid #007bff;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .btn-modern {
        background-color: #007bff;
        border: none;
        color: #fff;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
    }

    .btn-modern:hover {
        background-color: #0056b3;
    }

    .module-section {
        margin-top: 3rem;
        margin-bottom: 3rem;
    }

    .card-title {
        color: #111;
    }

    .card-text {
        color: #555;
    }

    ul li::marker {
        color: #007bff;
    }

    .divider {
        border-top: 1px solid #ddd;
        margin: 3rem 0;
    }

    .bg-light-box {
        background-color: #ffffff;
        border: 1px solid #e2e6ea;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    /* Reduced width and styling for Operating Hours box */
    .hours-box {
        max-width: 360px;
        background-color: #ffffff;
        border: 1px solid #e4e9f0;
        border-left: 4px solid #007bff;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    }
</style>

<main class="container text-center">

    <h2 class="mt-4 mb-3 text-dark">College Canteen Service 🍽️</h2>

    <!-- Operating Hours -->
    <div class="mb-5 p-4 text-start mx-auto rounded hours-box">
        <h5 class="text-primary mb-2">⏰ Operating Hours</h5>
        <p class="mb-1">Monday - Friday: <strong>8:00 AM - 5:00 PM</strong></p>
        <p class="text-muted small mb-0">Closed on weekends and public holidays.</p>
    </div>

    <!-- Modules -->
    <section class="module-section">
        <div class="row justify-content-center g-4 mb-4">
            <div class="col-md-5">
                <a href="general_menu.php">
                    <div class="card p-4 h-100">
                        <i class="fas fa-book icon-lg text-primary"></i>
                        <h5 class="card-title">General Menu</h5>
                        <p class="card-text">Explore the regular weekly menu and pricing.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-5">
                <a href="todays_menu.php">
                    <div class="card p-4 h-100">
                        <i class="fas fa-calendar-day icon-lg text-success"></i>
                        <h5 class="card-title">Today's Menu</h5>
                        <p class="card-text">Check out what’s fresh and special today!</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="row justify-content-center g-4">
            <div class="col-md-5">
                <a href="prebooking.php">
                    <div class="card p-4 h-100">
                        <i class="fas fa-clipboard-list icon-lg text-warning"></i>
                        <h5 class="card-title">Prebooking</h5>
                        <p class="card-text">Reserve and pay in advance to skip the queue.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-5">
                <a href="reports.php">
                    <div class="card p-4 h-100">
                        <i class="fas fa-lightbulb icon-lg text-danger"></i>
                        <h5 class="card-title">Reports & Suggestions</h5>
                        <p class="card-text">Send us your feedback or report any issue.</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Admin Dashboard (if admin) -->
    <?php if (isAdmin()): ?>
        <div class="divider"></div>
        <h3 class="text-warning mb-4">Admin Dashboard</h3>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="general_menu.php?action=admin" class="btn-modern">
                <i class="fas fa-cogs me-2"></i> Manage Menu
            </a>
            <a href="todays_menu.php" class="btn-modern">
                <i class="fas fa-calendar-check me-2"></i> Set Today’s Menu
            </a>
            <a href="admin_orders.php" class="btn-modern">
                <i class="fas fa-receipt me-2"></i> View Orders
            </a>
            <a href="admin_report.php" class="btn-modern">
                <i class="fas fa-envelope-open-text me-2"></i> Handle Reports
            </a>
        </div>
    <?php endif; ?>

    <!-- About Section -->
    <section class="mt-5 text-start mx-auto" style="max-width: 900px;">
        <div class="about-box">
            <h4 class="section-title">About This Platform</h4>
            <p>The <strong>College Canteen Service</strong> aims to simplify your daily food experience. You can check menus, prebook meals, and send us feedback — all in one place.</p>
            <ul>
                <li><strong>Live Menus</strong>: View daily and weekly food items with ease.</li>
                <li><strong>Prebooking</strong>: Plan ahead, save time, and skip the line.</li>
                <li><strong>Feedback</strong>: Report issues or suggest ideas to improve the service.</li>
            </ul>
        </div>
    </section>

</main>

<?php include 'footer.php'; ?>

