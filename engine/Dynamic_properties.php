<?php
trait Dynamic_properties {
    private $attributes = [];
    
    public function __set(string $key, mixed $value): void {
        $this->attributes[$key] = $value;
    }
    
    public function __get(string $key) {
        return $this->attributes[$key];
    }
}