<?php


namespace Kinihost\Exception\Storage\StorageProvider;


use Kinikit\Core\Exception\ItemNotFoundException;

/**
 * Exception raised if attempt to access container which does not exist.
 *
 * Class ContainerDoesNotExistException
 * @package Kinihost\Exception\StorageProvider
 */
class ContainerDoesNotExistException extends ItemNotFoundException {

    public function __construct($containerKey) {
        parent::__construct("The container does not exist for the supplied key $containerKey");
    }

}
