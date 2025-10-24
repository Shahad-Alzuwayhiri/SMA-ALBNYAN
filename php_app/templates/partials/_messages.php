<?php
/**
 * Flash Messages Component
 * عرض رسائل النجاح والأخطاء والتحذيرات
 */
?>

<style>
.alert {
    padding: 1rem 1.5rem;
    margin: 1rem 0;
    border-radius: 12px;
    border: 1px solid;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideInDown 0.5s ease;
    transition: all 0.3s ease;
}

.alert-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
    border-color: rgba(16, 185, 129, 0.3);
    color: #065f46;
    backdrop-filter: blur(10px);
}

.alert-error,
.alert-danger {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
    border-color: rgba(239, 68, 68, 0.3);
    color: #7f1d1d;
    backdrop-filter: blur(10px);
}

.alert-warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
    border-color: rgba(245, 158, 11, 0.3);
    color: #78350f;
    backdrop-filter: blur(10px);
}

.alert-info {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05));
    border-color: rgba(59, 130, 246, 0.3);
    color: #1e3a8a;
    backdrop-filter: blur(10px);
}

.alert-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.alert-content {
    flex: 1;
}

.alert-content ul {
    margin: 0.5rem 0 0 0;
    padding-right: 1rem;
}

.alert-content li {
    margin: 0.25rem 0;
}

.alert-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
}

.alert-close:hover {
    opacity: 1;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <span class="alert-icon">✅</span>
        <div class="alert-content">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <span class="alert-icon">❌</span>
        <div class="alert-content">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['warning'])): ?>
    <div class="alert alert-warning">
        <span class="alert-icon">⚠️</span>
        <div class="alert-content">
            <?= htmlspecialchars($_SESSION['warning']) ?>
        </div>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php unset($_SESSION['warning']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['info'])): ?>
    <div class="alert alert-info">
        <span class="alert-icon">ℹ️</span>
        <div class="alert-content">
            <?= htmlspecialchars($_SESSION['info']) ?>
        </div>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php unset($_SESSION['info']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])): ?>
    <div class="alert alert-error">
        <span class="alert-icon">❌</span>
        <div class="alert-content">
            <strong>الرجاء تصحيح الأخطاء التالية:</strong>
            <ul>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php unset($_SESSION['errors']); ?>
<?php endif; ?>