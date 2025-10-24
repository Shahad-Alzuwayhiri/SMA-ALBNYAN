<?php
/**
 * Master Layout Helper Functions
 * دوال مساعدة لاستخدام النظام الجديد للـ layouts
 */

if (!function_exists('renderMasterLayout')) {
    /**
     * Render page using master layout
     * 
     * @param string $content المحتوى الأساسي للصفحة
     * @param array $data البيانات المرسلة للصفحة
     * @param string $title عنوان الصفحة
     * @param bool $is_auth_page هل هي صفحة مصادقة؟
     * @param bool $show_sidebar عرض الشريط الجانبي؟
     * @param string $additional_head محتوى إضافي للـ head
     * @param string $additional_scripts scripts إضافية
     * @return string
     */
    function renderMasterLayout($content, $data = [], $title = null, $is_auth_page = false, $show_sidebar = true, $additional_head = '', $additional_scripts = '') {
        // حفظ المعاملات الأصلية قبل extract
        $originalTitle = $title;
        $originalIsAuthPage = $is_auth_page;
        $originalShowSidebar = $show_sidebar;
        $originalAdditionalHead = $additional_head;
        $originalAdditionalScripts = $additional_scripts;
        
        // استخراج المتغيرات من المصفوفة
        extract($data);
        
        // استخدام المعاملات المحفوظة إذا لم تكن في البيانات
        $title = $originalTitle ?? 'نظام إدارة العقود - سما البنيان التجارية';
        $is_auth_page = $originalIsAuthPage;
        $show_sidebar = $originalShowSidebar;
        $additional_head = $originalAdditionalHead;
        $additional_scripts = $originalAdditionalScripts;
        
        // تحميل وإرجاع الـ layout
        ob_start();
        include __DIR__ . '/master_layout.php';
        return ob_get_clean();
    }
}

if (!function_exists('renderAuthLayout')) {
    /**
     * Render authentication page (login, register, etc.)
     * 
     * @param string $template_name اسم قالب المصادقة
     * @param array $data البيانات
     * @param string $title العنوان
     * @return string
     */
    function renderAuthLayout($template_name, $data = [], $title = null) {
        // تحميل محتوى قالب المصادقة
        extract($data);
        ob_start();
        include __DIR__ . "/{$template_name}.php";
        $content = ob_get_clean();
        
        // استخدام master layout مع إعدادات المصادقة
        return renderMasterLayout(
            $content,
            $data,
            $title ?? 'سما البنيان التجارية - ' . ucfirst($template_name),
            true, // is_auth_page
            false, // show_sidebar
            '', // additional_head
            '' // additional_scripts
        );
    }
}

if (!function_exists('renderDashboardLayout')) {
    /**
     * Render dashboard page with sidebar and navigation
     * 
     * @param string $template_name اسم القالب
     * @param array $data البيانات
     * @param string $title العنوان
     * @param string $additional_head محتوى head إضافي
     * @param string $additional_scripts scripts إضافية
     * @return string
     */
    function renderDashboardLayout($template_name, $data = [], $title = null, $additional_head = '', $additional_scripts = '') {
        // تحميل محتوى القالب
        extract($data);
        ob_start();
        include __DIR__ . "/{$template_name}.php";
        $content = ob_get_clean();
        
        // استخدام master layout مع إعدادات لوحة التحكم
        return renderMasterLayout(
            $content,
            $data,
            $title,
            false, // is_auth_page
            true, // show_sidebar
            $additional_head,
            $additional_scripts
        );
    }
}

if (!function_exists('renderPageLayout')) {
    /**
     * Render regular page without sidebar
     * 
     * @param string $template_name اسم القالب
     * @param array $data البيانات
     * @param string $title العنوان
     * @param string $additional_head محتوى head إضافي
     * @param string $additional_scripts scripts إضافية
     * @return string
     */
    function renderPageLayout($template_name, $data = [], $title = null, $additional_head = '', $additional_scripts = '') {
        // تحميل محتوى القالب
        extract($data);
        ob_start();
        include __DIR__ . "/{$template_name}.php";
        $content = ob_get_clean();
        
        // استخدام master layout بدون sidebar
        return renderMasterLayout(
            $content,
            $data,
            $title,
            false, // is_auth_page
            false, // show_sidebar
            $additional_head,
            $additional_scripts
        );
    }
}

if (!function_exists('setFlashMessage')) {
    /**
     * Set flash message for next request
     * 
     * @param string $type نوع الرسالة (success, error, warning, info)
     * @param string $message الرسالة
     */
    function setFlashMessage($type, $message) {
        $_SESSION[$type] = $message;
    }
}

if (!function_exists('addBreadcrumb')) {
    /**
     * Add breadcrumb item
     * 
     * @param string $title العنوان
     * @param string $url الرابط
     * @param string $icon الأيقونة
     */
    function addBreadcrumb($title, $url = null, $icon = null) {
        if (!isset($_SESSION['breadcrumbs'])) {
            $_SESSION['breadcrumbs'] = [];
        }
        
        $_SESSION['breadcrumbs'][] = [
            'title' => $title,
            'url' => $url,
            'icon' => $icon
        ];
    }
}

if (!function_exists('clearBreadcrumbs')) {
    /**
     * Clear all breadcrumbs
     */
    function clearBreadcrumbs() {
        unset($_SESSION['breadcrumbs']);
    }
}

if (!function_exists('includeAssets')) {
    /**
     * Include CSS and JS assets
     * 
     * @param array $css مصفوفة ملفات CSS
     * @param array $js مصفوفة ملفات JavaScript
     * @return string
     */
    function includeAssets($css = [], $js = []) {
        $html = '';
        
        // Include CSS files
        foreach ($css as $file) {
            $html .= "<link rel=\"stylesheet\" href=\"{$file}\">\n";
        }
        
        // Include JS files
        foreach ($js as $file) {
            $html .= "<script src=\"{$file}\"></script>\n";
        }
        
        return $html;
    }
}

if (!function_exists('getCurrentUser')) {
    /**
     * Get current authenticated user data
     * 
     * @return object|null
     */
    function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return (object)[
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'مستخدم',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'employee',
        ];
    }
}

if (!function_exists('isAuthenticated')) {
    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('hasRole')) {
    /**
     * Check if user has specific role
     * 
     * @param string $role الدور المطلوب
     * @return bool
     */
    function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
}

if (!function_exists('requireAuth')) {
    /**
     * Require authentication - redirect to login if not authenticated
     */
    function requireAuth() {
        if (!isAuthenticated()) {
            setFlashMessage('error', 'يجب تسجيل الدخول للوصول لهذه الصفحة');
            header('Location: /login');
            exit;
        }
    }
}

if (!function_exists('requireRole')) {
    /**
     * Require specific role - redirect if user doesn't have required role
     * 
     * @param string $role الدور المطلوب
     */
    function requireRole($role) {
        requireAuth();
        
        if (!hasRole($role)) {
            setFlashMessage('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
            
            // Redirect based on current user role
            $currentRole = $_SESSION['user_role'] ?? 'employee';
            if ($currentRole === 'manager') {
                header('Location: /manager-dashboard');
            } else {
                header('Location: /employee-dashboard');
            }
            exit;
        }
    }
}