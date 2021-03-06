<?php

namespace Mango\Persistence;

use Collection\MutableMap;

/**
 * Class Cursor
 * @package Mango\Persistence
 */

class Cursor implements \IteratorAggregate, \Countable
{
    private $cursor;
    private $hydrate = true;
    private $hydrationClassName;

    /**
     * Constructor
     *
     * @param \MongoCursor $cursor
     * @param $hydrationClassName
     * @param bool $hydrate
     */

    public function __construct(\MongoCursor $cursor, $hydrationClassName, $hydrate = true)
    {
        $this->cursor = $cursor;
        $this->hydrationClassName = $hydrationClassName;
        $this->hydrate = $hydrate;
    }

    /**
     * Decide if a method call is on a result or cursor and execute the call
     *
     * Yeah, magic methods like these are ugly, but there is no other way?
     *
     * @param $method
     * @param $arguments
     * @return mixed
     * @throws \BadMethodCallException
     */

    public function __call($method, $arguments)
    {
        $object = $this->getDocuments();

        if (!method_exists($object, $method)) {
            throw new \BadMethodCallException("Method '{$$method}' does not exist.");
        }

        return call_user_func_array(
            [$object, $method],
            $arguments
        );
    }

    /**
     * Get the document count on the cursor
     *
     * @return int
     */

    public function count()
    {
        return $this->cursor->count();
    }

    /**
     * Set a limit
     *
     * @param $limit
     * @return $this
     */

    public function limit($limit)
    {
        $this->cursor->limit($limit);

        return $this;
    }

    /**
     * Skip # documents
     *
     * @param $skip
     * @return $this
     */

    public function skip($skip)
    {
        $this->cursor->skip($skip);

        return $this;
    }

    /**
     * Set sorting
     *
     * @param array $fields
     * @return $this
     */

    public function sort(array $fields)
    {
        $this->cursor->sort($fields);

        return $this;
    }

    /**
     * Get documents from the cursor as array or hydrated document objects
     *
     * @return MutableMap
     */

    private function getDocuments()
    {
        $data = [];

        foreach ($this->cursor as $document) {
            if ($this->hydrate === true) {
                $data[] = new $this->hydrationClassName($document);
            } else {
                $data[] = $document;
            }
        }

        return new MutableMap($data);
    }

    /**
     * Get an Iterator
     *
     * @return \ArrayIterator|\Traversable
     */

    public function getIterator()
    {
        return $this->getDocuments()->getIterator();
    }
}