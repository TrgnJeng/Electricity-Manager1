-- Tạo database trước khi sử dụng
CREATE DATABASE IF NOT EXISTS electricity_manager;
USE electricity_manager;

-- Bảng hộ gia đình
CREATE TABLE IF NOT EXISTS households (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL
);

-- Bảng hóa đơn
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    old_reading INT DEFAULT 0,
    new_reading INT NOT NULL,
    consumption INT DEFAULT 0,
    amount INT DEFAULT 0,
    paid BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_bill (household_id, month, year)
);

-- Bảng cài đặt
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(100) NOT NULL
);

-- Thêm 6 hộ mặc định
INSERT INTO households (code, name) VALUES
('HD01', 'Hộ 1 - Nguyễn Văn A'),
('HD02', 'Hộ 2 - Trần Thị B'),
('HD03', 'Hộ 3 - Lê Văn C'),
('HD04', 'Hộ 4 - Phạm Thị D'),
('HD05', 'Hộ 5 - Hoàng Văn E'),
('HD06', 'Hộ 6 - Vũ Thị F');

-- Cài đặt mặc định
INSERT INTO settings (setting_key, setting_value) VALUES
('gia_dien', '3500'),
('so_tk', '123456789'),
('ma_ngan_hang', '970415'),
('ten_ngan_hang', 'VietinBank'),
('chu_tk', 'NGUYEN VAN A');