<?php

namespace Mango;

use Collection\MutableMap;

trait Document
{
    protected $database;
    protected $fields = array();
    public $_id;

    public function __construct()
    {
        $this->_id = new \MongoId();
    }

    public static function getCollectionName()
    {
        $name = join('', array_slice(explode('\\', __CLASS__), -1));
        $name = strtolower($name);

        return $name;
    }

    public function getProperties()
    {
        $reflectionClass = new \ReflectionClass($this);
        $properties = new MutableMap();

        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->name;
            $properties->setProperty($name, $this->{$name});
        }

        return $properties;
    }
}