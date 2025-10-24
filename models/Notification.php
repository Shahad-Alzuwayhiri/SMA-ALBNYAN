<?php
class Notification {
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Backwards compatible create: either create($userId, $type, $message, $contractId)
    // or create(array $data) with keys: user_id, contract_id, type, title/message
    public function create($a, $b = null, $c = null, $d = null)
    {
        if (is_array($a)) {
            $data = $a;
            $userId = $data['user_id'] ?? null;
            $contractId = $data['contract_id'] ?? ($data['related_contract_id'] ?? null);
            $type = $data['type'] ?? null;
            $message = $data['message'] ?? ($data['title'] ?? null);
        } else {
            $userId = $a;
            $type = $b;
            $message = $c;
            $contractId = $d;
        }

        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, related_contract_id, type, message) VALUES (?, ?, ?, ?)');
        return $stmt->execute([$userId, $contractId, $type, $message]);
    }

    // Add other methods as needed
}

?>