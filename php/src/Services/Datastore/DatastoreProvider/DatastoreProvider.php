<?php

namespace Kinihost\Services\Datastore\DatastoreProvider;

use Kinihost\ValueObjects\Datastore\DatastoreFilter;
use Kinihost\ValueObjects\Datastore\DatastoreOrdering;

/**
 * Interface DatastoreProvider
 *
 * Encode interface for the storage of flexible object data in NoSQL engines e.g. Google Firestore using the usual CRUD
 * operations.
 *
 *
 * @implementation firestore Kinihost\Services\Datastore\DatastoreProvider\FirestoreDatastoreProvider
 * @defaultImplementation Kinihost\Services\Datastore\DatastoreProvider\FirestoreDatastoreProvider
 */
interface DatastoreProvider {


    /**
     * List all entities provided by this data store.  If the
     * optional prefix is supplied it will only return those
     * starting with the supplied prefix
     *
     * @return mixed
     */
    public function listEntities($prefix = "");


    /**
     * Delete a whole entity and all it's objects.
     *
     * @return mixed
     */
    public function deleteEntity($entity);


    /**
     * Get a single object by primary key - returns an associative array of key value pairs
     *
     * @param string $entity
     * @param mixed $primaryKey
     *
     * @return array
     */
    public function getObject($entity, $primaryKey);


    /**
     * List objects, optionally filtering, ordering and limiting the set.
     * Returns an array of objects indexed by primary key.
     *
     * @param $entity
     * @param DatastoreFilter[] $filters
     * @param DatastoreOrdering[] $orderings
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function listObjects($entity, $filters = [], $orderings = [], $limit = -1, $offset = 0);


    /**
     * Save an object.  This will do an insert / update automatically.
     * The primary key is assumed to be the "id" member and if blank an auto key should be generated.
     * The saved object will be returned from this function.
     *
     * @param string $entity
     * @param array $data
     * @param mixed $primaryKey
     *
     * @return mixed
     */
    public function saveObject($entity, $data);


    /**
     * Save multiple objects.  In this case we would expect the data to be an array of objects.
     *
     * @param $entity
     * @param $arrayOfDataObjects
     *
     * @return mixed
     */
    public function saveMultipleObjects($entity, $arrayOfDataObjects);

    /**
     * Delete an object
     *
     * @param $entity
     * @param $primaryKey
     * @return mixed
     */
    public function deleteObject($entity, $primaryKey);


}
