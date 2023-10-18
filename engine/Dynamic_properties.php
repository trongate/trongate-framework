<?php
/**
 * Trait Dynamic_properties - Provides dynamic property handling methods.
 */
trait Dynamic_properties {
    private $attributes = [];
    

    /**
     * Set a dynamic property.
     *
     * @param string $key The property key.
     * @param mixed $value The value to set.
     * @return void
     */
    public function __set(string $key, $value): void {
        $this->attributes[$key] = $value;
    }

    /**
     * Get a dynamic property.
     *
     * @param string $key The property key.
     * @return mixed The value of the property.
     */
    public function __get(string $key) {

        if ($key === 'model') {
            if (!isset($this->model)) {
                require_once 'Model.php';
                $this->model = new Model($this->module_name);
            }
            return $this->model;
        } elseif (!isset($this->attributes[$key])) {
            $class_name = ucfirst($key);
            $this->$key = new $key;
        }
        return $this->attributes[$key];
    }
}