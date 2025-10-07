<?php

namespace App\Models;

class User
{
    public $id;
    public $name;
    public $email;
    public $phone;
    public $password;
    public $role;
    public $stamp_path;
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
                // استخدام SQLite مؤقتاً حتى يتم إصلاح MySQL
                $dbPath = __DIR__ . '/../../database/users.db';
                $dbDir = dirname($dbPath);
                
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0777, true);
                }
                
                self::$db = new \PDO("sqlite:$dbPath");
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                
                // إنشاء جدول المستخدمين إذا لم يكن موجوداً
                self::$db->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL,
                        email TEXT UNIQUE NOT NULL,
                        phone TEXT,
                        password TEXT NOT NULL,
                        role TEXT DEFAULT 'employee',
                        stamp_path TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // إدراج المدير الافتراضي إذا لم يكن موجوداً
                $adminExists = self::$db->query("SELECT COUNT(*) FROM users WHERE email = 'admin@sama.com'")->fetchColumn();
                if ($adminExists == 0) {
                    $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
                    self::$db->exec("
                        INSERT INTO users (name, email, password, role) 
                        VALUES ('مدير النظام', 'admin@sama.com', '$hashedPassword', 'manager')
                    ");
                }
                
            } catch (\Exception $e) {
                throw new \Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$db;
    }

    public static function findByEmail($email)
    {
        $db = self::getDb();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch();
        
        return $data ? new self($data) : null;
    }

    public function checkPassword($password)
    {
        return password_verify($password, $this->password);
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function save()
    {
        $db = self::getDb();
        
        try {
            if (isset($this->id) && $this->id) {
                // تحديث مستخدم موجود
                $stmt = $db->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, password = ?, role = ?, 
                        stamp_path = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                return $stmt->execute([
                    $this->name, $this->email, $this->phone, $this->password, 
                    $this->role, $this->stamp_path, $this->id
                ]);
            } else {
                // إنشاء مستخدم جديد
                $stmt = $db->prepare("
                    INSERT INTO users (name, email, phone, password, role, stamp_path, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([
                    $this->name, $this->email, $this->phone, $this->password, 
                    $this->role, $this->stamp_path
                ]);
                
                if ($result) {
                    $this->id = $db->lastInsertId();
                }
                
                return $result;
            }
        } catch (\Exception $e) {
            throw new \Exception("Error saving user: " . $e->getMessage());
        }
    }

    // Get user statistics
    public static function getStats()
    {
        $db = self::getDb();
        
        try {
            // Total users
            $totalStmt = $db->query("SELECT COUNT(*) as total FROM users");
            $total = $totalStmt->fetch()['total'] ?? 0;
            
            // Users by role
            $roleStmt = $db->query("
                SELECT role, COUNT(*) as count 
                FROM users 
                GROUP BY role
            ");
            $roleCounts = [];
            while ($row = $roleStmt->fetch()) {
                $roleCounts[$row['role']] = $row['count'];
            }
            
            // Recent registrations (this month)
            $recentStmt = $db->query("
                SELECT COUNT(*) as recent_count
                FROM users 
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $recent = $recentStmt->fetch();
            
            return [
                'total_users' => $total,
                'total_employees' => $roleCounts['employee'] ?? 0,
                'total_managers' => $roleCounts['manager'] ?? 0,
                'active_employees' => $roleCounts['employee'] ?? 0, // كلهم نشطين افتراضياً
                'recent_registrations' => $recent['recent_count'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'total_users' => 0,
                'total_employees' => 0,
                'total_managers' => 0,
                'active_employees' => 0,
                'recent_registrations' => 0
            ];
        }
    }

    // Get recent users
    public static function getRecent($limit = 10)
    {
        $db = self::getDb();
        
        try {
            $stmt = $db->prepare("
                SELECT * FROM users 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            $users = [];
            while ($data = $stmt->fetch()) {
                $users[] = [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'created_at' => $data['created_at']
                ];
            }
            
            return $users;
        } catch (\Exception $e) {
            return [];
        }
    }
}
