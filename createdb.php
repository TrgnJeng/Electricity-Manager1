// Bước 2: Chọn database
echo "<div class='step'><strong>Bước 2:</strong> Chọn database...<br>";

if ($conn->select_db($dbname)) {
    echo "<div class='success'>✅ Đã chọn database <code>$dbname</code></div>";
} else {
    echo "<div class='error'>❌ Không tìm thấy database $dbname</div>";
    exit;
}
echo "</div>";

// Bước 3: Thêm cột price nếu chưa có
echo "<div class='step'><strong>Bước 3:</strong> Cập nhật cấu trúc bảng...<br>";

// Kiểm tra cột tồn tại chưa
$check = $conn->query("SHOW COLUMNS FROM households LIKE 'price'");
if ($check->num_rows == 0) {

    if ($conn->query("ALTER TABLE households ADD COLUMN price INT DEFAULT NULL COMMENT 'Giá điện riêng (VNĐ/kWh), NULL nếu dùng giá chung'")) {
        echo "<div class='success'>✅ Đã thêm cột price</div>";
    } else {
        echo "<div class='error'>❌ Lỗi thêm cột: " . $conn->error . "</div>";
    }

} else {
    echo "<div class='info'>ℹ Cột price đã tồn tại</div>";
}

// Update dữ liệu
$conn->query("UPDATE households SET price = 3800 WHERE code = 'HD01'");
$conn->query("UPDATE households SET price = 3600 WHERE code = 'HD03'");
$conn->query("UPDATE households SET price = 4000 WHERE code = 'HD05'");

echo "<div class='success'>✅ Đã cập nhật giá điện riêng cho một số hộ</div>";
echo "</div>";