<?php
require_once 'config.php';
$conn = getDB();

// Lấy tháng và năm từ URL
$thang = isset($_GET['thang']) ? (int)$_GET['thang'] : date('m');
$nam = isset($_GET['nam']) ? (int)$_GET['nam'] : date('Y');

// Lấy dữ liệu
$gia = getDefaultPrice($conn);
$bank = getBankInfo($conn);

// Lấy danh sách hóa đơn (cần lấy thêm phone từ households)
$bills = $conn->query("
    SELECT b.*, h.name, h.code, h.phone 
    FROM bills b 
    JOIN households h ON b.household_id = h.id 
    WHERE b.month = $thang AND b.year = $nam 
    ORDER BY h.id
");

// Tính thống kê
$total_ho = $conn->query("SELECT COUNT(*) as count FROM households")->fetch_assoc()['count'];
$da_thanh_toan = $conn->query("SELECT COUNT(*) as count FROM bills WHERE month = $thang AND year = $nam AND paid = 1")->fetch_assoc()['count'] ?? 0;
$tong_tien = $conn->query("SELECT SUM(amount) as total FROM bills WHERE month = $thang AND year = $nam")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Electricity Manager</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Giữ nguyên tất cả style của bạn */
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
            padding: 28px 24px;
            border-radius: 32px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
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

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo-icon {
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow);
        }

        .logo-text h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            background: linear-gradient(135deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-text p {
            font-size: 13px;
            opacity: 0.9;
            font-weight: 400;
        }

        .badge {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 18px 12px;
            border-radius: 24px;
            text-align: center;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: var(--shadow-lg);
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-icon {
            font-size: 22px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 6px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1.2;
        }

        .tabs {
            display: flex;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 6px;
            border-radius: 100px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .tab {
            flex: 1;
            padding: 14px 8px;
            text-align: center;
            border-radius: 100px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .tab:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .tab.active {
            background: white;
            color: var(--primary);
            box-shadow: var(--shadow);
        }

        .month-selector {
            background: white;
            border-radius: 100px;
            padding: 6px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .month-nav {
            width: 44px;
            height: 44px;
            border-radius: 100px;
            border: none;
            background: var(--light-gray);
            color: var(--primary);
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .month-nav:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }

        .current-month {
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
            letter-spacing: 0.5px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 100px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.5);
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .bill-card {
            background: white;
            border-radius: 32px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-lg);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border);
            animation: slideIn 0.5s ease-out;
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

        .bill-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .household-info h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        .household-code {
            font-size: 13px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 4px;
            background: var(--light-gray);
            padding: 4px 10px;
            border-radius: 100px;
            width: fit-content;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
            box-shadow: var(--shadow-sm);
        }

        .status-paid {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .status-unpaid {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        .meter-readings {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .reading-item {
            text-align: center;
        }

        .reading-label {
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .reading-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
            background: white;
            padding: 8px;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
        }

        .amount-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 16px;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-radius: 20px;
        }

        .amount-label {
            font-size: 15px;
            font-weight: 600;
            color: var(--danger);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .amount-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--danger);
            text-shadow: 2px 2px 4px rgba(239, 68, 68, 0.2);
        }

        .qr-section {
            text-align: center;
            margin: 24px 0;
            padding: 20px;
            background: linear-gradient(135deg, #ffffff, #fafafa);
            border-radius: 28px;
            border: 2px dashed var(--primary-light);
            transition: all 0.3s;
        }

        .qr-section:hover {
            border-color: var(--primary);
            background: white;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            margin: 0 auto 15px;
            padding: 12px;
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s;
        }

        .qr-code:hover {
            transform: scale(1.05);
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 16px;
        }

        .qr-note {
            font-size: 15px;
            color: var(--primary);
            font-weight: 600;
            margin: 15px 0 10px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            border-radius: 100px;
            display: inline-block;
        }

        .qr-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .qr-actions .btn {
            padding: 10px 20px;
            font-size: 13px;
            border-radius: 100px;
            background: white;
            border: 2px solid var(--border);
            color: var(--dark);
            transition: all 0.2s;
        }

        .qr-actions .btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-zalo {
            width: 100%;
            background: linear-gradient(135deg, #0068ff, #0052cc);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            box-shadow: 0 15px 30px -5px rgba(0, 104, 255, 0.4);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-zalo::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            bottom: -50%;
            left: -50%;
            background: linear-gradient(to bottom, rgba(255,255,255,0.2), rgba(255,255,255,0));
            transform: rotateZ(60deg) translate(-5em, 7.5em);
            animation: shine 4s infinite;
        }

        @keyframes shine {
            100% {
                transform: rotateZ(60deg) translate(1em, -9em);
            }
        }

        .btn-zalo:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 40px -5px rgba(0, 104, 255, 0.5);
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 32px;
            box-shadow: var(--shadow-xl);
        }

        .empty-icon {
            font-size: 80px;
            color: var(--primary-light);
            margin-bottom: 25px;
            opacity: 0.7;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.1); opacity: 1; }
        }

        .toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--dark);
            color: white;
            padding: 16px 24px;
            border-radius: 100px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: var(--shadow-xl);
            opacity: 0;
            transition: all 0.3s;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @media (max-width: 380px) {
            .qr-code {
                width: 160px;
                height: 160px;
            }
            
            .amount-value {
                font-size: 22px;
            }
            
            .stat-value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="logo-text">
                        <h1>Electricity Manager</h1>
                        <p>Quản lý hóa đơn điện thông minh</p>
                    </div>
                </div>
                <div class="badge">
                    <i class="fas fa-users"></i>
                    <span><?=$total_ho?> hộ</span>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-home"></i></div>
                    <div class="stat-label">Tổng hộ</div>
                    <div class="stat-value"><?=$total_ho?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-label">Đã thanh toán</div>
                    <div class="stat-value"><?=$da_thanh_toan?>/<?=$total_ho?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-label">Tổng tiền</div>
                    <div class="stat-value"><?=number_format($tong_tien)?>đ</div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" data-tab="bills" onclick="showTab('bills')">
                <i class="fas fa-file-invoice"></i> Hóa đơn
            </div>
            <div class="tab" data-tab="settings" onclick="showTab('settings')">
                <i class="fas fa-sliders-h"></i> Cài đặt
            </div>
            <div class="tab" data-tab="households" onclick="showTab('households')">
                <i class="fas fa-users"></i> Hộ
            </div>
        </div>
        
        <!-- Tab Bills -->
        <div id="tab-bills">
            <!-- Month Selector -->
            <div class="month-selector">
                <button class="month-nav" onclick="changeMonth(-1, <?=$thang?>, <?=$nam?>)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="current-month">
                    <i class="fas fa-calendar-alt" style="margin-right: 8px; color: var(--primary);"></i>
                    Tháng <?=$thang?>/<?=$nam?>
                </span>
                <button class="month-nav" onclick="changeMonth(1, <?=$thang?>, <?=$nam?>)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="bills.php?thang=<?=$thang?>&nam=<?=$nam?>" class="btn btn-primary">
                    <i class="fas fa-pen"></i>
                    Nhập chỉ số
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-calendar-check"></i>
                    Tháng này
                </a>
            </div>
            
            <!-- Bills List -->
            <?php if($bills->num_rows == 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3>Chưa có hóa đơn</h3>
                    <p>Hãy nhập chỉ số điện để tạo hóa đơn</p>
                    <a href="bills.php?thang=<?=$thang?>&nam=<?=$nam?>" class="btn btn-primary" style="display: inline-block; padding: 16px 32px;">
                        <i class="fas fa-pen"></i> Nhập chỉ số
                    </a>
                </div>
            <?php else: ?>
                <?php while($bill = $bills->fetch_assoc()): 
                    // Lấy số điện thoại từ kết quả query (đã có sẵn trong $bill)
                    $sodienthoai = $bill['phone'] ?? '';
                    
                    // Tạo bill info để truyền vào QR
                    $bill_info = [
                        'month' => $bill['month'],
                        'year' => $bill['year'],
                        'phone' => $sodienthoai
                    ];
                    
                    $qr_url = getQRUrl($bank, $bill['amount'], $bill['code'], $bill['id'], $bill_info);
                    
                    // Thêm phone vào bill để dùng trong JS
                    $bill['phone'] = $sodienthoai;
                ?>
                <!-- Bill Card -->
                <div class="bill-card">
                    <div class="card-header">
                        <div class="household-info">
                            <h2><?=htmlspecialchars($bill['name'])?></h2>
                            <span class="household-code">
                                <i class="fas fa-hashtag"></i> <?=$bill['code']?>
                            </span>
                        </div>
                        <span class="status-badge <?=$bill['paid'] ? 'status-paid' : 'status-unpaid'?>">
                            <?=$bill['paid'] ? '✓ Đã thanh toán' : '⏳ Chưa thanh toán'?>
                        </span>
                    </div>
                    
                    <!-- Meter Readings -->
                    <div class="meter-readings">
                        <div class="reading-item">
                            <div class="reading-label">Chỉ số cũ</div>
                            <div class="reading-value"><?=number_format($bill['old_reading'])?></div>
                        </div>
                        <div class="reading-item">
                            <div class="reading-label">Chỉ số mới</div>
                            <div class="reading-value"><?=number_format($bill['new_reading'])?></div>
                        </div>
                        <div class="reading-item">
                            <div class="reading-label">Tiêu thụ</div>
                            <div class="reading-value"><?=number_format($bill['consumption'])?> <small>kWh</small></div>
                        </div>
                    </div>
                    
                    <!-- Amount -->
                    <div class="amount-section">
                        <span class="amount-label">Thành tiền</span>
                        <span class="amount-value"><?=number_format($bill['amount'])?>₫</span>
                    </div>
                    
                    <!-- Thông tin thanh toán chi tiết -->
                    <div style="background: #f8fafc; border-radius: 16px; padding: 15px; margin: 15px 0; text-align: left;">
                        <p style="margin: 5px 0;"><strong>👤 Tên người nhận:</strong> <?=$bank['chu_tk']?></p>
                        <p style="margin: 5px 0;"><strong>🏦 Số tài khoản:</strong> <?=$bank['so_tk']?> - <?=$bank['ten_ngan_hang']?></p>
                        <p style="margin: 5px 0;"><strong>💰 Số tiền:</strong> <?=number_format($bill['amount'])?>đ</p>
                        <p style="margin: 5px 0;"><strong>📝 Nội dung:</strong> CK thang<?=$bill['month']?> <?=$sodienthoai?></p>
                        <p style="margin: 5px 0;"><strong>📌 Mã hộ:</strong> <?=$bill['code']?></p>
                    </div>
                    
                    <!-- QR Code -->
                    <div class="qr-section">
                        <div class="qr-code">
                            <img src="<?=$qr_url?>" alt="QR Code" 
                                 onerror="this.src='<?=getBackupQRUrl($bill['amount'], $bill['code'], $bill_info)?>'">
                        </div>
                        <span class="qr-note">
                            <i class="fas fa-camera"></i> Quét mã để thanh toán
                        </span>
                        
                        <!-- QR Actions -->
                        <div class="qr-actions">
                            <a href="<?=$qr_url?>" download="QR_<?=$bill['code']?>.png" class="btn">
                                <i class="fas fa-download"></i> Tải QR
                            </a>
                            <button class="btn" onclick="copyQRUrl('<?=$qr_url?>')">
                                <i class="fas fa-link"></i> Copy link
                            </button>
                            <button class="btn" onclick="copyPaymentInfo(<?=json_encode([
                                'chu_tk' => $bank['chu_tk'],
                                'so_tk' => $bank['so_tk'],
                                'ten_nh' => $bank['ten_ngan_hang'],
                                'amount' => $bill['amount'],
                                'noidung' => 'CK thang' . $bill['month'] . ' ' . $sodienthoai,
                                'code' => $bill['code']
                            ])?>)">
                                <i class="fas fa-copy"></i> Copy thông tin
                            </button>
                        </div>
                    </div>
                    
                    <!-- Zalo Button -->
                    <button class="btn-zalo" onclick='sendZalo(<?=json_encode($bill)?>, "<?=$qr_url?>")'>
                        <i class="fab fa-zalo"></i>
                        Gửi Zalo ngay
                    </button>
                    
                    <?php if(!$bill['paid']): ?>
                    <a href="bills.php?action=paid&id=<?=$bill['id']?>" 
                       class="btn btn-secondary" 
                       style="margin-top: 12px; width: 100%;"
                       onclick="return confirm('Xác nhận đã thanh toán?')">
                        <i class="fas fa-check-circle"></i>
                        Đánh dấu đã thanh toán
                    </a>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        
        <!-- Tab Settings -->
        <div id="tab-settings" style="display: none;">
            <?php include 'settings.php'; ?>
        </div>
        
        <!-- Tab Households -->
        <div id="tab-households" style="display: none;">
            <?php include 'households.php'; ?>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span></span>
    </div>
    
    <!-- JavaScript -->
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            
            const tabs = {
                'bills': document.getElementById('tab-bills'),
                'settings': document.getElementById('tab-settings'),
                'households': document.getElementById('tab-households')
            };
            
            Object.values(tabs).forEach(tab => {
                if (tab) {
                    tab.style.opacity = '0';
                    tab.style.transition = 'opacity 0.3s';
                    setTimeout(() => { tab.style.opacity = '1'; }, 50);
                }
            });
            
            tabs.bills.style.display = tabName === 'bills' ? 'block' : 'none';
            tabs.settings.style.display = tabName === 'settings' ? 'block' : 'none';
            tabs.households.style.display = tabName === 'households' ? 'block' : 'none';
        }
        
        function changeMonth(direction, currentMonth, currentYear) {
            let month = parseInt(currentMonth);
            let year = parseInt(currentYear);
            
            month += direction;
            if (month < 1) { month = 12; year--; }
            if (month > 12) { month = 1; year++; }
            
            window.location.href = `index.php?thang=${month}&nam=${year}`;
        }
        
        function sendZalo(bill, qrUrl) {
            const message = `THÔNG BÁO TIỀN ĐIỆN\n` +
                `──────────────────\n` +
                `Kính gửi: ${bill.name}\n` +
                `──────────────────\n` +
                `Kỳ: Tháng ${bill.month}/${bill.year}\n` +
                `──────────────────\n` +
                `CHỈ SỐ ĐIỆN\n` +
                `• Cũ: ${Number(bill.old_reading).toLocaleString()} kWh\n` +
                `• Mới: ${Number(bill.new_reading).toLocaleString()} kWh\n` +
                `• Tiêu thụ: ${Number(bill.consumption).toLocaleString()} kWh\n` +
                `──────────────────\n` +
                `💰THANH TOÁN\n` +
                `• Tên người nhận: ${bankInfo.chu_tk}\n` +
                `• Số TK: ${bankInfo.so_tk} - ${bankInfo.ten_ngan_hang}\n` +
                `• Số tiền: ${Number(bill.amount).toLocaleString()} ₫\n` +
                `• Nội dung: CK thang${bill.month} ${bill.phone}\n` +
                `• QR: ${qrUrl}\n` +
                `──────────────────\n` +
                `Cảm ơn! Chúc bạn một ngày tốt lành.`;
            
            navigator.clipboard.writeText(message).then(() => {
                showToast('Đã copy nội dung!');
                setTimeout(() => {
                    window.open('https://zalo.me', '_blank');
                }, 1000);
            }).catch(() => {
                showToast('Không thể copy');
            });
        }
        
        function copyQRUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                showToast('Đã copy link QR!');
            });
        }
        
        function copyPaymentInfo(info) {
            const text = `👤 Tên người nhận: ${info.chu_tk}
🏦 Số tài khoản: ${info.so_tk} - ${info.ten_nh}
💰 Số tiền: ${Number(info.amount).toLocaleString()}đ
📝 Nội dung: ${info.noidung}
📌 Mã hộ: ${info.code}`;
            
            navigator.clipboard.writeText(text).then(() => {
                showToast('Đã copy thông tin thanh toán!');
            }).catch(() => {
                showToast('Không thể copy');
            });
        }
        
        function showToast(message) {
            const toast = document.getElementById('toast');
            const span = toast.querySelector('span');
            span.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
    
    <?php if(isset($_GET['msg'])): ?>
    <script>showToast('<?=$_GET['msg']?>');</script>
    <?php endif; ?>
</body>
</html>