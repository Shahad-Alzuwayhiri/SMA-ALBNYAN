<div class="contract-create-container">
    <div class="page-header">
        <h2>إنشاء عقد جديد</h2>
        <p>أدخل بيانات العقد الجديد</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <ul>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <form class="contract-form" method="POST" action="/contracts/create" id="contractForm">
        <!-- Contract Basic Info -->
        <div class="form-section">
            <h3 class="section-title">
                <span class="section-icon">📄</span>
                بيانات العقد الأساسية
            </h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="title">عنوان العقد</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        placeholder="أدخل عنوان العقد"
                        value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                        required
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="client_name">اسم العميل</label>
                    <input 
                        type="text" 
                        id="client_name" 
                        name="client_name" 
                        placeholder="أدخل اسم العميل"
                        value="<?= htmlspecialchars($_POST['client_name'] ?? '') ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="amount">قيمة العقد (ر.س)</label>
                    <input 
                        type="number" 
                        id="amount" 
                        name="amount" 
                        placeholder="0.00"
                        step="0.01"
                        min="0"
                        value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="description">وصف العقد</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="أدخل وصف مفصل للعقد"
                        rows="4"
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Contract Status -->
        <div class="form-section">
            <h3 class="section-title">
                <span class="section-icon">⚙️</span>
                حالة العقد
            </h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status">حالة العقد</label>
                    <select id="status" name="status">
                        <option value="draft" <?= ($_POST['status'] ?? '') === 'draft' ? 'selected' : '' ?>>مسودة</option>
                        <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>في الانتظار</option>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager'): ?>
                            <option value="approved" <?= ($_POST['status'] ?? '') === 'approved' ? 'selected' : '' ?>>معتمد</option>
                            <option value="completed" <?= ($_POST['status'] ?? '') === 'completed' ? 'selected' : '' ?>>مكتمل</option>
                            <option value="rejected" <?= ($_POST['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>مرفوض</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="history.back()">إلغاء</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <span class="btn-content">
                    <span class="btn-icon">💾</span>
                    <span class="btn-text">حفظ العقد</span>
                </span>
                <span class="btn-loading" style="display: none;">
                    <span class="loading-spinner"></span>
                    <span class="loading-text">جاري الحفظ...</span>
                </span>
            </button>
        </div>
    </form>
</div>

<style>
.contract-create-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h2 {
    color: var(--text-primary);
    margin-bottom: 8px;
}

.page-header p {
    color: var(--text-secondary);
}

.contract-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
}

.section-icon {
    font-size: 18px;
}

.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-group {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 14px;
}

.form-group input,
.form-group textarea,
.form-group select {
    padding: 12px 16px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--accent-color);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: var(--text-secondary);
}

.form-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-top: 20px;
}

.btn {
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.btn:active {
    transform: translateY(0);
}

.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.btn-content {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-loading {
    display: flex;
    align-items: center;
    gap: 10px;
}

.loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .contract-create-container {
        padding: 16px;
    }
    
    .form-section {
        padding: 16px;
    }
}
</style>

<script>
document.getElementById('contractForm').addEventListener('submit', function(e) {
    // Show loading state
    const btn = document.getElementById('submitBtn');
    const btnContent = btn.querySelector('.btn-content');
    const btnLoading = btn.querySelector('.btn-loading');
    
    btnContent.style.display = 'none';
    btnLoading.style.display = 'inline-flex';
    btn.disabled = true;
    
    // Reset button state after timeout if no redirect occurs
    setTimeout(() => {
        btnContent.style.display = 'inline-flex';
        btnLoading.style.display = 'none';
        btn.disabled = false;
    }, 5000);
});

// Add interactive effects
document.querySelectorAll('.form-group input, .form-group textarea, .form-group select').forEach(input => {
    input.addEventListener('focus', function() {
        this.style.transform = 'scale(1.02)';
        this.style.boxShadow = '0 8px 32px rgba(255, 255, 255, 0.3)';
    });
    
    input.addEventListener('blur', function() {
        this.style.transform = '';
        this.style.boxShadow = '';
    });
});
</script>