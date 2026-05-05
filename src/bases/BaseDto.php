<?php

class BaseDto
{
    public function getDataValues()
    {
        return array_filter(get_object_vars($this), function ($value) {
            // return !is_null($value) && $value !== '';
            return !is_null($value);
        });
    }

    public function getDataTypes()
    {
        $types = [];
        foreach(get_object_vars($this) as $value){
            if(!is_null($value) && $value !== ''){
                $types[] = match (gettype($value)) {
                    'integer' => '%d',
                    'boolean' => '%d',
                    'string' => '%s',
                    default   => '%s',
                };
            }
        };
        return $types;
    }
}