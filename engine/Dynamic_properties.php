<?php
trait Dynamic_properties {
    private $attributes = [];
    
    public function __set(string $key, object $value): void {
        $this->attributes[$key] = $value;
    }
    
    public function __get(string $key) {

        if (!isset($this->attributes[$key])) {
            $class_name = ucfirst($key);
            $this->$key = new $key;
        }
        return $this->attributes[$key];
    }
}