-- Database: college_canteen_service
-- Note: Assuming your database is named 'college_canteen_service' or similar.

-- 1. Menu Table
CREATE TABLE menu (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL UNIQUE,
    price DECIMAL(5, 2) NOT NULL,
    is_available_today BOOLEAN DEFAULT FALSE, -- Flag for today's menu
    special_item BOOLEAN DEFAULT FALSE,      -- Flag for items only available today (not general menu)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Prebooking Table
CREATE TABLE prebooking (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Reports Table
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'viewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample Data (General Menu Items)
INSERT INTO menu (item_name, price) VALUES
('Chicken Biryani', 120.00),
('Veg Pulao', 80.00),
('Masala Dosa', 50.00),
('Coffee', 25.00),
('Tea', 20.00);