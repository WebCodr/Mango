<?php

namespace Mango;

/**
 * Class DocumentManager
 * @package Mango
 */

class DocumentManager
{
    private $connection;

    /**
     * Constructor
     *
     * @param Mango $mango
     */

    public function __construct(Mango $mango)
    {
        $this->connection = $mango->getConnection();
        $mango::setDocumentManager($this);
    }

    /**
     * Store a document
     *
     * @param DocumentInterface $document
     */

    public function store(DocumentInterface $document)
    {
        $collection = $this->connection->selectCollection($document::getCollectionName());
        $data = $document->allPrepared()->getArray();
        $collection->save($data);
    }

    /**
     * Remove a document
     *
     * @param DocumentInterface $document
     */

    public function remove(DocumentInterface $document)
    {
        $collection = $this->connection->selectCollection($document::getCollectionName());
        $collection->remove($document->_id);
    }

    /**
     *
     *
     * @param DocumentInterface $document
     * @param $field
     */

    public function index(DocumentInterface $document, $field)
    {
        $collection = $this->connection->selectCollection($document::getCollectionName());
        $collection->ensureIndex($field);
    }

    /**
     * Execute a query on a collection
     *
     * @param $collection
     * @param array $query
     * @param $hydrationClassName
     * @return \Mango\Persistence\Cursor
     */

    public function where($collection, array $query, $hydrationClassName)
    {
        $collection = $this->connection->selectCollection($collection);

        return $collection->where($query, $hydrationClassName);
    }
}