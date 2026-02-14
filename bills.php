<?php
require_once 'config.php';
$conn = getDB();

// Xử lý sửa chỉ số
if(isset($_POST['sua'])) {
    $bill_id = (int)$_POST['bill_id'];
    $chiso_moi = (int)$_POST['new_reading'];
    
    // Lấy thông tin bill hiện tại
    $bill_result = $conn->query("SELECT * FROM bills WHERE id = $bill_id");
    if($bill_result && $bill_result->num_rows > 0) {
        $bill_info = $bill_result->fetch_assoc();
        $id_ho = $bill_info['household_id'];
        $thang = $bill_info['month'];
        $nam = $bill_info['year'];
        
        // Lấy chỉ số cũ từ tháng trước (không tính bill hiện tại)
        $result = $conn->query("SELECT new_reading FROM bills 
                                WHERE household_id = $id_ho AND id != $bill_id
                                ORDER BY year DESC, month DESC LIMIT 1");
        $cu = $result->num_rows > 0 ? $result->fetch_assoc()['new_reading'] : 0;
        
        // KIỂM TRA: Chỉ số mới phải lớn hơn hoặc bằng chỉ số cũ
        if($chiso_moi < $cu) {
            header("Location: bills.php?thang=$thang&nam=$nam&error=" . urlencode("Chỉ số mới ($chiso_moi kWh) không thể thấp hơn chỉ số tháng trước ($cu kWh)"));
            exit;
        }
        
        // Lấy giá điện riêng
        $gia = getHouseholdPrice($conn, $id_ho);
        $tieu_thu = $chiso_moi - $cu;
        $tien = $tieu_thu * $gia;
        
        // Cập nhật bill
        $conn->query("UPDATE bills SET 
                      old_reading = $cu, 
                      new_reading = $chiso_moi, 
                      consumption = $tieu_thu, 
                      amount = $tien 
                      WHERE id = $bill_id");
        
        header("Location: index.php?thang=$thang&nam=$nam&msg=Đã sửa chỉ số thành công");
        exit;
    } else {
        header("Location: index.php?msg=Không tìm thấy hóa đơn");
        exit;
    }
}

// Xử lý lưu chỉ số mới
if(isset($_POST['luu'])) {
    $thang = (int)$_POST['thang'];
    $nam = (int)$_POST['nam'];
    $id_ho = (int)$_POST['household_id'];
    $chiso_moi = (int)$_POST['new_reading'];
    
    // Lấy chỉ số cũ
    $result = $conn->query("SELECT new_reading FROM bills 
                            WHERE household_id = $id_ho 
                            ORDER BY year DESC, month DESC LIMIT 1");
    $cu = $result->num_rows > 0 ? $result->fetch_assoc()['new_reading'] : 0;
    
    // KIỂM TRA: Chỉ số mới phải lớn hơn hoặc bằng chỉ số cũ
    if($chiso_moi < $cu) {
        header("Location: bills.php?thang=$thang&nam=$nam&error=" . urlencode("Chỉ số mới ($chiso_moi kWh) không thể thấp hơn chỉ số tháng trước ($cu kWh)"));
        exit;
    }
    
    // Lấy giá điện riêng
    $gia = getHouseholdPrice($conn, $id_ho);
    $tieu_thu = $chiso_moi - $cu;
    $tien = $tieu_thu * $gia;
    
    // Kiểm tra đã có chưa
    $check = $conn->query("SELECT id FROM bills WHERE household_id = $id_ho AND month = $thang AND year = $nam");
    
    if($check->num_rows > 0) {
        $conn->query("UPDATE bills SET 
                      old_reading = $cu, 
                      new_reading = $chiso_moi, 
                      consumption = $tieu_thu, 
                      amount = $tien 
                      WHERE household_id = $id_ho AND month = $thang AND year = $nam");
        $msg = "Đã cập nhật chỉ số thành công";
    } else {
        $conn->query("INSERT INTO bills 
                     (household_id, month, year, old_reading, new_reading, consumption, amount) 
                     VALUES ($id_ho, $thang, $nam, $cu, $chiso_moi, $tieu_thu, $tien)");
        $msg = "Đã thêm hóa đơn mới thành công";
    }
    
    header("Location: index.php?thang=$thang&nam=$nam&msg=" . urlencode($msg));
    exit;
}

// Lấy tháng và năm
$thang = isset($_GET['thang']) ? (int)$_GET['thang'] : date('m');
$nam = isset($_GET['nam']) ? (int)$_GET['nam'] : date('Y');

// Lấy danh sách hộ
$households = $conn->query("SELECT * FROM households ORDER BY id");
$gia_chung = getDefaultPrice($conn);

// Lấy danh sách hóa đơn đã có trong tháng
$existing_bills = $conn->query("
    SELECT b.*, h.name, h.code 
    FROM bills b 
    JOIN households h ON b.household_id = h.id 
    WHERE b.month = $thang AND b.year = $nam 
    ORDER BY h.id
");
$bills_data = [];
if($existing_bills) {
    while($bill = $existing_bills->fetch_assoc()) {
        $bills_data[$bill['household_id']] = $bill;
    }
}

// Lấy chỉ số cũ của từng hộ để hiển thị
$old_readings = [];
$households->data_seek(0);
while($ho = $households->fetch_assoc()) {
    $result = $conn->query("SELECT new_reading FROM bills 
                            WHERE household_id = {$ho['id']} 
                            ORDER BY year DESC, month DESC LIMIT 1");
    $old_readings[$ho['id']] = $result->num_rows > 0 ? $result->fetch_assoc()['new_reading'] : 0;
}
$households->data_seek(0);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Nhập chỉ số điện</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Giữ nguyên tất cả style cũ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #60a5fa;
            --success: #10b981;
            --success-dark: #059669;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --danger-dark: #dc2626;
            --dark: #0f172a;
            --gray: #475569;
            --light-gray: #f1f5f9;
            --border: #e2e8f0;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 16px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><circle cx="50" cy="50" r="40" fill="white"/></svg>') repeat;
            pointer-events: none;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 24px;
            border-radius: 32px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header h1 {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .back-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .error-message {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            padding: 16px;
            border-radius: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 2px solid #dc2626;
            animation: shake 0.5s ease;
            font-weight: 600;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-message i {
            font-size: 24px;
            color: #dc2626;
        }

        .form-container {
            background: white;
            border-radius: 32px;
            padding: 24px;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .info-box {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 22px;
            padding: 20px;
            margin-bottom: 24px;
            border-left: 5px solid var(--primary);
            transition: all 0.3s;
            animation: slideIn 0.3s ease;
        }

        .info-box:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .info-box-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-default {
            background: white;
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 600;
            color: var(--primary);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .old-reading-info {
            font-size: 13px;
            color: var(--gray);
            margin-top: 12px;
            padding: 10px;
            background: #eef2ff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .old-reading-info i {
            color: var(--primary);
        }

        .old-reading-info strong {
            color: var(--danger-dark);
            font-weight: 700;
        }

        .entered-badge {
            background: var(--success);
            color: white;
            padding: 4px 10px;
            border-radius: 40px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 8px;
        }

        .household-selector {
            margin-bottom: 25px;
        }
        
        .household-card {
            background: var(--light-gray);
            border-radius: 22px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .household-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .household-card:hover {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 15px 30px -12px var(--primary);
            transform: translateX(5px);
        }

        .household-card:hover::before {
            opacity: 1;
        }
        
        .household-card.selected {
            background: #eef2ff;
            border-color: var(--primary);
            box-shadow: 0 10px 20px -8px var(--primary);
        }

        .household-card.has-bill {
            border-left: 4px solid var(--success);
        }
        
        .household-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #2563eb20, #1e40af20);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 20px;
            transition: all 0.3s;
        }

        .household-card:hover .household-avatar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            transform: rotate(5deg) scale(1.1);
        }
        
        .household-info {
            flex: 1;
        }
        
        .household-info h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .household-info p {
            font-size: 13px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .household-old-reading {
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .household-old-reading i {
            color: var(--primary);
            font-size: 10px;
        }

        .household-old-reading span {
            background: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .price-tag {
            margin-left: auto;
            padding: 6px 12px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .price-tag.special {
            background: #fef3c7;
            color: #d97706;
        }
        
        .price-tag.default {
            background: #eef2ff;
            color: var(--primary);
        }

        .input-section {
            background: var(--light-gray);
            border-radius: 24px;
            padding: 24px;
            margin-top: 24px;
            animation: slideIn 0.4s ease;
            border: 1px solid var(--border);
        }

        .input-section h4 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-section h4 i {
            color: var(--primary);
        }

        .selected-price {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px;
            background: white;
            border-radius: 40px;
            display: inline-block;
            box-shadow: var(--shadow-sm);
        }

        .current-reading {
            background: #eef2ff;
            border-radius: 16px;
            padding: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--primary);
        }

        .current-reading span {
            font-size: 14px;
            color: var(--dark);
        }

        .current-reading strong {
            color: var(--primary);
            font-size: 18px;
        }

        /* Style cho phần validation màu đỏ đậm */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 6px;
        }

        .form-control {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid var(--border);
            border-radius: 24px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            transform: translateY(-2px);
        }

        /* Style lỗi - MÀU ĐỎ ĐẬM */
        .form-control.error {
            border-color: var(--danger-dark);
            background: var(--danger-light);
            color: var(--danger-dark);
            font-weight: 600;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.15);
            animation: pulse-red 1s infinite;
        }

        @keyframes pulse-red {
            0% { border-color: var(--danger-dark); }
            50% { border-color: #ef4444; }
            100% { border-color: var(--danger-dark); }
        }

        .error-text {
            color: var(--danger-dark);
            font-size: 14px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--danger-light);
            padding: 12px;
            border-radius: 16px;
            border-left: 4px solid var(--danger-dark);
            font-weight: 600;
        }

        .error-text i {
            font-size: 16px;
            color: var(--danger-dark);
        }

        .note {
            margin: 20px 0;
            padding: 16px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #f59e0b40;
        }

        .note i {
            font-size: 18px;
            color: #f59e0b;
        }

        .note strong {
            font-weight: 700;
        }

        .btn-save {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--success), var(--success-dark));
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.4s;
            box-shadow: 0 15px 30px -8px var(--success);
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .btn-save::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-save:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 40px -10px var(--success);
        }

        .btn-save:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-save:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            background: #94a3b8;
        }

        .btn-save:disabled:hover {
            transform: none;
        }

        .btn-save:disabled::before {
            display: none;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-cancel {
            flex: 1;
            padding: 16px;
            border: 2px solid var(--border);
            border-radius: 40px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            background: var(--light-gray);
            color: var(--dark);
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        #previewText {
            font-size: 15px;
        }

        #previewText strong {
            color: var(--danger);
            font-size: 18px;
        }

        .validation-warning {
            color: var(--danger);
            font-size: 13px;
            margin-top: 8px;
            display: none;
            align-items: center;
            gap: 6px;
        }

        .validation-warning i {
            font-size: 14px;
        }

        /* Badge cảnh báo */
        .warning-badge {
            background: var(--danger-light);
            color: var(--danger-dark);
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            border: 1px solid var(--danger-dark);
        }

        @media (max-width: 480px) {
            .header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .back-btn {
                width: 100%;
                justify-content: center;
            }

            .price-grid {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .household-card {
                flex-wrap: wrap;
            }

            .price-tag {
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-pen"></i>
                Nhập chỉ số tháng <?=$thang?>/<?=$nam?>
            </h1>
            <a href="index.php?thang=<?=$thang?>&nam=<?=$nam?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <?php if(isset($_GET['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?=htmlspecialchars($_GET['error'])?></span>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="info-box">
                <div class="info-box-title">
                    <i class="fas fa-info-circle"></i>
                    Thông tin giá điện
                </div>
                <div class="price-grid">
                    <span>Giá mặc định:</span>
                    <span class="price-default"><?=number_format($gia_chung)?>đ/kWh</span>
                </div>
                <div class="old-reading-info">
                    <i class="fas fa-history"></i>
                    <span>Chỉ số tháng trước hiển thị bên cạnh mỗi hộ. Chỉ số mới <strong class="text-danger">PHẢI LỚN HƠN</strong> chỉ số cũ.</span>
                </div>
                <div class="warning-badge">
                    <i class="fas fa-exclamation-triangle"></i>
                    Nếu nhập sai, ô nhập sẽ chuyển <span style="color: #dc2626; font-weight: 800;">MÀU ĐỎ</span> và không thể lưu
                </div>
            </div>
            
            <h3 style="margin-bottom: 15px; font-size: 16px; color: var(--dark);">
                <i class="fas fa-hand-pointer" style="color: var(--primary);"></i>
                Chọn hộ để nhập chỉ số:
            </h3>
            
            <div class="household-selector">
                <?php 
                $index = 0;
                $households->data_seek(0);
                while($ho = $households->fetch_assoc()): 
                    $gia_ho = $ho['price'] ?? $gia_chung;
                    $gia_class = $ho['price'] ? 'special' : 'default';
                    $animation_delay = $index * 0.05;
                    $has_bill = isset($bills_data[$ho['id']]);
                    $bill = $has_bill ? $bills_data[$ho['id']] : null;
                    $old_reading = $old_readings[$ho['id']];
                ?>
                <div class="household-card <?=$has_bill ? 'has-bill' : ''?>" 
                     onclick="selectHousehold(<?=$ho['id']?>, '<?=htmlspecialchars($ho['name'])?>', <?=$gia_ho?>, <?=$old_reading?>, <?=$has_bill ? 'true' : 'false'?>, <?=$bill ? $bill['id'] : 'null'?>, <?=$bill ? $bill['new_reading'] : 'null'?>)" 
                     style="animation-delay: <?=$animation_delay?>s;">
                    <div class="household-avatar">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="household-info">
                        <h4>
                            <?=htmlspecialchars($ho['name'])?>
                            <?php if($has_bill): ?>
                                <span class="entered-badge">
                                    <i class="fas fa-check"></i> Đã nhập
                                </span>
                            <?php endif; ?>
                        </h4>
                        <p><i class="fas fa-hashtag" style="font-size: 10px;"></i> <?=$ho['code']?></p>
                        <div class="household-old-reading">
                            <i class="fas fa-history"></i>
                            Chỉ số cũ: <span><?=number_format($old_reading)?> kWh</span>
                        </div>
                    </div>
                    <span class="price-tag <?=$gia_class?>">
                        <i class="fas fa-tag"></i>
                        <?=number_format($gia_ho)?>đ
                    </span>
                </div>
                <?php 
                $index++;
                endwhile; 
                ?>
            </div>
            
            <div id="inputSection" class="input-section" style="display: none;">
                <form method="POST" id="readingForm" onsubmit="return validateForm()">
                    <input type="hidden" name="thang" value="<?=$thang?>">
                    <input type="hidden" name="nam" value="<?=$nam?>">
                    <input type="hidden" name="household_id" id="selectedHouseholdId">
                    <input type="hidden" name="bill_id" id="selectedBillId">
                    <input type="hidden" id="oldReadingValue" value="0">
                    
                    <div style="text-align: center;">
                        <h4 id="selectedHouseholdName">
                            <i class="fas fa-home"></i>
                        </h4>
                        <span class="selected-price" id="selectedHouseholdPrice"></span>
                    </div>
                    
                    <div id="currentReadingBox" class="current-reading" style="display: none;">
                        <span><i class="fas fa-history"></i> Chỉ số hiện tại:</span>
                        <strong id="currentReading"></strong>
                    </div>
                    
                    <div id="oldReadingBox" class="current-reading" style="background: #eef2ff;">
                        <span><i class="fas fa-history"></i> Chỉ số tháng trước:</span>
                        <strong id="oldReadingDisplay"></strong>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-bolt"></i>
                            Chỉ số mới (kWh)
                        </label>
                        <input type="number" name="new_reading" id="newReading" class="form-control" 
                               placeholder="Nhập chỉ số mới" required min="0" oninput="validateReading()">
                        <div id="validationError" class="error-text" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span></span>
                        </div>
                    </div>
                    
                    <div class="note" id="previewBox" style="display: none;">
                        <i class="fas fa-calculator"></i>
                        <span id="previewText"></span>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="cancelInput()">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <button type="submit" name="" id="submitBtn" class="btn-save" style="margin-top: 0;" disabled>
                            <i class="fas fa-save"></i> <span id="submitText">Lưu chỉ số</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentPrice = 0;
        let isEdit = false;
        let currentBillId = null;
        let oldReading = 0;
        
        function selectHousehold(id, name, price, oldRead, hasBill = false, billId = null, currentRead = null) {
            // Remove selected class from all cards
            document.querySelectorAll('.household-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Show input section
            document.getElementById('inputSection').style.display = 'block';
            
            // Set values
            document.getElementById('selectedHouseholdId').value = id;
            document.getElementById('selectedHouseholdName').innerHTML = 
                '<i class="fas fa-home"></i> ' + name;
            document.getElementById('selectedHouseholdPrice').innerHTML = 
                '💰 Giá áp dụng: ' + Number(price).toLocaleString() + 'đ/kWh';
            
            // Store values
            currentPrice = price;
            oldReading = oldRead;
            
            // Display old reading
            document.getElementById('oldReadingDisplay').textContent = Number(oldRead).toLocaleString() + ' kWh';
            document.getElementById('oldReadingValue').value = oldRead;
            
            // Check if editing existing bill
            isEdit = hasBill;
            currentBillId = billId;
            
            if (isEdit && billId) {
                document.getElementById('submitBtn').name = 'sua';
                document.getElementById('submitText').textContent = 'Cập nhật chỉ số';
                document.getElementById('currentReadingBox').style.display = 'flex';
                document.getElementById('currentReading').textContent = Number(currentRead).toLocaleString() + ' kWh';
                document.getElementById('newReading').value = currentRead;
            } else {
                document.getElementById('submitBtn').name = 'luu';
                document.getElementById('submitText').textContent = 'Lưu chỉ số';
                document.getElementById('currentReadingBox').style.display = 'none';
                document.getElementById('newReading').value = '';
                document.getElementById('selectedBillId').value = '';
            }
            
            // Focus on input
            const input = document.getElementById('newReading');
            input.focus();
            
            // Hide preview box and validate
            document.getElementById('previewBox').style.display = 'none';
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('validationError').style.display = 'none';
            document.getElementById('newReading').classList.remove('error');
            
            // Validate current value if editing
            if (isEdit && currentRead) {
                validateReading();
            }
        }
        
        // Validate reading - MÀU ĐỎ ĐẬM KHI SAI
        function validateReading() {
            const newReading = parseInt(document.getElementById('newReading').value) || 0;
            const errorDiv = document.getElementById('validationError');
            const errorSpan = errorDiv.querySelector('span');
            const submitBtn = document.getElementById('submitBtn');
            const input = document.getElementById('newReading');
            
            if (newReading < oldReading) {
                // HIỂN THỊ LỖI MÀU ĐỎ ĐẬM
                errorSpan.innerHTML = `❌ CHỈ SỐ KHÔNG HỢP LỆ: ${newReading.toLocaleString()} kWh < ${oldReading.toLocaleString()} kWh`;
                errorDiv.style.display = 'flex';
                input.classList.add('error');
                submitBtn.disabled = true;
                
                // Log để debug
                console.log('Validation failed:', newReading, '<', oldReading);
                return false;
            } else {
                errorDiv.style.display = 'none';
                input.classList.remove('error');
                submitBtn.disabled = false;
                console.log('Validation passed:', newReading, '>=', oldReading);
                return true;
            }
        }
        
        // Preview calculation
        document.getElementById('newReading')?.addEventListener('input', function() {
            const previewBox = document.getElementById('previewBox');
            const previewText = document.getElementById('previewText');
            
            // Validate first
            validateReading();
            
            if(this.value && this.value > 0 && parseInt(this.value) >= oldReading) {
                const amount = this.value * currentPrice;
                previewText.innerHTML = `Số tiền dự kiến: <strong>${amount.toLocaleString()}đ</strong>`;
                previewBox.style.display = 'flex';
            } else {
                previewBox.style.display = 'none';
            }
        });
        
        // Form validation before submit
        function validateForm() {
            const newReading = parseInt(document.getElementById('newReading').value) || 0;
            
            if (newReading < oldReading) {
                alert(`❌ KHÔNG THỂ LƯU!\nChỉ số mới (${newReading.toLocaleString()} kWh) phải lớn hơn hoặc bằng chỉ số tháng trước (${oldReading.toLocaleString()} kWh)`);
                return false;
            }
            
            return true;
        }
        
        // Cancel input
        function cancelInput() {
            document.getElementById('inputSection').style.display = 'none';
            document.querySelectorAll('.household-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Reset validation
            document.getElementById('validationError').style.display = 'none';
            document.getElementById('newReading').classList.remove('error');
        }
        
        // Animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.household-card');
            cards.forEach((card, index) => {
                card.style.animation = `slideIn 0.3s ease ${index * 0.05}s both`;
            });
        });
    </script>
</body>
</html>