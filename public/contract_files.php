<?php
/**
 * Contract Files Management Page - Sama Al-Bunyan Contracts Platform
 * Manage contract-specific files and attachments
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Authentication check
$auth->requireAuth();
$user = $auth->getCurrentUser();

$contract_id = $_GET['id'] ?? null;
if (!$contract_id || !is_numeric($contract_id)) {
    header('Location: contracts_list.php?error=' . urlencode('معرف العقد مطلوب'));
    exit;
}

$contract = null;
$contractFiles = [];
$error = '';
$success = '';

try {
    // Get contract details
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as created_by_name 
        FROM contracts c
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();

    if (!$contract) {
        header('Location: contracts_list.php?error=' . urlencode('العقد غير موجود'));
        exit;
    }
    
    // Role-based access control
    if ($user['role'] === 'employee' && $contract['created_by'] != $user['id']) {
        header('Location: contracts_list.php?error=' . urlencode('غير مسموح لك بإدارة ملفات هذا العقد'));
        exit;
    }
    
    // Get contract files
    $filesStmt = $pdo->prepare("
        SELECT 
            cf.*,
            u.name as uploaded_by_name
        FROM contract_files cf
        LEFT JOIN users u ON cf.uploaded_by = u.id
        WHERE cf.contract_id = ? 
        ORDER BY cf.uploaded_at DESC
    ");
    $filesStmt->execute([$contract_id]);
    $contractFiles = $filesStmt->fetchAll();

} catch (PDOException $e) {
    $error = 'خطأ في جلب بيانات العقد: ' . $e->getMessage();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['contract_file']) && $contract) {
    $uploadedFile = $_FILES['contract_file'];
    $fileDescription = trim($_POST['file_description'] ?? '');
    $fileType = $_POST['file_type'] ?? 'document';
    
    // Validation
    $uploadErrors = [];
    
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors[] = 'خطأ في رفع الملف';
    }
    
    if ($uploadedFile['size'] > 10 * 1024 * 1024) { // 10MB limit
        $uploadErrors[] = 'حجم الملف يجب أن يكون أقل من 10 ميجابايت';
    }
    
    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
    $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes)) {
        $uploadErrors[] = 'نوع الملف غير مدعوم. الأنواع المدعومة: ' . implode(', ', $allowedTypes);
    }
    
    if (empty($uploadErrors)) {
        try {
            // Create uploads directory if it doesn't exist
            $uploadsDir = '../storage/contracts/' . $contract_id;
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            // Generate unique filename
            $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $filePath = $uploadsDir . '/' . $fileName;
            
            if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                // Save file info to database
                $insertStmt = $pdo->prepare("
                    INSERT INTO contract_files (
                        contract_id, original_name, stored_name, file_path, 
                        file_size, file_type, file_extension, description,
                        uploaded_by, uploaded_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                
                $insertResult = $insertStmt->execute([
                    $contract_id,
                    $uploadedFile['name'],
                    $fileName,
                    $filePath,
                    $uploadedFile['size'],
                    $fileType,
                    $fileExtension,
                    $fileDescription,
                    $user['id']
                ]);
                
                if ($insertResult) {
                    // Log activity
                    $logStmt = $pdo->prepare("
                        INSERT INTO activity_log (user_id, contract_id, action, description, created_at)
                        VALUES (?, ?, 'file_uploaded', ?, CURRENT_TIMESTAMP)
                    ");
                    $logStmt->execute([
                        $user['id'], 
                        $contract_id, 
                        'تم رفع ملف: ' . $uploadedFile['name']
                    ]);
                    
                    $success = 'تم رفع الملف بنجاح';
                    
                    // Refresh files list
                    $filesStmt->execute([$contract_id]);
                    $contractFiles = $filesStmt->fetchAll();
                } else {
                    $error = 'خطأ في حفظ معلومات الملف';
                }
            } else {
                $error = 'خطأ في رفع الملف';
            }
            
        } catch (PDOException $e) {
            $error = 'خطأ في حفظ الملف: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $uploadErrors);
    }
}

// Handle file deletion
if (isset($_POST['delete_file']) && $contract) {
    $fileId = intval($_POST['file_id']);
    
    try {
        // Get file info
        $fileStmt = $pdo->prepare("SELECT * FROM contract_files WHERE id = ? AND contract_id = ?");
        $fileStmt->execute([$fileId, $contract_id]);
        $fileToDelete = $fileStmt->fetch();
        
        if ($fileToDelete) {
            // Check permissions
            if ($user['role'] === 'employee' && $fileToDelete['uploaded_by'] != $user['id']) {
                $error = 'غير مسموح لك بحذف هذا الملف';
            } else {
                // Delete physical file
                if (file_exists($fileToDelete['file_path'])) {
                    unlink($fileToDelete['file_path']);
                }
                
                // Delete from database
                $deleteStmt = $pdo->prepare("DELETE FROM contract_files WHERE id = ?");
                $deleteStmt->execute([$fileId]);
                
                // Log activity
                $logStmt = $pdo->prepare("
                    INSERT INTO activity_log (user_id, contract_id, action, description, created_at)
                    VALUES (?, ?, 'file_deleted', ?, CURRENT_TIMESTAMP)
                ");
                $logStmt->execute([
                    $user['id'], 
                    $contract_id, 
                    'تم حذف ملف: ' . $fileToDelete['original_name']
                ]);
                
                $success = 'تم حذف الملف بنجاح';
                
                // Refresh files list
                $filesStmt->execute([$contract_id]);
                $contractFiles = $filesStmt->fetchAll();
            }
        } else {
            $error = 'الملف غير موجود';
        }
        
    } catch (PDOException $e) {
        $error = 'خطأ في حذف الملف: ' . $e->getMessage();
    }
}

// Helper functions
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' ميجابايت';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' كيلوبايت';
    } else {
        return $bytes . ' بايت';
    }
}

function getFileIcon($extension) {
    $icons = [
        'pdf' => 'fas fa-file-pdf text-danger',
        'doc' => 'fas fa-file-word text-primary',
        'docx' => 'fas fa-file-word text-primary',
        'jpg' => 'fas fa-file-image text-success',
        'jpeg' => 'fas fa-file-image text-success',
        'png' => 'fas fa-file-image text-success',
        'gif' => 'fas fa-file-image text-success'
    ];
    return $icons[$extension] ?? 'fas fa-file text-secondary';
}

function getFileTypeName($type) {
    $types = [
        'document' => 'وثيقة',
        'image' => 'صورة',
        'signature' => 'توقيع',
        'id_copy' => 'نسخة هوية',
        'other' => 'أخرى'
    ];
    return $types[$type] ?? 'غير محدد';
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة ملفات العقد <?= htmlspecialchars($contract['contract_number'] ?? '') ?> - سما البنيان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/unified-theme.css" rel="stylesheet">
    <style>
        :root {
            --sma-gold: #D4AF37;
            --sma-dark-gold: #B8941F;
            --sma-gray: #6C757D;
        }
        
        .files-header {
            background: linear-gradient(135deg, #6f42c1, #e83e8c);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .upload-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 2px dashed var(--sma-gold);
        }
        
        .files-list {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .file-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-right: 4px solid var(--sma-gold);
        }
        
        .file-item:hover {
            background: #e9ecef;
            transition: background-color 0.3s ease;
        }
        
        .file-icon {
            font-size: 2rem;
            margin-left: 1rem;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: var(--sma-gold);
            background: #fff;
        }
        
        .upload-area.dragover {
            border-color: var(--sma-gold);
            background: rgba(212, 175, 55, 0.1);
        }
        
        .section-title {
            color: var(--sma-dark-gold);
            border-bottom: 2px solid var(--sma-gold);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .btn-sma-primary {
            background: var(--sma-gold);
            border-color: var(--sma-gold);
            color: white;
        }
        
        .btn-sma-primary:hover {
            background: var(--sma-dark-gold);
            border-color: var(--sma-dark-gold);
            color: white;
        }
        
        @media (max-width: 768px) {
            .file-item {
                flex-direction: column;
                text-align: center;
            }
            
            .file-icon {
                margin: 0 0 1rem 0;
            }
            
            .file-actions {
                justify-content: center;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <?php if ($error && !$contract): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <div class="text-center">
            <a href="contracts_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
        <?php elseif ($contract): ?>
        
        <!-- Files Header -->
        <div class="files-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-paperclip me-2"></i>
                        إدارة ملفات العقد رقم: <?= htmlspecialchars($contract['contract_number']) ?>
                    </h2>
                    <p class="mb-2">
                        <strong>العميل:</strong> <?= htmlspecialchars($contract['client_name']) ?>
                        <span class="ms-3">
                            <strong>عدد الملفات:</strong> <?= count($contractFiles) ?>
                        </span>
                    </p>
                    <p class="mb-0">
                        <strong>منشئ العقد:</strong> <?= htmlspecialchars($contract['created_by_name']) ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="contract_view.php?id=<?= $contract['id'] ?>" class="btn btn-outline-light me-2">
                        <i class="fas fa-eye me-1"></i> عرض العقد
                    </a>
                    <a href="contracts_list.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Upload Section -->
            <div class="col-lg-4">
                <div class="upload-section">
                    <h4 class="section-title">رفع ملف جديد</h4>
                    
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <p class="mb-2">اسحب الملف هنا أو انقر للاختيار</p>
                            <input type="file" class="form-control d-none" id="contract_file" name="contract_file" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" required>
                            <small class="text-muted">الحد الأقصى: 10 ميجابايت</small>
                        </div>
                        
                        <div class="mt-3">
                            <label for="file_type" class="form-label">نوع الملف</label>
                            <select class="form-select" id="file_type" name="file_type" required>
                                <option value="document">وثيقة</option>
                                <option value="image">صورة</option>
                                <option value="signature">توقيع</option>
                                <option value="id_copy">نسخة هوية</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        
                        <div class="mt-3">
                            <label for="file_description" class="form-label">وصف الملف</label>
                            <textarea class="form-control" id="file_description" name="file_description" 
                                      rows="3" placeholder="وصف مختصر للملف..."></textarea>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-sma-primary w-100">
                                <i class="fas fa-upload me-1"></i> رفع الملف
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Upload Tips -->
                <div class="upload-section">
                    <h5 class="section-title">نصائح الرفع</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            الأنواع المدعومة: PDF, DOC, DOCX, JPG, PNG, GIF
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            الحد الأقصى لحجم الملف: 10 ميجابايت
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            يُفضل ملفات PDF للوثائق الرسمية
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            أضف وصف واضح لكل ملف
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Files List -->
            <div class="col-lg-8">
                <div class="files-list">
                    <h4 class="section-title">الملفات المرفقة</h4>
                    
                    <?php if (empty($contractFiles)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">لا توجد ملفات مرفقة بهذا العقد</p>
                        <small class="text-muted">استخدم النموذج على اليسار لرفع الملفات</small>
                    </div>
                    <?php else: ?>
                    
                    <?php foreach ($contractFiles as $file): ?>
                    <div class="file-item d-flex align-items-center">
                        <div class="file-icon">
                            <i class="<?= getFileIcon($file['file_extension']) ?>"></i>
                        </div>
                        
                        <div class="file-info">
                            <h6 class="mb-1"><?= htmlspecialchars($file['original_name']) ?></h6>
                            <p class="mb-1 text-muted">
                                <span class="badge bg-info"><?= getFileTypeName($file['file_type']) ?></span>
                                <span class="ms-2"><?= formatFileSize($file['file_size']) ?></span>
                            </p>
                            <?php if (!empty($file['description'])): ?>
                            <p class="mb-1 small text-muted"><?= htmlspecialchars($file['description']) ?></p>
                            <?php endif; ?>
                            <small class="text-muted">
                                رُفع بواسطة: <?= htmlspecialchars($file['uploaded_by_name']) ?> - 
                                <?= date('Y/m/d H:i', strtotime($file['uploaded_at'])) ?>
                            </small>
                        </div>
                        
                        <div class="file-actions">
                            <!-- Download -->
                            <a href="download_file.php?id=<?= $file['id'] ?>" 
                               class="btn btn-outline-primary btn-sm" title="تحميل">
                                <i class="fas fa-download"></i>
                            </a>
                            
                            <!-- View (for images and PDFs) -->
                            <?php if (in_array($file['file_extension'], ['jpg', 'jpeg', 'png', 'gif', 'pdf'])): ?>
                            <a href="view_file.php?id=<?= $file['id'] ?>" 
                               class="btn btn-outline-info btn-sm" target="_blank" title="عرض">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Delete -->
                            <?php if (in_array($user['role'], ['manager', 'admin']) || $file['uploaded_by'] == $user['id']): ?>
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars($file['original_name']) ?>')" 
                                    title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد حذف الملف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من رغبتك في حذف هذا الملف؟</p>
                    <p><strong id="fileName"></strong></p>
                    <p class="text-danger"><small>هذا الإجراء لا يمكن التراجع عنه.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="file_id" id="fileIdToDelete">
                        <button type="submit" name="delete_file" class="btn btn-danger">حذف الملف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload drag and drop
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('contract_file');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileDisplay(files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateFileDisplay(e.target.files[0]);
            }
        });
        
        function updateFileDisplay(file) {
            const uploadArea = document.getElementById('uploadArea');
            uploadArea.innerHTML = `
                <i class="fas fa-file fa-3x text-success mb-3"></i>
                <p class="mb-2"><strong>${file.name}</strong></p>
                <p class="mb-2">الحجم: ${formatFileSize(file.size)}</p>
                <small class="text-muted">انقر لتغيير الملف</small>
            `;
        }
        
        function formatFileSize(bytes) {
            if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' ميجابايت';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' كيلوبايت';
            } else {
                return bytes + ' بايت';
            }
        }
        
        // Delete file
        function deleteFile(fileId, fileName) {
            document.getElementById('fileIdToDelete').value = fileId;
            document.getElementById('fileName').textContent = fileName;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('contract_file');
            
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('يرجى اختيار ملف للرفع');
                return;
            }
            
            const file = fileInput.files[0];
            if (file.size > 10 * 1024 * 1024) {
                e.preventDefault();
                alert('حجم الملف يجب أن يكون أقل من 10 ميجابايت');
                return;
            }
        });
    </script>
</body>
</html>