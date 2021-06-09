<?php

namespace Kinihost\Services\Datastore\DatastoreProvider;

use Google\Cloud\Firestore\FirestoreClient;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinihost\ValueObjects\Datastore\DatastoreFilter;
use Kinihost\ValueObjects\Datastore\DatastoreOrdering;

class FirestoreDatastoreProvider implements DatastoreProvider {


    /**
     * @var FirestoreClient
     */
    private $firestoreClient;


    public function __construct() {
        $this->firestoreClient = new FirestoreClient([
            'keyFilePath' => Configuration::readParameter("google.keyfile.path")
        ]);
    }


    /**
     * List all entities contained in this data store.  If the
     * optional prefix is supplied it will only return those
     * starting with the supplied prefix
     *
     * @return mixed
     */
    public function listEntities($prefix = "") {

        $prefix = $this->slashEncode($prefix);

        $entities = [];
        foreach ($this->firestoreClient->collections() as $collection) {
            if (!$prefix || substr($collection->path(), 0, strlen($prefix)) == $prefix)
                $entities[] = $this->slashDecode($collection->path());
        }

        return $entities;
    }


    /**
     * Delete a whole entity and all it's objects.
     *
     * @return mixed
     */
    public function deleteEntity($entity) {

        $entity = $this->slashEncode($entity);

        foreach ($this->firestoreClient->collection($entity)->listDocuments() as $document) {
            $document->delete();
        }
    }


    /**
     * Get a single object by primary key - returns an associative array of key value pairs
     *
     * @param string $entity
     * @param mixed $primaryKey
     *
     * @return array
     */
    public function getObject($entity, $primaryKey) {
        $entity = $this->slashEncode($entity);
        $primaryKey = $this->slashEncode($primaryKey);

        $document = $this->firestoreClient->collection($entity)->document($primaryKey)->snapshot();

        if ($document->exists()) {
            return array_merge($document->data(), ["id" => $this->slashDecode($primaryKey)]);
        } else {
            throw new ItemNotFoundException("The object does not exist for supplied primary key");
        }


    }

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
    public function listObjects($entity, $filters = [], $orderings = [], $limit = -1, $offset = 0) {
        $entity = $this->slashEncode($entity);
        $list = $this->firestoreClient->collection($entity);

        // Add any filterings
        if ($filters) {
            foreach ($filters as $filter) {

                $filterType = $filter->getFilterType();
                switch ($filter->getFilterType()) {
                    case DatastoreFilter::CONTAINS:
                        $filterType = "array-contains";
                        break;
                }

                $list = $list->where($filter->getFilterMember(), $filterType, $filter->getFilterValue());
            }
        }

        // Add any orderings
        if ($orderings) {
            foreach ($orderings as $ordering) {
                $list = $list->orderBy($ordering->getField(), $ordering->getDirection());
            }
        }

        // Add a limit if applicable
        if ($limit > -1) {
            $list = $list->limit($limit);
        }

        // Add an offset if applicable
        if ($offset > 0) {
            $list = $list->offset($offset);
        }

        // Grab the documents
        $list = $list->documents();

        /**
         * Get the document data for each list item
         */
        $returnItems = [];
        foreach ($list as $listItem) {
            $data = $listItem->data();
            $data["id"] = $this->slashDecode($listItem->id());
            $returnItems[] = $data;
        }

        return $returnItems;

    }

    /**
     * Save an object.  This will do an insert / update automatically.
     * If the primary key is supplied it will be used otherwise an insert is assumed and a key will be auto assigned.
     * The primary key will be returned from this function.
     *
     * @param string $entity
     * @param array $data
     * @param mixed $primaryKey
     *
     * @return mixed
     */
    public function saveObject($entity, $data) {

        // Encode slashes on the entity
        $entity = $this->slashEncode($entity);

        $primaryKey = $data["id"] ?? null;

        if ($primaryKey) {
            unset($data["id"]);
            $primaryKey = $this->slashEncode($primaryKey);
        }

        if ($primaryKey) {
            $document = $this->firestoreClient->collection($entity)->document($primaryKey);
        } else {
            $document = $this->firestoreClient->collection($entity)->newDocument();
        }

        $document->set($data);

        // Return the document id
        return array_merge($data, ["id" => $this->slashDecode($document->id())]);

    }


    /**
     * Save multiple objects.  In this case we would expect the data to be an array of objects.
     *
     * @param $entity
     * @param $arrayOfDataObjects
     *
     * @return mixed
     */
    public function saveMultipleObjects($entity, $arrayOfDataObjects) {

        // Save each object in turn
        foreach ($arrayOfDataObjects as $index => $dataObject) {
            $arrayOfDataObjects[$index] = $this->saveObject($entity, $dataObject);
        }

        // Return the objects again
        return $arrayOfDataObjects;
    }


    /**
     * Delete an object
     *
     * @param $entity
     * @param $primaryKey
     * @return mixed
     */
    public function deleteObject($entity, $primaryKey) {
        $entity = $this->slashEncode($entity);
        $item = $this->firestoreClient->collection($entity)->document($this->slashEncode($primaryKey));
        $item->delete();
    }


    // Encode slashes
    private function slashEncode($value) {
        $encoded = str_replace("/", "\\", $value);
        $encoded = str_replace("::", "/", $encoded);
        return $encoded;
    }

    // Decode slashes
    private function slashDecode($value) {
        return str_replace("\\", "/", $value);
    }

}
