USE property_db;

-- Insert an admin user (password will be 'admin123')
INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@example.com', '$2y$10$8K1p/a0WpYoKv2oPMy0pouqQQYqibwpGoCm1aQD3YfvgHNHjEEHK.', 'admin')
ON DUPLICATE KEY UPDATE id=id; 