<?php
require_once 'config.php';
$conn = getDB();

// Xử lý thêm hộ
if(isset($_POST['them'])) {
    $code = strtoupper($conn->real_escape_string($_POST['code']));
    $name = $conn->real_escape_string($_POST['name']);
    $price = !empty($_POST['price']) ? (int)$_POST['price'] : 'NULL';
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    
    // Kiểm tra mã đã tồn tại chưa
    $check = $conn->query("SELECT id FROM households WHERE code = '$code'");
    if($check->num_rows == 0) {
        $conn->query("INSERT INTO households (code, name, price, phone, email) VALUES ('$code', '$name', $price, '$phone', '$email')");
        $msg = "Đã thêm hộ thành công";
    } else {
        $msg = "Mã hộ đã tồn tại";
    }
    header("Location: index.php?tab=households&msg=$msg");
    exit;
}

// Xử lý sửa hộ
if(isset($_POST['sua'])) {
    $id = (int)$_POST['id'];
    $code = strtoupper($conn->real_escape_string($_POST['code']));
    $name = $conn->real_escape_string($_POST['name']);
    $price = !empty($_POST['price']) ? (int)$_POST['price'] : 'NULL';
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    
    $conn->query("UPDATE households SET code = '$code', name = '$name', price = $price, phone = '$phone', email = '$email' WHERE id = $id");
    header("Location: index.php?tab=households&msg=Đã cập nhật hộ thành công");
    exit;
}

// Xử lý xóa hộ
if(isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    
    // Kiểm tra có hóa đơn không
    $check = $conn->query("SELECT id FROM bills WHERE household_id = $id LIMIT 1");
    if($check->num_rows > 0) {
        header("Location: index.php?tab=households&msg=Không thể xóa hộ đã có hóa đơn");
    } else {
        $conn->query("DELETE FROM households WHERE id = $id");
        header("Location: index.php?tab=households&msg=Đã xóa hộ thành công");
    }
    exit;
}

// Lấy danh sách hộ
$households = getHouseholds($conn);
$gia_chung = getDefaultPrice($conn);
?>

<style>
    /* Thêm style cho price badge */
    .price-badge-custom {
        background: #fef3c7;
        color: #d97706;
        padding: 4px 12px;
        border-radius: 40px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 8px;
    }

    .price-badge-default {
        background: #eef2ff;
        color: #2563eb;
        padding: 4px 12px;
        border-radius: 40px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 8px;
    }

    .household-price-info {
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .household-price-info i {
        color: #f59e0b;
    }

    .input-hint {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .input-hint i {
        color: #2563eb;
    }

    /* Form Grid mở rộng */
    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }

    .contact-info {
        display: flex;
        gap: 10px;
        margin-top: 8px;
        font-size: 12px;
        color: #64748b;
    }

    .contact-info i {
        color: #2563eb;
        width: 16px;
    }

    /* Giữ nguyên tất cả style hiện tại của bạn */
    .households-container {
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .add-card {
        background: linear-gradient(145deg, #ffffff, #fafcff);
        border-radius: 28px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(226, 232, 240, 0.6);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .add-card:hover {
        box-shadow: 0 25px 45px -12px rgba(37, 99, 235, 0.25);
        border-color: #2563eb;
    }

    .add-card::before {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 150px;
        height: 150px;
        background: linear-gradient(135deg, #2563eb08, #1e40af08);
        border-radius: 50%;
        transition: all 0.5s;
    }

    .add-card:hover::before {
        transform: scale(1.5);
        background: linear-gradient(135deg, #2563eb15, #1e40af15);
    }

    .add-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        position: relative;
        z-index: 1;
    }

    .add-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 26px;
        box-shadow: 0 12px 25px -8px #2563eb;
        transition: all 0.3s;
    }

    .add-card:hover .add-icon {
        transform: rotate(90deg) scale(1.1);
    }

    .add-header h3 {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .add-header p {
        font-size: 13px;
        color: #64748b;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 16px;
        position: relative;
        z-index: 1;
    }

    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #2563eb;
        font-size: 15px;
        z-index: 2;
        transition: all 0.2s;
    }

    .input-field {
        width: 100%;
        padding: 16px 16px 16px 48px;
        border: 2px solid #e2e8f0;
        border-radius: 24px;
        font-size: 15px;
        transition: all 0.3s;
        background: white;
        color: #0f172a;
    }

    .input-field:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        transform: translateY(-2px);
    }

    .input-field::placeholder {
        color: #94a3b8;
        font-size: 14px;
    }

    .btn-add {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #10b981, #059669);
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
        box-shadow: 0 15px 30px -8px #10b981b3;
        position: relative;
        z-index: 1;
        overflow: hidden;
    }

    .btn-add::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn-add:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 40px -10px #10b981;
    }

    .btn-add:hover::before {
        left: 100%;
    }

    .btn-add i {
        font-size: 18px;
        transition: transform 0.3s;
    }

    .btn-add:hover i {
        transform: rotate(90deg);
    }

    .list-card {
        background: white;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(226, 232, 240, 0.6);
    }

    .list-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px dashed #e2e8f0;
    }

    .list-header h3 {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }

    .list-header h3 i {
        color: #2563eb;
        background: #eef2ff;
        padding: 8px;
        border-radius: 14px;
        font-size: 16px;
    }

    .total-badge {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        padding: 8px 20px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 8px 16px -4px #2563eb80;
    }

    .households-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .household-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: #f8fafc;
        border-radius: 22px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
        animation: slideIn 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .household-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .household-item:hover {
        background: white;
        border-color: #2563eb;
        box-shadow: 0 15px 30px -12px #2563eb80;
        transform: translateX(8px) scale(1.02);
    }

    .household-item:hover::before {
        opacity: 1;
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

    .household-info-wrapper {
        display: flex;
        align-items: center;
        gap: 15px;
        flex: 1;
    }

    .household-avatar {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, #2563eb15, #1e40af15);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        font-size: 22px;
        transition: all 0.3s;
    }

    .household-item:hover .household-avatar {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        transform: rotate(10deg) scale(1.1);
    }

    .household-details {
        flex: 1;
    }

    .household-name-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }

    .household-name {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        word-break: break-word;
    }

    .household-code {
        background: white;
        padding: 4px 12px;
        border-radius: 40px;
        font-size: 11px;
        font-weight: 600;
        color: #2563eb;
        border: 1px solid #2563eb20;
        box-shadow: 0 2px 5px #2563eb10;
    }

    .household-price-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 40px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }

    .household-price-badge.special {
        background: #fef3c7;
        color: #d97706;
    }

    .household-price-badge.default {
        background: #eef2ff;
        color: #2563eb;
    }

    .household-contact {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
    }

    .household-contact span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .household-contact i {
        color: #2563eb;
        font-size: 10px;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .action-btn {
        width: 42px;
        height: 42px;
        border-radius: 16px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 16px;
        position: relative;
        overflow: hidden;
    }

    .action-btn.edit {
        background: #eef2ff;
        color: #2563eb;
    }

    .action-btn.edit:hover {
        background: #2563eb;
        color: white;
        transform: translateY(-3px) rotate(5deg);
        box-shadow: 0 8px 16px -4px #2563eb;
    }

    .action-btn.delete {
        background: #fee2e2;
        color: #dc2626;
    }

    .action-btn.delete:hover {
        background: #dc2626;
        color: white;
        transform: translateY(-3px) rotate(-5deg);
        box-shadow: 0 8px 16px -4px #dc2626;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background: #f8fafc;
        border-radius: 28px;
        margin-top: 20px;
    }

    .empty-icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 20px;
        background: linear-gradient(135deg, #2563eb10, #1e40af10);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: #2563eb;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.7; }
        50% { transform: scale(1.1); opacity: 1; }
    }

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
        box-shadow: 0 40px 70px -15px rgba(0, 0, 0, 0.3);
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
        border-bottom: 2px solid #f1f5f9;
    }

    .modal-header-premium h3 {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
    }

    .modal-header-premium h3 i {
        color: #2563eb;
        background: #eef2ff;
        padding: 8px;
        border-radius: 14px;
        font-size: 18px;
    }

    .modal-close-premium {
        width: 42px;
        height: 42px;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        background: white;
        color: #64748b;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close-premium:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
        transform: rotate(180deg);
    }

    .form-group-premium {
        margin-bottom: 20px;
    }

    .form-group-premium label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .form-group-premium label i {
        color: #2563eb;
        margin-right: 8px;
    }

    .form-control-premium {
        width: 100%;
        padding: 16px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 24px;
        font-size: 15px;
        transition: all 0.3s;
        background: #fafcfc;
    }

    .form-control-premium:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        background: white;
        transform: translateY(-2px);
    }

    .btn-save {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #2563eb, #1e40af);
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
        box-shadow: 0 15px 30px -8px #2563eb;
        margin-top: 10px;
    }

    .btn-save:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 40px -10px #2563eb;
    }

    .btn-save i {
        font-size: 18px;
    }

    @media (max-width: 480px) {
        .form-grid, .form-grid-3 {
            grid-template-columns: 1fr;
        }
        
        .household-item {
            padding: 16px;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .action-buttons {
            align-self: flex-end;
        }
        
        .household-info-wrapper {
            width: 100%;
        }
        
        .household-contact {
            flex-wrap: wrap;
        }
    }
</style>

<div class="households-container">
    <!-- Add New Household Card -->
    <div class="add-card">
        <div class="add-header">
            <div class="add-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div>
                <h3>Thêm hộ mới</h3>
                <p><i class="fas fa-home"></i> Điền thông tin để thêm hộ gia đình</p>
            </div>
        </div>
        
        <form method="POST">
            <div class="form-grid">
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-tag"></i></span>
                    <input type="text" name="code" class="input-field" 
                           placeholder="Mã hộ (VD: HD07)" required 
                           pattern="[A-Za-z0-9]+" title="Chỉ chấp nhận chữ và số"
                           maxlength="10">
                </div>
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" name="name" class="input-field" 
                           placeholder="Tên hộ (VD: Nguyễn Văn A)" required>
                </div>
            </div>
            
            <div class="form-grid-3">
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-dollar-sign"></i></span>
                    <input type="number" name="price" class="input-field" 
                           placeholder="Giá riêng (đ/kWh)" 
                           title="Để trống nếu dùng giá chung">
                </div>
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-phone"></i></span>
                    <input type="text" name="phone" class="input-field" 
                           placeholder="Số điện thoại">
                </div>
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="input-field" 
                           placeholder="Email">
                </div>
            </div>
            
            <div class="input-hint">
                <i class="fas fa-info-circle"></i>
                Giá chung hiện tại: <?=number_format($gia_chung)?>đ/kWh. Nếu để trống giá riêng sẽ dùng giá chung.
            </div>
            
            <button type="submit" name="them" class="btn-add" style="margin-top: 16px;">
                <i class="fas fa-plus-circle"></i>
                Thêm hộ mới
            </button>
        </form>
    </div>
    
    <!-- Households List Card -->
    <div class="list-card">
        <div class="list-header">
            <h3>
                <i class="fas fa-users"></i>
                Danh sách hộ
            </h3>
            <div class="total-badge">
                <i class="fas fa-home"></i>
                <?=$households->num_rows?> hộ
            </div>
        </div>
        
        <?php if($households->num_rows == 0): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-users-slash"></i>
                </div>
                <h4>Chưa có hộ nào</h4>
                <p>Bắt đầu bằng cách thêm hộ đầu tiên</p>
                <button class="btn-add" style="width: auto; padding: 14px 30px; display: inline-flex;" 
                        onclick="document.querySelector('[name=code]').focus()">
                    <i class="fas fa-plus"></i> Thêm hộ
                </button>
            </div>
        <?php else: ?>
            <div class="households-grid">
                <?php 
                $households->data_seek(0);
                while($ho = $households->fetch_assoc()): 
                    $gia_ho = $ho['price'] ? number_format($ho['price']) . 'đ' : 'Dùng giá chung';
                    $gia_class = $ho['price'] ? 'special' : 'default';
                ?>
                <div class="household-item" data-id="<?=$ho['id']?>" 
                     data-code="<?=$ho['code']?>" 
                     data-name="<?=htmlspecialchars($ho['name'])?>"
                     data-price="<?=$ho['price']?>"
                     data-phone="<?=$ho['phone']?>"
                     data-email="<?=$ho['email']?>">
                    <div class="household-info-wrapper">
                        <div class="household-avatar">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="household-details">
                            <div class="household-name-row">
                                <span class="household-name"><?=htmlspecialchars($ho['name'])?></span>
                                <span class="household-code"><?=$ho['code']?></span>
                                <span class="household-price-badge <?=$gia_class?>">
                                    <i class="fas fa-tag"></i>
                                    <?=$gia_ho?>
                                </span>
                            </div>
                            
                            <div class="household-contact">
                                <?php if(!empty($ho['phone'])): ?>
                                <span><i class="fas fa-phone"></i> <?=$ho['phone']?></span>
                                <?php endif; ?>
                                <?php if(!empty($ho['email'])): ?>
                                <span><i class="fas fa-envelope"></i> <?=$ho['email']?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="household-meta">
                                <span>
                                    <i class="fas fa-calendar-alt"></i>
                                    ID: <?=$ho['id']?>
                                </span>
                                <span>
                                    <i class="fas fa-bolt"></i>
                                    <?php
                                    $bill_count = $conn->query("SELECT COUNT(*) as count FROM bills WHERE household_id = {$ho['id']}")->fetch_assoc()['count'];
                                    echo $bill_count . ' hóa đơn';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="action-btn edit" onclick='editHousehold(<?=$ho['id']?>, "<?=$ho['code']?>", "<?=htmlspecialchars($ho['name'])?>", <?=$ho['price'] ?: 'null'?>, "<?=$ho['phone']?>", "<?=$ho['email']?>")' 
                                title="Sửa thông tin">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick='confirmDelete(<?=$ho['id']?>, "<?=htmlspecialchars($ho['name'])?>")' 
                                title="Xóa hộ">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editHouseholdModal" class="modal-premium">
    <div class="modal-content-premium">
        <div class="modal-header-premium">
            <h3>
                <i class="fas fa-edit"></i>
                Sửa thông tin hộ
            </h3>
            <button class="modal-close-premium" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="households.php">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-tag"></i>
                    Mã hộ
                </label>
                <input type="text" name="code" id="edit_code" class="form-control-premium" 
                       required pattern="[A-Za-z0-9]+" 
                       title="Chỉ chấp nhận chữ và số" maxlength="10">
            </div>
            
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-user"></i>
                    Tên hộ
                </label>
                <input type="text" name="name" id="edit_name" class="form-control-premium" required>
            </div>
            
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-dollar-sign"></i>
                    Giá điện riêng (đ/kWh)
                </label>
                <input type="number" name="price" id="edit_price" class="form-control-premium" 
                       placeholder="Để trống nếu dùng giá chung">
                <div class="input-hint">
                    <i class="fas fa-info-circle"></i>
                    Giá chung hiện tại: <?=number_format($gia_chung)?>đ/kWh
                </div>
            </div>
            
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-phone"></i>
                    Số điện thoại
                </label>
                <input type="text" name="phone" id="edit_phone" class="form-control-premium" 
                       placeholder="Nhập số điện thoại (không bắt buộc)">
            </div>
            
            <div class="form-group-premium">
                <label>
                    <i class="fas fa-envelope"></i>
                    Email
                </label>
                <input type="email" name="email" id="edit_email" class="form-control-premium" 
                       placeholder="Nhập email (không bắt buộc)">
            </div>
            
            <button type="submit" name="sua" class="btn-save">
                <i class="fas fa-save"></i>
                Lưu thay đổi
            </button>
        </form>
    </div>
</div>

<script>
function editHousehold(id, code, name, price = null, phone = '', email = '') {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_price').value = price || '';
    document.getElementById('edit_phone').value = phone || '';
    document.getElementById('edit_email').value = email || '';
    openModal();
}

function confirmDelete(id, name) {
    if(confirm(`🗑️ Xóa hộ "${name}"?\nHành động này không thể hoàn tác!`)) {
        window.location.href = `households.php?xoa=${id}`;
    }
}

function openModal() {
    document.getElementById('editHouseholdModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('editHouseholdModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editHouseholdModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Animation cho household items
document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.household-item');
    items.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.05}s`;
    });
});
</script>