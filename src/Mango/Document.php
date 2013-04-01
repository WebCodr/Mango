<?php

namespace Mango;

use Mango\Type\Date;
use Mango\Type\Id;
use Mango\Type\String;


use Collection\MutableMap;
use Mango\Type\TypeInterface;

/**
 * Trait Document
 * @package Mango
 */

trait Document
{
    private $fields = array();
    private $attributes;

    /**
     * Constructor
     */

    public function __construct(array $attributes = [])
    {
        $this->attributes = new MutableMap();

        // config for id field
        $this->addField('_id', ['type' => 'Id']);
        $this->_id = new Id();

        // call hook method (can be overridden in parent class)
        $this->addFields();

        if (!empty($attributes)) {
            $this->update($attributes);
        }
    }

    /**
     * @param $attribute
     * @return \Collection\MutableMap
     */

    public function __get($attribute)
    {
        return $this->attributes->get($attribute)->getValue();
    }

    /**
     * @param $attribute
     * @param $value
     */

    public function __set($attribute, $value)
    {
        if (!$value instanceof TypeInterface) {
            $type = $this->getFieldConfig($attribute, 'type');
            $value = $this->hydrate($value, $type);
        }

        $this->attributes->set($attribute, $value);
    }

    /**
     * @param $attribute
     * @return bool
     */

    public function __isset($attribute)
    {
        return $this->attributes->has($attribute);
    }

    /**
     * @param $value
     * @param null $type
     * @return Date|String|Id
     */

    private function hydrate($value, $type = null)
    {
        switch ($type) {
            case 'DateTime':
                $value = new Date($value);
                break;

            case 'Id':
                $value = new Id($value);
                break;

            case 'String':
                $value = new String($value);
                break;

            default:
                $value = new String($value);
        }

        return $value;
    }

    /**
     * Get MongoId string
     *
     * @return string
     */

    public function getId()
    {
        return $this->_id;
    }

    /**
     * Store document to MongoDB
     *
     * @return $this
     */

    public function store()
    {
        $this->ensureIndices();
        Mango::getDocumentManager()->store($this);

        return $this;
    }

    /**
     * Remove document from MongoDB
     *
     * @return $this
     */

    public function remove()
    {
        Mango::getDocumentManager()->remove($this);
        $this->reset();

        return $this;
    }

    /**
     * Reset all public document properties
     */

    private function reset()
    {
        foreach ($this->all() as $name => $value) {
            if ($name == '_id') {
                $this->_id = new Id();
            } else {
                $this->{$name} = null;
            }
        }
    }

    /**
     * Get collection name from class name (lower case)
     *
     * @return string
     */

    public static function getCollectionName()
    {
        $name = join('', array_slice(explode('\\', __CLASS__), -1));
        $name = strtolower($name);

        return $name;
    }

    /**
     * Get document(s) by id(s)
     *
     * @return Persistence\Cursor
     * @throws \InvalidArgumentException
     */

    public static function find()
    {
        $args = func_get_args();

        if (empty($args)) {
            throw new \InvalidArgumentException('No Ids given.');
        }

        if (count($args) === 1) {
            $id = current($args);

            return self::where(['_id' => new \MongoId($id)]);
        }

        $ids = [];

        foreach ($args as $id) {
            $ids[] = new \MongoId($id);
        }

        return self::where(['_id' => ['$in' => $ids]]);
    }

    /**
     * Execute query
     *
     * @param array $query
     * @return \Mango\Persistence\Cursor
     */

    public static function where(array $query = [])
    {
        $dm = Mango::getDocumentManager();

        return $dm->where(self::getCollectionName(), $query, __CLASS__);
    }

    /**
     * Hook method for parent class to initialize its field config
     */

    private function addFields()
    {

    }

    /**
     * Add field config
     *
     * @param $field
     * @param array $config
     */

    private function addField($field, $config = [])
    {
        $this->fields[$field] = [
            'name' => $field,
            'config' => $config
        ];
    }

    /**
     * Get field data
     *
     * @param $field
     * @return mixed
     */

    public function getField($field)
    {
        return $this->fields[$field];
    }

    /**
     * Get field config or a specific config property
     *
     * @param $name
     * @param null $attribute
     * @return mixed
     */

    private function getFieldConfig($name, $attribute = null)
    {
        // return the whole config
        if ($attribute === null) {
            return $this->fields[$name]['config'];
        }

        return (isset($this->fields[$name]['config'][$attribute])) ? $this->fields[$name]['config'][$attribute] : null;
    }

    /**
     * Hook method to prepare for storage
     */

    private function prepare()
    {
        return null;
    }

    /**
     * Get all public properties
     *
     * @return MutableMap
     */

    public function all()
    {
        return $this->attributes->all();
    }

    /**
     * Update document properties from given array
     *
     * @param array $attributes
     * @return $this
     */

    public function update(array $attributes = [])
    {
        $this
            ->all()
            ->update($attributes)
            ->each(function($value, $attribute) {
                $this->{$attribute} = $value;
            });

        return $this;
    }

    /**
     * Ensure that indices are set
     */

    private function ensureIndices()
    {
        foreach ($this->all() as $attribute => $value) {
            if ($this->getFieldConfig($attribute, 'index') === true) {
                Mango::getDocumentManager()->index($this, $attribute);
            }
        }
    }

    /**
     * Get all public properties and dehydrates them for storage
     *
     * @return MutableMap
     */

    public function allPrepared()
    {
        $attributes = new MutableMap();
        $this->prepare();

        $this->all()->each(function($value, $attribute) use($attributes) {
            $attributes->set($attribute, $value->getMongoType());
        });

        return $attributes;
    }
}