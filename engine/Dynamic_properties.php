<?php

/**
 * dynamic_properties.php
 *
 * This file provides a solution for dynamically setting and getting properties
 * within classes in PHP. It includes a trait called `Dynamic_properties` that
 * can be used by other classes to enable dynamic property handling.
 */

/**
 * Trait Dynamic_properties
 *
 * This trait provides functionality for dynamically setting and getting properties.
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
     * If the property is 'model', it initializes and returns a Model object if not already set.
     * Otherwise, it creates an instance of the class corresponding to the property name if not set.
     *
     * @param string $key The property key.
     * @return mixed The value of the property.
     */
    public function __get(string $key) {
        if ($key === 'model') {
            if (!isset($this->model)) {
                require_once 'Model.php'; // Adjust the path accordingly
                $this->model = new Model($this->module_name); // Adjust instantiation as needed
            }
            return $this->model;
        } elseif (!isset($this->attributes[$key])) {
            // Adjust class instantiation based on property name if needed
            $class_name = ucfirst($key);
            $this->$key = new $class_name; // Adjust instantiation as needed
        }
        return $this->attributes[$key];
    }
}

/**
 * Example usage:
 *
 * class My_Class {
 *     use Dynamic_Properties;
 *     // ...
 * }
 *
 * $obj = new My_Class();
 * $obj->name = 'John Doe'; // Dynamically set the 'name' property
 * $obj->age = 30; // Dynamically set the 'age' property
 *
 * echo $obj->name; // Output: 'John Doe'
 * echo $obj->age; // Output: 30
 *
 * if (isset($obj->email)) {
 *     echo $obj->email; // Property 'email' is not set, so this won't be executed
 * }
 */