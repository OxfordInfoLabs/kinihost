<?php


namespace Kinihost\Services\Datastore;


class ExampleObject {

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var string
     */
    private $address;

    /**
     * ExampleObject constructor.
     *
     * @param int $id
     * @param string $name
     * @param string $phone
     * @param string $address
     */
    public function __construct($id, $name, $phone, $address) {
        $this->id = $id;
        $this->name = $name;
        $this->phone = $phone;
        $this->address = $address;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPhone() {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }





}
