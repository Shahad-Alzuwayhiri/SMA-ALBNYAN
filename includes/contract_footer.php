    </div> <!-- End Main Container -->
    
    <!-- Company Watermark -->
    <div class="company-watermark no-print">
        <img src="../static/img/SMA-LOGO.png" alt="شعار سما البنيان" style="width: 120px;" 
             onerror="this.style.display='none'">
    </div>
    
    <!-- Success Toast Template -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="successMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    
    <!-- Error Toast Template -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11; margin-bottom: 80px;">
        <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="errorMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    
    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-body text-center">
                    <div class="spinner-border text-warning" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <div class="mt-3 text-white">
                        <strong>جاري المعالجة...</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Common Contract Functions -->
    <script>
        // Global SMA Contract System Object
        const SMAContracts = {
            // Show success message
            showSuccess: function(message) {
                document.getElementById('successMessage').textContent = message;
                const toast = new bootstrap.Toast(document.getElementById('successToast'));
                toast.show();
            },
            
            // Show error message
            showError: function(message) {
                document.getElementById('errorMessage').textContent = message;
                const toast = new bootstrap.Toast(document.getElementById('errorToast'));
                toast.show();
            },
            
            // Show loading modal
            showLoading: function() {
                const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
                modal.show();
            },
            
            // Hide loading modal
            hideLoading: function() {
                const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
                if (modal) modal.hide();
            },
            
            // Format currency in Arabic
            formatCurrency: function(amount) {
                return new Intl.NumberFormat('ar-SA', {
                    style: 'currency',
                    currency: 'SAR',
                    minimumFractionDigits: 2
                }).format(amount);
            },
            
            // Format number in Arabic
            formatNumber: function(number) {
                return new Intl.NumberFormat('ar-SA').format(number);
            },
            
            // Validate Saudi ID
            validateSaudiId: function(id) {
                return /^[0-9]{10}$/.test(id);
            },
            
            // Validate Saudi phone
            validateSaudiPhone: function(phone) {
                return /^05[0-9]{8}$/.test(phone);
            },
            
            // Confirm action with modal
            confirmAction: function(message, callback) {
                if (confirm(message)) {
                    callback();
                }
            },
            
            // Auto-resize textareas
            autoResizeTextarea: function(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            },
            
            // Initialize tooltips
            initTooltips: function() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            },
            
            // Initialize popovers
            initPopovers: function() {
                const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                popoverTriggerList.map(function (popoverTriggerEl) {
                    return new bootstrap.Popover(popoverTriggerEl);
                });
            },
            
            // Print specific element
            printElement: function(elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html dir="rtl" lang="ar">
                        <head>
                            <meta charset="UTF-8">
                            <title>طباعة - سما البنيان</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
                            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
                            <style>
                                * { font-family: 'Cairo', sans-serif; }
                                body { direction: rtl; }
                                @media print {
                                    .no-print { display: none !important; }
                                    body { margin: 0; font-size: 12px; }
                                }
                            </style>
                        </head>
                        <body>
                            ${element.innerHTML}
                        </body>
                        </html>
                    `);
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(() => {
                        printWindow.print();
                        printWindow.close();
                    }, 500);
                }
            }
        };
        
        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap components
            SMAContracts.initTooltips();
            SMAContracts.initPopovers();
            
            // Auto-resize textareas
            document.querySelectorAll('textarea').forEach(textarea => {
                textarea.addEventListener('input', () => SMAContracts.autoResizeTextarea(textarea));
                SMAContracts.autoResizeTextarea(textarea);
            });
            
            // Add fade-in animation to cards
            document.querySelectorAll('.sma-card').forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 100);
            });
            
            // Handle form submissions with loading
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="sma-loading me-2"></span> جاري المعالجة...';
                        
                        // Re-enable after 10 seconds as fallback
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'إرسال';
                        }, 10000);
                    }
                });
            });
            
            // Store original button texts
            document.querySelectorAll('button[type="submit"]').forEach(btn => {
                btn.setAttribute('data-original-text', btn.innerHTML);
            });
        });
        
        // Auto-close alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                if (bsAlert) bsAlert.close();
            });
        }, 5000);
    </script>
    
    <?php if (isset($additionalScripts)): ?>
        <script><?= $additionalScripts ?></script>
    <?php endif; ?>
</body>
</html>