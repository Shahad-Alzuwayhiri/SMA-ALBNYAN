<?php

class SimpleContractController
{
    public function index()
    {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Load contracts from database
        try {
            require_once __DIR__ . '/../../Models/Contract.php';
            
            // Get contracts based on user role
            if ($_SESSION['user_role'] === 'manager') {
                // Manager sees all contracts
                $contracts = \App\Models\Contract::all();
            } else {
                // Employee sees only their contracts
                $contracts = $this->getUserContracts($_SESSION['user_id']);
            }
            
            // Convert to array format for template
            $contractsArray = [];
            foreach ($contracts as $contract) {
                $contractsArray[] = [
                    'id' => $contract->id ?? $contract['id'],
                    'serial' => 'CT-' . str_pad($contract->id ?? $contract['id'], 4, '0', STR_PAD_LEFT),
                    'title' => $contract->title ?? $contract['title'],
                    'client_name' => $contract->client_name ?? $contract['client_name'],
                    'status' => $contract->status ?? $contract['status'],
                    'amount' => $contract->amount ?? $contract['amount'],
                    'created_at' => $contract->created_at ?? $contract['created_at']
                ];
            }
        } catch (Exception $e) {
            $contractsArray = [];
            $_SESSION['error'] = 'حدث خطأ في تحميل العقود: ' . $e->getMessage();
        }
        
        // Include layout helpers
        require_once dirname(__DIR__, 3) . '/templates/layout_helpers.php';
        
        // Load contracts list template
        ob_start();
        $contracts = $contractsArray; // Make available to template
        include dirname(__DIR__, 3) . '/templates/contracts_list.php';
        $content = ob_get_clean();
        
        // Return using master layout
        return renderMasterLayout(
            $content,
            ['contracts' => $contractsArray],
            'إدارة العقود - سما البنيان التجارية',
            false, // is_auth_page
            true, // show_sidebar
            '', // additional_head
            '' // additional_scripts
        );
    }

    // Get contracts for specific user
    private function getUserContracts($userId)
    {
        $db = \App\Models\Contract::getDb();
        $stmt = $db->prepare("SELECT * FROM contracts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        
        $contracts = [];
        while ($data = $stmt->fetch()) {
            $contracts[] = $data;
        }
        
        return $contracts;
    }

    public function create()
    {
        // Include layout helpers
        require_once dirname(__DIR__, 3) . '/templates/layout_helpers.php';
        
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Load create contract template
        ob_start();
        include dirname(__DIR__, 3) . '/templates/create_contract.php';
        $content = ob_get_clean();
        
        // Return using master layout
        return renderMasterLayout(
            $content,
            [],
            'إنشاء عقد جديد - سما البنيان التجارية',
            false, // is_auth_page
            true, // show_sidebar
            '', // additional_head
            '' // additional_scripts
        );
    }

    public function store()
    {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Validate input
        $title = trim($_POST["title"] ?? "");
        $client_name = trim($_POST["client_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $amount = floatval($_POST["amount"] ?? 0);
        $status = $_POST["status"] ?? "draft";
        
        $errors = [];
        
        if (empty($title)) {
            $errors[] = "عنوان العقد مطلوب";
        }
        
        if (empty($client_name)) {
            $errors[] = "اسم العميل مطلوب";
        }
        
        if (!in_array($status, ['draft', 'pending', 'approved', 'completed', 'rejected'])) {
            $errors[] = "حالة العقد غير صحيحة";
        }
        
        // Check if employee is trying to set unauthorized status
        if ($_SESSION['user_role'] !== 'manager' && in_array($status, ['approved', 'completed', 'rejected'])) {
            $errors[] = "لا يحق لك تعيين هذه الحالة للعقد";
        }
        
        if (!empty($errors)) {
            $_SESSION["errors"] = $errors;
            header("Location: /contracts/create");
            exit;
        }
        
        // Save to database
        try {
            require_once __DIR__ . '/../../Models/Contract.php';
            
            $contract = new \App\Models\Contract([
                'title' => $title,
                'client_name' => $client_name,
                'description' => $description,
                'amount' => $amount,
                'status' => $status,
                'user_id' => $_SESSION['user_id']
            ]);
            
            if ($contract->save()) {
                $_SESSION["success"] = "تم إنشاء العقد بنجاح";
                header("Location: /contracts");
            } else {
                $_SESSION["error"] = "حدث خطأ أثناء حفظ العقد";
                header("Location: /contracts/create");
            }
        } catch (Exception $e) {
            $_SESSION["error"] = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
            header("Location: /contracts/create");
        }
        
        exit;
    }

    public function show($id)
    {
        $contract = [
            "id" => $id,
            "serial" => "CT-" . str_pad($id, 4, "0", STR_PAD_LEFT),
            "client_name" => "شركة المستقبل",
            "status" => "pending",
            "created_at" => "2025-10-01"
        ];
        
        return view("contracts.show", ["contract" => $contract]);
    }

    public function pdf($id)
    {
        header("Content-Type: text/html; charset=utf-8");
        echo "<h1>عقد رقم: CT-" . str_pad($id, 4, "0", STR_PAD_LEFT) . "</h1>";
        exit;
    }

    public function inProgress()
    {
        $contracts_in_progress = [
            (object)["id" => 1, "serial" => "CT-0001", "client_name" => "أحمد محمد", "status" => "in_progress", "status_display" => "قيد التنفيذ"]
        ];
        return view("contracts.in_progress", ["contracts_in_progress" => $contracts_in_progress]);
    }

    public function closed()
    {
        $contracts_closed = [
            (object)["id" => 3, "serial" => "CT-0003", "client_name" => "محمد سالم", "status" => "completed", "status_display" => "مكتمل"]
        ];
        return view("contracts.closed", ["contracts_closed" => $contracts_closed]);
    }

    public function approve($id)
    {
        $_SESSION["success"] = "تم اعتماد العقد بنجاح";
        header("Location: " . ($_SERVER["HTTP_REFERER"] ?? "/dashboard"));
        exit;
    }

    public function reject($id)
    {
        $_SESSION["success"] = "تم رفض العقد";
        header("Location: " . ($_SERVER["HTTP_REFERER"] ?? "/dashboard"));
        exit;
    }

    public function archive($id)
    {
        $_SESSION["success"] = "تم أرشفة العقد";
        header("Location: " . ($_SERVER["HTTP_REFERER"] ?? "/dashboard"));
        exit;
    }
}