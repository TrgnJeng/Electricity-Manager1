<?php
// ==================== CẤU HÌNH DATABASE ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Laragon để trống
define('DB_NAME', 'electricity_manager');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', '/THANHTOANDIEN/uploads/');

// Tạo thư mục uploads nếu chưa có
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// ==================== HÀM KẾT NỐI DATABASE ====================
function getDB() {
    // Kết nối MySQL không chọn database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Kiểm tra kết nối
    if ($conn->connect_error) {
        die("❌ Không thể kết nối MySQL: " . $conn->connect_error);
    }
    
    // Tạo database nếu chưa có
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    
    // Chọn database
    $conn->select_db(DB_NAME);
    
    // Đặt charset
    $conn->set_charset('utf8mb4');
    
    // Tạo các bảng cần thiết
    createTables($conn);
    
    return $conn;
}

// ==================== TẠO BẢNG ====================
function createTables($conn) {
    // Tạo bảng households
    $conn->query("
        CREATE TABLE IF NOT EXISTS households (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(10) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            price INT DEFAULT NULL COMMENT 'Giá điện riêng (VNĐ/kWh)',
            phone VARCHAR(20) DEFAULT NULL,
            email VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Tạo bảng settings
    $conn->query("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value VARCHAR(100) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Tạo bảng bills
    $conn->query("
        CREATE TABLE IF NOT EXISTS bills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            household_id INT NOT NULL,
            month INT NOT NULL,
            year INT NOT NULL,
            old_reading INT DEFAULT 0,
            new_reading INT NOT NULL,
            consumption INT DEFAULT 0,
            amount INT DEFAULT 0,
            price_used INT NOT NULL DEFAULT 0 COMMENT 'Giá điện áp dụng',
            paid BOOLEAN DEFAULT FALSE,
            paid_at DATETIME NULL,
            note TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_bill (household_id, month, year),
            FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Tạo bảng meter_images để lưu ảnh công tơ
    $conn->query("
        CREATE TABLE IF NOT EXISTS meter_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bill_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            ai_reading INT DEFAULT NULL,
            manual_reading INT DEFAULT NULL,
            confirmed BOOLEAN DEFAULT FALSE,
            status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Kiểm tra và thêm dữ liệu mẫu
    $result = $conn->query("SELECT COUNT(*) as count FROM households");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $conn->query("
            INSERT INTO households (code, name, price, phone, email) VALUES
            ('HD01', 'Hộ 1 - Nguyễn Văn A', 3800, '0901234567', 'nguyenvana@gmail.com'),
            ('HD02', 'Hộ 2 - Trần Thị B', NULL, '0901234568', 'tranthib@gmail.com'),
            ('HD03', 'Hộ 3 - Lê Văn C', 3600, '0901234569', 'levanc@gmail.com'),
            ('HD04', 'Hộ 4 - Phạm Thị D', NULL, '0901234570', 'phamthid@gmail.com'),
            ('HD05', 'Hộ 5 - Hoàng Văn E', 4000, '0901234571', 'hoangvane@gmail.com'),
            ('HD06', 'Hộ 6 - Vũ Thị F', NULL, '0901234572', 'vuthif@gmail.com')
        ");
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM settings");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $conn->query("
            INSERT INTO settings (setting_key, setting_value) VALUES
            ('gia_dien', '3500'),
            ('so_tk', '4802205120449'),
            ('ma_ngan_hang', '970403'),
            ('ten_ngan_hang', 'Agribank'),
            ('chu_tk', 'VO VAN MINH')
        ");
    }
}

// ==================== HÀM LẤY GIÁ ====================
function getDefaultPrice($conn) {
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'gia_dien'");
    if ($result && $result->num_rows > 0) {
        return (int)$result->fetch_assoc()['setting_value'];
    }
    return 3500;
}

function getHouseholdPrice($conn, $household_id) {
    $result = $conn->query("SELECT price FROM households WHERE id = $household_id");
    if ($result && $result->num_rows > 0) {
        $price = $result->fetch_assoc()['price'];
        if ($price !== null && $price > 0) {
            return (int)$price;
        }
    }
    return getDefaultPrice($conn);
}

// ==================== HÀM LẤY THÔNG TIN ====================
function getBankInfo($conn) {
    $bank = [
        'so_tk' => '',
        'ma_ngan_hang' => '',
        'ten_ngan_hang' => '',
        'chu_tk' => ''
    ];
    
    $result = $conn->query("SELECT * FROM settings WHERE setting_key IN ('so_tk', 'ma_ngan_hang', 'ten_ngan_hang', 'chu_tk')");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bank[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $bank;
}

function getHouseholds($conn) {
    return $conn->query("SELECT * FROM households ORDER BY id");
}

function getBillsByMonth($conn, $thang, $nam) {
    return $conn->query("
        SELECT b.*, h.name, h.code, h.price as household_price, h.phone 
        FROM bills b 
        JOIN households h ON b.household_id = h.id 
        WHERE b.month = $thang AND b.year = $nam 
        ORDER BY h.id
    ");
}

function getHouseholdPriceHistory($conn, $household_id) {
    return $conn->query("
        SELECT month, year, price_used 
        FROM bills 
        WHERE household_id = $household_id 
        ORDER BY year DESC, month DESC 
        LIMIT 12
    ");
}

// ==================== HÀM FORMAT ====================
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

function formatNumber($num) {
    return number_format($num, 0, ',', '.');
}

// ==================== HÀM XỬ LÝ ẢNH ====================
function downloadImage($url, $savePath) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $data) {
        file_put_contents($savePath, $data);
        return true;
    }
    return false;
}

// ==================== HÀM TẠO QR CODE (QUAN TRỌNG NHẤT) ====================

/**
 * Tạo nội dung chuyển khoản theo định dạng yêu cầu
 * Format: CK thang2 090xxxxxxx
 */
function createTransferContent($month, $phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone); // Chỉ giữ lại số
    if (empty($phone)) {
        $phone = 'khongcosdt';
    }
    return "CK thang{$month} {$phone}";
}

/**
 * Lưu QR Code với thông tin đầy đủ
 */
function saveQRCode($bank, $amount, $code, $bill_id, $bill_info = []) {
    if (empty($bank['ma_ngan_hang']) || empty($bank['so_tk'])) {
        return 'https://via.placeholder.com/300?text=No+Bank+Info';
    }
    
    // Tạo tên file
    $filename = 'qr_' . $bill_id . '_' . time() . '.png';
    $filepath = UPLOAD_DIR . $filename;
    $fileUrl = UPLOAD_URL . $filename;
    
    // Nếu file đã tồn tại, trả về luôn
    if (file_exists($filepath)) {
        return $fileUrl;
    }
    
    // Lấy thông tin để tạo nội dung
    $thang = $bill_info['month'] ?? date('m');
    $phone = $bill_info['phone'] ?? '';
    $noidung = createTransferContent($thang, $phone);
    
    // URL QR từ VietQR
    $qrUrl = "https://img.vietqr.io/image/{$bank['ma_ngan_hang']}-{$bank['so_tk']}-compact2.jpg";
    $qrUrl .= "?amount=" . intval($amount);
    $qrUrl .= "&addInfo=" . urlencode($noidung);
    $qrUrl .= "&accountName=" . urlencode($bank['chu_tk'] ?? '');
    
    // Thử tải bằng cURL
    $downloaded = downloadImage($qrUrl, $filepath);
    
    if ($downloaded) {
        // Lưu thêm file thông tin để đối chiếu
        $infofile = UPLOAD_DIR . 'info_' . $bill_id . '_' . time() . '.txt';
        $infoContent = "Tên người nhận: " . $bank['chu_tk'] . "\n";
        $infoContent .= "Số tài khoản: " . $bank['so_tk'] . "\n";
        $infoContent .= "Ngân hàng: " . $bank['ten_ngan_hang'] . "\n";
        $infoContent .= "Số tiền: " . number_format($amount) . " VNĐ\n";
        $infoContent .= "Nội dung: " . $noidung . "\n";
        $infoContent .= "Mã hộ: " . $code . "\n";
        $infoContent .= "Kỳ: Tháng {$thang}\n";
        file_put_contents($infofile, $infoContent);
        
        return $fileUrl;
    }
    
    // Nếu không tải được, dùng link trực tiếp
    return $qrUrl;
}

/**
 * Lấy URL QR Code (ưu tiên file đã lưu)
 */
function getQRUrl($bank, $amount, $code, $bill_id = null, $bill_info = []) {

    // Validate ngân hàng
    if (empty($bank['ma_ngan_hang']) || empty($bank['so_tk'])) {
        return 'https://via.placeholder.com/300?text=No+Bank+Info';
    }

    $bankCode      = strtoupper(trim($bank['ma_ngan_hang']));
    $accountNumber = trim($bank['so_tk']);
    $accountName   = trim($bank['chu_tk'] ?? '');

    // Làm sạch số tiền
    $amount = intval(preg_replace('/\D/', '', $amount));
    $amount = max(0, $amount);

    // Nội dung chuyển khoản
    $thang  = $bill_info['month'] ?? date('m');
    $phone  = $bill_info['phone'] ?? '';
    $noidung = createTransferContent($thang, $phone);
    $content = urlencode($noidung);

    // ==============================
    // Nếu có bill_id → ưu tiên file đã lưu
    // ==============================
    if (!empty($bill_id)) {

        if (defined('UPLOAD_DIR') && defined('UPLOAD_URL') && is_dir(UPLOAD_DIR)) {

            $pattern = UPLOAD_DIR . "qr_{$bill_id}_*.png";
            $files = glob($pattern) ?: [];

            if (!empty($files)) {
                usort($files, function($a, $b) {
                    return filemtime($b) <=> filemtime($a);
                });

                return UPLOAD_URL . basename($files[0]);
            }
        }

        // Nếu chưa có → tạo mới
        if (function_exists('saveQRCode')) {
            return saveQRCode($bank, $amount, $code, $bill_id, $bill_info);
        }
    }

    // ==============================
    // Fallback VietQR online
    // ==============================
    return "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-compact2.jpg"
        . "?amount={$amount}"
        . "&addInfo={$content}"
        . "&accountName=" . urlencode($accountName);
}
/**
 * Tạo QR dự phòng
 */
function getBackupQRUrl($amount, $code, $bill_info = []) {
    $thang = $bill_info['month'] ?? date('m');
    $phone = $bill_info['phone'] ?? '';
    
    // Ép kiểu số tiền
    $amount = intval($amount);

    // Nội dung chuyển khoản
    $noidung = createTransferContent($thang, $phone);

    $text = "Chuyen khoan tien dien thang {$thang} - {$phone} - {$amount} VND";

    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($text);
}