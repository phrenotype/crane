<?php

namespace Crane;

class Crane
{    
    private $rawAttributes = [];

    /**
     * Convert and array into a generic object.
     * 
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->rawAttributes = $attributes;
        $this->fromArray($attributes);
    }

    private function fromArray(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $this->$key = new self($value);
            } else {
                $this->$key = $value;
            }
        }
    }

    public function __get($name)
    {
        return $this->$name ?? null;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * Get object attributes as an associative array.
     * 
     * @return array
     */
    public function asArray(): array
    {
        $copy = $this->rawAttributes;
        return $copy;
    }
}
