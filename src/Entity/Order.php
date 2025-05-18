<?php
namespace App\Entity;

class Order
{
    private $id;
    private $userId;
    private $items = [];
    private $total;
    private $status; // pending, shipped, delivered
    private $createdAt;
    private $shippedAt;

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getUserId() { return $this->userId; }
    public function setUserId($userId) { $this->userId = $userId; }

    public function getItems() { return $this->items; }
    public function setItems($items) { $this->items = $items; }

    public function getTotal() { return $this->total; }
    public function setTotal($total) { $this->total = $total; }

    public function getStatus() { return $this->status; }
    public function setStatus($status) { $this->status = $status; }

    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }

    public function getShippedAt() { return $this->shippedAt; }
    public function setShippedAt($shippedAt) { $this->shippedAt = $shippedAt; }
}
