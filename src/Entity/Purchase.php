<?php
namespace App\Entity;

use App\Entity\User;
use App\Entity\Product;

class Purchase
{
    private $id;
    private $user; // User object or user id
    private $product; // Product object or product id
    private $quantity;
    private $price;
    private $purchasedAt;

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getUser() { return $this->user; }
    public function setUser($user) { $this->user = $user; }

    public function getProduct() { return $this->product; }
    public function setProduct($product) { $this->product = $product; }

    public function getQuantity() { return $this->quantity; }
    public function setQuantity($quantity) { $this->quantity = $quantity; }

    public function getPrice() { return $this->price; }
    public function setPrice($price) { $this->price = $price; }

    public function getPurchasedAt() { return $this->purchasedAt; }
    public function setPurchasedAt($purchasedAt) { $this->purchasedAt = $purchasedAt; }
}
