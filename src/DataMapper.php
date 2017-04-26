<?php
namespace vakata\orm;

interface DataMapper
{
    /**
     * Get an entity from an array of fields
     *
     * @param array $row
     * @return mixed
     */
    public function entity(array $row);
    /**
     * Insert an entity, returning the primary key fields and their value
     *
     * @param mixed $entity
     * @return array a key value map of the primary key columns
     */
    public function insert($entity) : array;
    /**
     * Update an entity
     *
     * @param mixed $entity
     * @return array a key value map of the primary key columns
     */
    public function update($entity) : array;
    /**
     * Delete an entity
     *
     * @param mixed $entity
     * @return array a key value map of the primary key columns
     */
    public function delete($entity) : array;
    /**
     * Convert an entity to an array of fields, optionally including relation fields. 
     *
     * @param mixed $entity the entity to convert
     * @param bool $relations should the 1 end of relations be included, defaults to `true`
     * @return array
     */
    public function toArray($entity) : array;
}