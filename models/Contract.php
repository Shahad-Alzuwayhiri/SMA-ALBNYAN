<?php
class Contract {
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contracts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM contracts WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Add other methods as needed
}
