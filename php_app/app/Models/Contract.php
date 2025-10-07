<?php

namespace App\Models;

class Contract
{
    public $id;
    public $title;
    public $description;
    public $amount;
    public $client_name;
    public $status;
    public $user_id;
    public $pdf_path;
    public $created_at;
    public $updated_at;

    private static $db = null;

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function getDb()
    {
        if (self::$db === null) {
            try {
                // استخدام SQLite مع المسار الصحيح
                $dbPath = __DIR__ . '/../../../contracts.db';
                $dbDir = dirname($dbPath);
                
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0777, true);
                }
                
                self::$db = new \PDO("sqlite:$dbPath");
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                
                // إنشاء جدول العقود إذا لم يكن موجوداً
                self::$db->exec("
                    CREATE TABLE IF NOT EXISTS contracts (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        title TEXT NOT NULL,
                        description TEXT,
                        amount DECIMAL(10,2),
                        client_name TEXT NOT NULL,
                        status TEXT DEFAULT 'draft',
                        user_id INTEGER,
                        pdf_path TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
            } catch (\Exception $e) {
                throw new \Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$db;
    }

    public static function all()
    {
        $db = self::getDb();
        $stmt = $db->query("SELECT * FROM contracts ORDER BY created_at DESC");
        $contracts = [];
        
        while ($data = $stmt->fetch()) {
            $contracts[] = new self($data);
        }
        
        return $contracts;
    }

    public static function find($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare("SELECT * FROM contracts WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        return $data ? new self($data) : null;
    }

    public function save()
    {
        $db = self::getDb();
        
        if ($this->id) {
            $stmt = $db->prepare("
                UPDATE contracts 
                SET title = ?, description = ?, amount = ?, client_name = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([
                $this->title,
                $this->description,
                $this->amount,
                $this->client_name,
                $this->status,
                $this->id
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO contracts (title, description, amount, client_name, status, user_id, pdf_path, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $result = $stmt->execute([
                $this->title,
                $this->description,
                $this->amount,
                $this->client_name,
                $this->status,
                $this->user_id,
                $this->pdf_path
            ]);
            
            if ($result) {
                $this->id = $db->lastInsertId();
            }
            
            return $result;
        }
    }

    // Get contract statistics
    public static function getStats()
    {
        $db = self::getDb();
        
        try {
            // Total contracts
            $totalStmt = $db->query("SELECT COUNT(*) as total FROM contracts");
            $total = $totalStmt->fetch()['total'] ?? 0;
            
            // Contracts by status
            $statusStmt = $db->query("
                SELECT status, COUNT(*) as count 
                FROM contracts 
                GROUP BY status
            ");
            $statusCounts = [];
            while ($row = $statusStmt->fetch()) {
                $statusCounts[$row['status']] = $row['count'];
            }
            
            // Monthly totals (current month)
            $monthlyStmt = $db->query("
                SELECT SUM(amount) as monthly_total, COUNT(*) as monthly_count
                FROM contracts 
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $monthly = $monthlyStmt->fetch();
            
            return [
                'total' => $total,
                'pending' => $statusCounts['pending'] ?? 0,
                'approved' => $statusCounts['approved'] ?? 0,
                'completed' => $statusCounts['completed'] ?? 0,
                'draft' => $statusCounts['draft'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
                'monthly_total' => $monthly['monthly_total'] ?? 0,
                'monthly_count' => $monthly['monthly_count'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'completed' => 0,
                'draft' => 0,
                'rejected' => 0,
                'monthly_total' => 0,
                'monthly_count' => 0
            ];
        }
    }

    // Get recent contracts
    public static function getRecent($limit = 10)
    {
        $db = self::getDb();
        
        try {
            $stmt = $db->prepare("
                SELECT c.*, u.name as user_name 
                FROM contracts c 
                LEFT JOIN users u ON c.user_id = u.id 
                ORDER BY c.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            $contracts = [];
            while ($data = $stmt->fetch()) {
                $contracts[] = [
                    'id' => $data['id'],
                    'title' => $data['title'],
                    'client_name' => $data['client_name'],
                    'status' => $data['status'],
                    'amount' => $data['amount'],
                    'user_name' => $data['user_name'],
                    'created_at' => $data['created_at']
                ];
            }
            
            return $contracts;
        } catch (\Exception $e) {
            return [];
        }
    }

    // Get recent activities
    public static function getRecentActivities($limit = 10)
    {
        $db = self::getDb();
        
        try {
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.title,
                    c.client_name,
                    c.status,
                    c.created_at,
                    c.updated_at,
                    u.name as user_name,
                    CASE 
                        WHEN c.created_at = c.updated_at THEN 'إنشاء عقد جديد'
                        WHEN c.status = 'approved' THEN 'موافقة على عقد'
                        WHEN c.status = 'rejected' THEN 'رفض عقد'
                        WHEN c.status = 'completed' THEN 'إتمام عقد'
                        ELSE 'تحديث عقد'
                    END as action
                FROM contracts c
                LEFT JOIN users u ON c.user_id = u.id
                ORDER BY c.updated_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            $activities = [];
            while ($data = $stmt->fetch()) {
                $activities[] = [
                    'action' => $data['action'],
                    'contract' => 'CT-' . str_pad($data['id'], 4, '0', STR_PAD_LEFT),
                    'user' => $data['user_name'] ?? 'مستخدم غير معروف',
                    'time' => self::timeAgo($data['updated_at']),
                    'contract_id' => $data['id'],
                    'client_name' => $data['client_name']
                ];
            }
            
            return $activities;
        } catch (\Exception $e) {
            return [];
        }
    }

    // Helper function to calculate time ago
    private static function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'منذ لحظات';
        if ($time < 3600) return 'منذ ' . floor($time/60) . ' دقيقة';
        if ($time < 86400) return 'منذ ' . floor($time/3600) . ' ساعة';
        if ($time < 2592000) return 'منذ ' . floor($time/86400) . ' يوم';
        if ($time < 31104000) return 'منذ ' . floor($time/2592000) . ' شهر';
        return 'منذ ' . floor($time/31104000) . ' سنة';
    }
}
