<?php
require_once 'config.php';
$conn = getDB();

// Xử lý lưu cài đặt
if(isset($_POST['luu'])) {
    $success = true;
    $error_msg = '';
    
    if(isset($_POST['gia_dien'])) {
        $gia = (int)$_POST['gia_dien'];
        if(!$conn->query("UPDATE settings SET setting_value = '$gia' WHERE setting_key = 'gia_dien'")) {
            $success = false;
            $error_msg .= 'Lỗi cập nhật giá điện. ';
        }
    }
    
    if(isset($_POST['so_tk'])) {
        $so_tk = $conn->real_escape_string($_POST['so_tk']);
        if(!$conn->query("UPDATE settings SET setting_value = '$so_tk' WHERE setting_key = 'so_tk'")) {
            $success = false;
            $error_msg .= 'Lỗi cập nhật số tài khoản. ';
        }
    }
    
    if(isset($_POST['ma_ngan_hang'])) {
        $ma_nh = $conn->real_escape_string($_POST['ma_ngan_hang']);
        if(!$conn->query("UPDATE settings SET setting_value = '$ma_nh' WHERE setting_key = 'ma_ngan_hang'")) {
            $success = false;
            $error_msg .= 'Lỗi cập nhật mã ngân hàng. ';
        }
    }
    
    if(isset($_POST['ten_ngan_hang'])) {
        $ten_nh = $conn->real_escape_string($_POST['ten_ngan_hang']);
        if(!$conn->query("UPDATE settings SET setting_value = '$ten_nh' WHERE setting_key = 'ten_ngan_hang'")) {
            $success = false;
            $error_msg .= 'Lỗi cập nhật tên ngân hàng. ';
        }
    }
    
    if(isset($_POST['chu_tk'])) {
        $chu_tk = $conn->real_escape_string($_POST['chu_tk']);
        if(!$conn->query("UPDATE settings SET setting_value = '$chu_tk' WHERE setting_key = 'chu_tk'")) {
            $success = false;
            $error_msg .= 'Lỗi cập nhật chủ tài khoản. ';
        }
    }
    
    if($success) {
        header("Location: index.php?tab=settings&msg=Đã lưu cài đặt thành công");
    } else {
        header("Location: index.php?tab=settings&msg=Lỗi: " . urlencode($error_msg));
    }
    exit;
}

$gia = getDefaultPrice($conn);
$bank = getBankInfo($conn);
?>

<!-- CSS đồng bộ với index.php và households.php -->
<style>
    /* Variables - đồng bộ với các file khác */
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

    /* Settings Container */
    .settings-container {
        animation: fadeIn 0.5s ease;
        padding: 4px 0;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
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

    /* Settings Card - đồng bộ với bill-card */
    .settings-card {
        background: white;
        border-radius: 32px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-lg);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid var(--border);
        animation: slideIn 0.5s ease-out;
        position: relative;
        overflow: hidden;
    }

    .settings-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: var(--shadow-xl);
        border-color: var(--primary-light);
    }

    .settings-card::before {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 150px;
        height: 150px;
        background: linear-gradient(135deg, #2563eb08, #1e40af08);
        border-radius: 50%;
        transition: all 0.5s;
        z-index: 0;
    }

    .settings-card:hover::before {
        transform: scale(1.5);
        background: linear-gradient(135deg, #2563eb15, #1e40af15);
    }

    /* Card Header - đồng bộ với card-header */
    .settings-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        position: relative;
        z-index: 1;
    }

    .settings-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 26px;
        box-shadow: 0 12px 25px -8px var(--primary);
        transition: all 0.3s;
    }

    .settings-card:hover .settings-icon {
        transform: rotate(5deg) scale(1.1);
    }

    .settings-header h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 4px;
        letter-spacing: -0.3px;
    }

    .settings-header p {
        font-size: 13px;
        color: var(--gray);
    }

    .settings-header p i {
        color: var(--primary);
        margin-right: 4px;
    }

    /* Settings Item - đồng bộ với household-item */
    .settings-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 20px;
        background: var(--light-gray);
        border-radius: 22px;
        margin-bottom: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
        position: relative;
        z-index: 1;
        animation: slideIn 0.3s ease;
    }

    .settings-item:last-child {
        margin-bottom: 0;
    }

    .settings-item:hover {
        background: white;
        border-color: var(--primary);
        box-shadow: 0 15px 30px -12px var(--primary);
        transform: translateX(5px);
    }

    .settings-item::before {
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

    .settings-item:hover::before {
        opacity: 1;
    }

    .settings-info {
        flex: 1;
    }

    .settings-info strong {
        display: block;
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 4px;
    }

    .settings-info span {
        font-size: 13px;
        color: var(--gray);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .settings-info span i {
        color: var(--primary);
        font-size: 10px;
    }

    .settings-value {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary);
        background: white;
        padding: 8px 20px;
        border-radius: 40px;
        box-shadow: var(--shadow-sm);
        margin: 0 15px;
        min-width: 120px;
        text-align: center;
        border: 1px solid var(--border);
    }

    /* Button Edit - đồng bộ với action-btn.edit */
    .btn-edit {
        background: #eef2ff;
        border: 2px solid transparent;
        color: var(--primary);
        padding: 10px 20px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
    }

    .btn-edit::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        transform: translate(-50%, -50%);
        transition: width 0.4s, height 0.4s;
    }

    .btn-edit:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 8px 16px -4px var(--primary);
    }

    .btn-edit:hover::before {
        width: 80px;
        height: 80px;
    }

    .btn-edit i {
        font-size: 14px;
    }

    /* Bank Grid */
    .bank-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin: 20px 0;
        position: relative;
        z-index: 1;
    }

    .bank-card {
        background: linear-gradient(145deg, #f8fafc, #ffffff);
        padding: 20px 15px;
        border-radius: 24px;
        text-align: center;
        border: 1px solid var(--border);
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
        animation: fadeIn 0.5s ease;
    }

    .bank-card:nth-child(1) { animation-delay: 0.1s; }
    .bank-card:nth-child(2) { animation-delay: 0.2s; }
    .bank-card:nth-child(3) { animation-delay: 0.3s; }
    .bank-card:nth-child(4) { animation-delay: 0.4s; }

    .bank-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        opacity: 0;
        transition: opacity 0.3s;
    }

    .bank-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary);
        box-shadow: 0 20px 30px -12px var(--primary);
    }

    .bank-card:hover::after {
        opacity: 1;
    }

    .bank-label {
        font-size: 12px;
        color: var(--gray);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .bank-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
        word-break: break-word;
        background: white;
        padding: 10px;
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
    }

    .bank-value.highlight {
        color: var(--primary);
        font-size: 18px;
    }

    /* Update Button - đồng bộ với btn-primary */
    .btn-update {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
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
        box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
        margin-top: 20px;
        position: relative;
        z-index: 1;
        overflow: hidden;
    }

    .btn-update::before {
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

    .btn-update:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.5);
    }

    .btn-update:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-update i {
        font-size: 18px;
    }

    /* Price Badge - đồng bộ với amount-section */
    .price-badge {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        color: var(--danger);
        padding: 16px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 20px;
        font-weight: 600;
        font-size: 15px;
        position: relative;
        z-index: 1;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .price-badge i {
        font-size: 18px;
        color: var(--danger);
    }

    /* Quick Actions - đồng bộ với qr-actions */
    .quick-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .quick-btn {
        flex: 1;
        min-width: 100px;
        padding: 14px;
        background: white;
        border: 2px solid var(--border);
        border-radius: 40px;
        color: var(--dark);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .quick-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .quick-btn i {
        font-size: 14px;
    }

    /* Modal Styles - đồng bộ với modal-premium */
    .modal-premium {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        align-items: center;
        justify-content: center;
        z-index: 9999;
        animation: modalFadeIn 0.3s;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-premium.active {
        display: flex;
    }

    .modal-content-premium {
        background: white;
        border-radius: 32px;
        padding: 28px;
        max-width: 420px;
        width: 90%;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: var(--shadow-xl);
        animation: modalSlideUp 0.4s;
        border: 1px solid rgba(255,255,255,0.3);
    }

    @keyframes modalSlideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header-premium {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid var(--border);
    }

    .modal-header-premium h3 {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 20px;
        font-weight: 700;
        color: var(--dark);
    }

    .modal-header-premium h3 i {
        color: var(--primary);
        background: #eef2ff;
        padding: 8px;
        border-radius: 14px;
        font-size: 18px;
    }

    .modal-close-premium {
        width: 42px;
        height: 42px;
        border-radius: 16px;
        border: 2px solid var(--border);
        background: white;
        color: var(--gray);
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close-premium:hover {
        background: var(--danger);
        color: white;
        border-color: var(--danger);
        transform: rotate(180deg);
    }

    /* Form Styles - đồng bộ với form-group-premium */
    .form-group-premium {
        margin-bottom: 20px;
    }

    .form-group-premium label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .form-group-premium label i {
        color: var(--primary);
        margin-right: 8px;
    }

    .form-control-premium {
        width: 100%;
        padding: 16px 18px;
        border: 2px solid var(--border);
        border-radius: 24px;
        font-size: 15px;
        transition: all 0.3s;
        background: var(--light-gray);
    }

    .form-control-premium:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        background: white;
        transform: translateY(-2px);
    }

    .form-hint {
        font-size: 12px;
        color: var(--gray);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-hint i {
        color: var(--primary);
        font-size: 12px;
    }

    /* Button Save - đồng bộ với btn-save */
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
        margin-top: 10px;
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

    .btn-save i {
        font-size: 18px;
    }

    /* Toast Notification - đồng bộ với toast */
    .toast-settings {
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
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .toast-settings.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .toast-settings i {
        font-size: 18px;
    }

    .toast-settings.success i {
        color: var(--success);
    }

    .toast-settings.error i {
        color: var(--danger);
    }

    /* Responsive */
    @media (max-width: 480px) {
        .settings-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .settings-value {
            width: 100%;
            margin: 0;
        }
        
        .btn-edit {
            width: 100%;
            justify-content: center;
        }
        
        .bank-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            flex-direction: column;
        }
        
        .quick-btn {
            width: 100%;
        }
    }
</style>

<div class="settings-container">
    <!-- Price Settings Card -->
    <div class="settings-card">
        <div class="settings-header">
            <div class="settings-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <div>
                <h3>Giá điện</h3>
                <p><i class="fas fa-calculator"></i> Cấu hình giá điện cho hóa đơn</p>
            </div>
        </div>
        
        <div class="settings-item">
            <div class="settings-info">
                <strong>Giá điện hiện tại</strong>
                <span><i class="fas fa-circle"></i> Áp dụng từ tháng <?=date('m/Y')?></span>
            </div>
            <div class="settings-value"><?=number_format($gia)?><small style="font-size: 12px;">đ/kWh</small></div>
            <button class="btn-edit" onclick="openModal('priceModal')">
                <i class="fas fa-edit"></i> Sửa
            </button>
        </div>
        
        <div class="price-badge">
            <i class="fas fa-calculator"></i>
            <span>Tổng tiền = Số kWh × <?=number_format($gia)?>đ</span>
        </div>
    </div>
    
    <!-- Bank Settings Card -->
    <div class="settings-card">
        <div class="settings-header">
            <div class="settings-icon">
                <i class="fas fa-university"></i>
            </div>
            <div>
                <h3>Thông tin ngân hàng</h3>
                <p><i class="fas fa-qrcode"></i> Dùng để tạo mã QR thanh toán</p>
            </div>
        </div>
        
        <!-- Bank Grid -->
        <div class="bank-grid">
            <div class="bank-card">
                <div class="bank-label">Số tài khoản</div>
                <div class="bank-value highlight"><?=htmlspecialchars($bank['so_tk'] ?: 'Chưa cập nhật')?></div>
            </div>
            <div class="bank-card">
                <div class="bank-label">Chủ tài khoản</div>
                <div class="bank-value"><?=htmlspecialchars($bank['chu_tk'] ?: 'Chưa cập nhật')?></div>
            </div>
            <div class="bank-card">
                <div class="bank-label">Mã ngân hàng</div>
                <div class="bank-value"><?=htmlspecialchars($bank['ma_ngan_hang'] ?: 'Chưa cập nhật')?></div>
            </div>
            <div class="bank-card">
                <div class="bank-label">Tên ngân hàng</div>
                <div class="bank-value"><?=htmlspecialchars($bank['ten_ngan_hang'] ?: 'Chưa cập nhật')?></div>
            </div>
        </div>
        
        <!-- Update Button -->
        <button class="btn-update" onclick="openModal('bankModal')">
            <i class="fas fa-edit"></i>
            Cập nhật thông tin ngân hàng
        </button>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="quick-btn" onclick="fillSampleBank()">
                <i class="fas fa-magic"></i> Mẫu
            </button>
            <button class="quick-btn" onclick="copyBankInfo()">
                <i class="fas fa-copy"></i> Copy
            </button>
            <button class="quick-btn" onclick="testBankInfo()">
                <i class="fas fa-check-circle"></i> Kiểm tra
            </button>
        </div>
    </div>
</div>

<!-- Price Modal -->
<div id="priceModal" class="modal-premium">
    <div class="modal-content-premium">
        <div class="modal-header-premium">
            <h3>
                <i class="fas fa-pen"></i>
                Sửa giá điện
            </h3>
            <button class="modal-close-premium" onclick="closeModal('priceModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-dollar-sign"></i>
                    Giá điện (VNĐ/kWh)
                </label>
                <input type="number" name="gia_dien" class="form-control-premium" 
                       value="<?=$gia?>" required min="0" step="100" 
                       placeholder="Nhập giá điện">
                <div class="form-hint">
                    <i class="fas fa-info-circle"></i>
                    Giá hiện tại: <?=number_format($gia)?>đ/kWh
                </div>
            </div>
            <button type="submit" name="luu" class="btn-save">
                <i class="fas fa-save"></i>
                Lưu thay đổi
            </button>
        </form>
    </div>
</div>

<!-- Bank Modal -->
<div id="bankModal" class="modal-premium">
    <div class="modal-content-premium">
        <div class="modal-header-premium">
            <h3>
                <i class="fas fa-pen"></i>
                Sửa thông tin ngân hàng
            </h3>
            <button class="modal-close-premium" onclick="closeModal('bankModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-credit-card"></i>
                    Số tài khoản
                </label>
                <input type="text" name="so_tk" class="form-control-premium" 
                       value="<?=htmlspecialchars($bank['so_tk'])?>" required 
                       placeholder="VD: 123456789">
            </div>
            
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-qrcode"></i>
                    Mã ngân hàng (BIN)
                </label>
                <input type="text" name="ma_ngan_hang" class="form-control-premium" 
                       value="<?=htmlspecialchars($bank['ma_ngan_hang'])?>" required 
                       placeholder="VD: 970415">
                <div class="form-hint">
                    <i class="fas fa-info-circle"></i>
                    VietinBank: 970415 | Vietcombank: 970436 | BIDV: 970418 | MB: 970432
                </div>
            </div>
            
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-building"></i>
                    Tên ngân hàng
                </label>
                <input type="text" name="ten_ngan_hang" class="form-control-premium" 
                       value="<?=htmlspecialchars($bank['ten_ngan_hang'])?>" required 
                       placeholder="VD: VietinBank">
            </div>
            
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-user"></i>
                    Tên chủ tài khoản
                </label>
                <input type="text" name="chu_tk" class="form-control-premium" 
                       value="<?=htmlspecialchars($bank['chu_tk'])?>" required 
                       placeholder="VD: NGUYEN VAN A">
            </div>
            
            <button type="submit" name="luu" class="btn-save">
                <i class="fas fa-save"></i>
                Lưu thông tin
            </button>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="settingsToast" class="toast-settings">
    <i class="fas fa-check-circle"></i>
    <span></span>
</div>

<script>
// Biến toàn cục
const bankInfo = {
    so_tk: '<?=$bank['so_tk']?>',
    ten_ngan_hang: '<?=$bank['ten_ngan_hang']?>',
    chu_tk: '<?=$bank['chu_tk']?>',
    ma_ngan_hang: '<?=$bank['ma_ngan_hang']?>'
};

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

// Fill sample bank info
function fillSampleBank() {
    if(confirm('Điền thông tin ngân hàng mẫu?')) {
        // Cập nhật giá trị trong modal
        const modal = document.getElementById('bankModal');
        modal.querySelector('[name="so_tk"]').value = '4802205120449';
        modal.querySelector('[name="ma_ngan_hang"]').value = '970403';
        modal.querySelector('[name="ten_ngan_hang"]').value = 'Agribank';
        modal.querySelector('[name="chu_tk"]').value = 'VO VAN MINH';
        showToast('✅ Đã điền thông tin mẫu', 'success');
    }
}

// Copy bank info
function copyBankInfo() {
    const text = `Số TK: <?=$bank['so_tk']?>\nNgân hàng: <?=$bank['ten_ngan_hang']?>\nChủ TK: <?=$bank['chu_tk']?>`;
    navigator.clipboard.writeText(text).then(() => {
        showToast('✅ Đã copy thông tin ngân hàng', 'success');
    }).catch(() => {
        showToast('❌ Không thể copy', 'error');
    });
}

// Test bank info
function testBankInfo() {
    <?php if(empty($bank['so_tk']) || empty($bank['ma_ngan_hang'])): ?>
        showToast('❌ Thông tin ngân hàng chưa đầy đủ', 'error');
    <?php else: ?>
        showToast('✅ Thông tin ngân hàng hợp lệ', 'success');
    <?php endif; ?>
}

// Show toast message
function showToast(message, type = 'success') {
    const toast = document.getElementById('settingsToast');
    const icon = toast.querySelector('i');
    const span = toast.querySelector('span');
    
    icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    icon.style.color = type === 'success' ? '#10b981' : '#ef4444';
    span.textContent = message;
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    if(event.target.classList.contains('modal-premium')) {
        event.target.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Animation on load
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.settings-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>