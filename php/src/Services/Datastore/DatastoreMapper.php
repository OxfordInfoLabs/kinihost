<?php

namespace Kinihost\Services\Datastore;

use Kinikit\Core\Binding\ObjectBinder;
use Kinihost\Services\Datastore\DatastoreProvider\DatastoreProvider;
use Kinihost\ValueObjects\Datastore\DatastoreQuery;

/**
 *
 * Public mapper class which uses the configured provider and provides mapping back to classes if required.
 *
 * Class DatastoreMapper
 *
 */
class DatastoreMapper {

    /**
     * @var DatastoreProvider
     */
    private $dataStoreProvider;

    /**
     * @var ObjectBinder
     */
    private $objectBinder;

    /**
     * DatastoreMapper constructor.
     *
     * @param DatastoreProvider $dataStoreProvider
     * @param ObjectBinder $objectBinder
     */
    public function __construct($dataStoreProvider, $objectBinder) {
        $this->dataStoreProvider = $dataStoreProvider;
        $this->objectBinder = $objectBinder;
    }


    /**
     * List all entities contained in this data store.  If the
     * optional prefix is supplied it will only return those
     * starting with the supplied prefix
     *
     * @return mixed
     */
    public function listEntities($prefix = "") {
        return $this->dataStoreProvider->listEntities($prefix);
    }


    /**
     * Delete a whole entity and all it's objects.
     *
     * @return mixed
     */
    public function deleteEntity($entityName) {
        $this->dataStoreProvider->deleteEntity($entityName);
    }


    /**
     * Get a single object by primary key from a stored entity.  If $mapToClass is supplied
     * attempt to map to a class of this type otherwise return an associative array
     *
     * @param string $entityName
     * @param mixed $primaryKey
     * @param string $mapToClass
     *
     * @return mixed
     */
    public function getObject($entityName, $primaryKey, $mapToClass = null) {
        $result = $this->dataStoreProvider->getObject($entityName, $primaryKey);
        if ($mapToClass) {
            $result = $this->objectBinder->bindFromArray($result, $mapToClass, false);
        }
        return $result;
    }


    /**
     * Return an array of objects for the passed query definition.  If $mapToClass is supplied
     * attempt to map to an array of objects of this type otherwise return an array of associative arrays
     * for each item.
     *
     * @param string $entityName
     * @param DatastoreQuery $query
     * @param string $mapToClass
     */
    public function queryForObjects($entityName, $query, $mapToClass = null) {
        $results = $this->dataStoreProvider->listObjects($entityName,
            $query->getFilters(), $query->getOrderings(), $query->getLimit(), $query->getOffset());

        if ($mapToClass) {
            foreach ($results as $index => $result) {
                $results[$index] = $this->objectBinder->bindFromArray($result, $mapToClass, false);
            }
        }

        return $results;
    }

    /**
     * Save an object.  This will do an insert / update automatically.
     * If the primary key is supplied it will be used otherwise an insert is assumed and a key will be auto assigned.
     * The primary key will be returned from this function.
     *
     * Data can be supplied either as an object or as an associative array.
     *
     *
     * @param string $entity
     * @param mixed $data
     * @param mixed $primaryKey
     *
     * @return mixed
     */
    public function saveObject($entity, $data) {

        // If data is an array, send it intact
        if (is_array($data)) {
            return $this->dataStoreProvider->saveObject($entity, $data);
        } else {
            $arrayData = $this->objectBinder->bindToArray($data, false);
            $returnData = $this->dataStoreProvider->saveObject($entity, $arrayData);
            return $this->objectBinder->bindFromArray($returnData, get_class($data), false);
        }

    }


    /**
     * Save multiple objects.
     *
     * The array of data objects can be supplied either as an array of objects or as an array of associative arrays
     * for each object.
     *
     * @param string $entity
     * @param mixed[] $arrayOfDataObjects
     *
     * @return mixed
     */
    public function saveMultipleObjects($entity, $arrayOfDataObjects) {

        $mapResults = [];
        $arrayData = [];
        foreach ($arrayOfDataObjects as $dataObject) {
            if (is_array($dataObject)) {
                $arrayData[] = $dataObject;
                $mapResults[] = false;
            } else {
                $arrayData[] = $this->objectBinder->bindToArray($dataObject, false);
                $mapResults[] = get_class($dataObject);
            }
        }

        $response = $this->dataStoreProvider->saveMultipleObjects($entity, $arrayData);

        // Now map back correctly
        $returnedResults = [];
        foreach ($response as $index => $responseItem) {

            if ($mapResults[$index]) {
                $returnedResults[] = $this->objectBinder->bindFromArray($responseItem, $mapResults[$index], false);
            } else {
                $returnedResults[] = $responseItem;
            }

        }

        return $returnedResults;

    }


    /**
     * Delete an object by primary key
     *
     * Simply forwards to provider
     *
     * @param $entity
     * @param $primaryKey
     * @return mixed
     */
    public function deleteObject($entity, $primaryKey) {
        $this->dataStoreProvider->deleteObject($entity, $primaryKey);
    }


}
