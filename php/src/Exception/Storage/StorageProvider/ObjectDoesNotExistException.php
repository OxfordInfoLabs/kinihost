<?php


namespace Kinihost\Exception\Storage\StorageProvider;


use Kinikit\Core\Exception\ItemNotFoundException;

/**
 * Exception raised if attempt to access container which does not exist.
 *
 * Class ContainerDoesNotExistException
 * @package Kinihost\Exception\StorageProvider
 */
class ObjectDoesNotExistException extends ItemNotFoundException {

    public function __construct($containerKey, $objectKey) {
        parent::__construct("The object does not exist in the container $containerKey for the supplied key $objectKey");
    }

}
