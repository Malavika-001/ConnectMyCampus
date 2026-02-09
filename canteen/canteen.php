php
session_start();
<?
// Database Connection Details
$servername = "localhost";
$username = "root";
$password = "Root1234";
$dbname = "your_college_database"; // Change to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for login status and admin privilege
// For this single-file demo, we'll simulate a logged-in user and an admin.
// In a real project, this would be handled by your main login system.
// For now, you can uncomment one of the following lines to test:
$_SESSION['user_id'] = 1; // Simulate a student user
// $_SESSION['user_id'] = 2; // Simulate an admin user
// You'll need to manually add these users to your 'registration' table.

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = $is_logged_in && ($conn->query("SELECT is_admin FROM registration WHERE user_id = " . $_SESSION['user_id'])->fetch_assoc()['is_admin'] ?? false);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

$status_message = "";

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Admin: Update Today's Menu
    if (isset($_POST['update_menu']) && $is_admin) {
        $conn->query("UPDATE menu SET is_available_today = 0");
        if (isset($_POST['today_items'])) {
            $selected_items = $_POST['today_items'];
            $ids = implode(',', array_map('intval', $selected_items));
            $conn->query("UPDATE menu SET is_available_today = 1 WHERE item_id IN ($ids)");
        }
        $status_message = "Today's menu updated successfully!";
    }
    // User: Submit Pre-booking Order
    elseif (isset($_POST['submit_order']) && $is_logged_in) {
        $item_name = $_POST['item_name'];
        $quantity = $_POST['quantity'];
        $stmt = $conn->prepare("INSERT INTO orders (user_id, item_name, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $user_id, $item_name, $quantity);
        if ($stmt->execute()) {
            $status_message = "Order submitted successfully!";
        } else {
            $status_message = "Error submitting order: " . $conn->error;
        }
        $stmt->close();
    }
    // Admin: Update Order Status
    elseif (isset($_POST['update_order_status']) && $is_admin) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            $status_message = "Order status updated successfully!";
        } else {
            $status_message = "Error updating order status: " . $conn->error;
        }
        $stmt->close();
    }
    // User: Submit Report/Suggestion
    elseif (isset($_POST['submit_report']) && $is_logged_in) {
        $report_message = $_POST['report_message'];
        $stmt = $conn->prepare("INSERT INTO suggestions (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $report_message);
        if ($stmt->execute()) {
            $status_message = "Report submitted successfully!";
        } else {
            $status_message = "Error submitting report: " . $conn->error;
        }
        $stmt->close();
    }
    // Admin: Update Report Status
    elseif (isset($_POST['update_report_status']) && $is_admin) {
        $suggestion_id = $_POST['suggestion_id'];
        $new_status = $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE suggestions SET status = ? WHERE suggestion_id = ?");
        $stmt->bind_param("si", $new_status, $suggestion_id);
        if ($stmt->execute()) {
            $status_message = "Report status updated successfully!";
        } else {
            $status_message = "Error updating report status: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch user details if logged in
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT name, department, year, phone_number FROM registration WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Canteen Service</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: auto; }
        .header { text-align: center; }
        .header img { max-width: 200px; }
        .modules { display: flex; justify-content: space-around; margin-top: 30px; }
        .module { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; width: 23%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .module a { text-decoration: none; color: #333; font-weight: bold; }
        .module img { max-width: 60px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        form { margin-top: 20px; }
        .status-message { color: green; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Canteen Service</h1>
            <img src="https://via.placeholder.com/200x150.png?text=Canteen+Image" alt="Cartoon Canteen Image">
        </div>

        <?php if ($status_message): ?>
            <p class="status-message"><?= htmlspecialchars($status_message) ?></p>
        <?php endif; ?>

        <div class="modules">
            <div class="module"><a href="#general-menu"><img src="https://via.placeholder.com/60x60.png?text=Menu" alt="Menu Icon">General Menu</a></div>
            <div class="module"><a href="#todays-menu"><img src="https://via.placeholder.com/60x60.png?text=Today" alt="Today's Menu Icon">Today's Menu</a></div>
            <div class="module"><a href="#prebooking"><img src="https://via.placeholder.com/60x60.png?text=Book" alt="Pre-booking Icon">Pre-booking</a></div>
            <div class="module"><a href="#reports"><img src="https://via.placeholder.com/60x60.png?text=Report" alt="Reports Icon">Reports & Suggestions</a></div>
        </div>

        <hr>

        <h2 id="general-menu">General Menu</h2>
        <?php
        $sql = "SELECT item_name, price FROM menu";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo "<table><tr><th>Item Name</th><th>Price</th></tr>";
            while($row = $result->fetch_assoc()) {
                echo "<tr><td>" . htmlspecialchars($row['item_name']) . "</td><td>$" . htmlspecialchars($row['price']) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No general menu items available.</p>";
        }
        ?>

        <hr>

        <h2 id="todays-menu">Today's Menu</h2>
        <?php if ($is_admin): ?>
            <h3>Admin: Update Today's Menu</h3>
            <form method="post">
                <table>
                    <tr><th>Select</th><th>Item Name</th><th>Price</th></tr>
                    <?php
                    $result = $conn->query("SELECT item_id, item_name, price, is_available_today FROM menu");
                    while($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><input type="checkbox" name="today_items[]" value="<?= $row['item_id'] ?>" <?= $row['is_available_today'] ? 'checked' : '' ?>></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td>$<?= htmlspecialchars($row['price']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                <button type="submit" name="update_menu">Update Menu</button>
            </form>
        <?php else: ?>
            <h3>Available Today</h3>
            <?php
            $sql = "SELECT item_name, price FROM menu WHERE is_available_today = 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                echo "<table><tr><th>Item Name</th><th>Price</th></tr>";
                while($row = $result->fetch_assoc()) {
                    echo "<tr><td>" . htmlspecialchars($row['item_name']) . "</td><td>$" . htmlspecialchars($row['price']) . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No items available on today's menu.</p>";
            }
            ?>
        <?php endif; ?>

        <hr>

        <h2 id="prebooking">Pre-booking</h2>
        <?php if ($is_logged_in): ?>
            <div class="user-details">
                <p><strong>Name:</strong> <?= htmlspecialchars($user_details['name']) ?></p>
                <p><strong>Department:</strong> <?= htmlspecialchars($user_details['department']) ?></p>
                <p><strong>Year:</strong> <?= htmlspecialchars($user_details['year']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($user_details['phone_number']) ?></p>
            </div>
            <form method="post">
                <label for="item_name">Food Item:</label>
                <input type="text" id="item_name" name="item_name" required>
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" min="1" required>
                <button type="submit" name="submit_order">Place Order</button>
            </form>
            <?php if ($is_admin): ?>
                <h3>Admin: View and Process Orders</h3>
                <table>
                    <tr><th>Order ID</th><th>User Name</th><th>Item</th><th>Quantity</th><th>Status</th><th>Action</th></tr>
                    <?php
                    $sql = "SELECT o.order_id, r.name, o.item_name, o.quantity, o.order_status FROM orders o JOIN registration r ON o.user_id = r.user_id ORDER BY o.order_timestamp DESC";
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= $row['order_id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= htmlspecialchars($row['quantity']) ?></td>
                            <td><?= htmlspecialchars($row['order_status']) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                    <select name="new_status">
                                        <option value="pending" <?= $row['order_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $row['order_status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="rejected" <?= $row['order_status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                    </select>
                                    <button type="submit" name="update_order_status">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <h3>Your Orders</h3>
                <?php
                $sql = "SELECT item_name, quantity, order_status FROM orders WHERE user_id = ? ORDER BY order_timestamp DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    echo "<table><tr><th>Item</th><th>Quantity</th><th>Status</th></tr>";
                    while($row = $result->fetch_assoc()) {
                        echo "<tr><td>" . htmlspecialchars($row['item_name']) . "</td><td>" . htmlspecialchars($row['quantity']) . "</td><td>" . htmlspecialchars($row['order_status']) . "</td></tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>You have no pending orders.</p>";
                }
                $stmt->close();
                ?>
            <?php endif; ?>
        <?php else: ?>
            <p>Please log in to use the pre-booking service.</p>
        <?php endif; ?>

        <hr>

        <h2 id="reports">Reports & Suggestions</h2>
        <?php if ($is_logged_in): ?>
            <h3>Submit a Report or Suggestion</h3>
            <form method="post">
                <textarea name="report_message" rows="4" cols="50" required></textarea><br>
                <button type="submit" name="submit_report">Submit</button>
            </form>
            <?php if ($is_admin): ?>
                <h3>Admin: View Reports</h3>
                <table>
                    <tr><th>ID</th><th>User Name</th><th>Message</th><th>Status</th><th>Action</th></tr>
                    <?php
                    $sql = "SELECT s.suggestion_id, r.name, s.message, s.status FROM suggestions s JOIN registration r ON s.user_id = r.user_id ORDER BY s.submitted_at DESC";
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= $row['suggestion_id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['message']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="suggestion_id" value="<?= $row['suggestion_id'] ?>">
                                    <select name="new_status">
                                        <option value="new" <?= $row['status'] == 'new' ? 'selected' : '' ?>>New</option>
                                        <option value="resolved" <?= $row['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                    </select>
                                    <button type="submit" name="update_report_status">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <h3>Your Suggestions</h3>
                <?php
                $sql = "SELECT message, status FROM suggestions WHERE user_id = ? ORDER BY submitted_at DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    echo "<table><tr><th>Message</th><th>Status</th></tr>";
                    while($row = $result->fetch_assoc()) {
                        echo "<tr><td>" . htmlspecialchars($row['message']) . "</td><td>" . htmlspecialchars($row['status']) . "</td></tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>You have no reports or suggestions.</p>";
                }
                $stmt->close();
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$conn->close();
?>