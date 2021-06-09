<?php

namespace Kinihost\Services\Datastore;

include_once "autoloader.php";

use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\Services\Datastore\DatastoreProvider\DatastoreProvider;
use Kinihost\ValueObjects\Datastore\DatastoreFilter;
use Kinihost\ValueObjects\Datastore\DatastoreOrdering;
use Kinihost\ValueObjects\Datastore\DatastoreQuery;
use Kinihost\TestBase;

class DatastoreMapperTest extends TestBase {

    /**
     * @var DatastoreMapper
     */
    private $mapper;


    /**
     * @var MockObject
     */
    private $mockProvider;


    public function setUp(): void {

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $this->mockProvider = $mockObjectProvider->getMockInstance(DatastoreProvider::class);
        $this->mapper = new DatastoreMapper($this->mockProvider, Container::instance()->get(ObjectBinder::class));


    }


    public function testDeleteObjectSimplyPassesThroughToProvider() {

        $this->mapper->deleteObject("bingo", 44);
        $this->assertTrue($this->mockProvider->methodWasCalled("deleteObject", ["bingo", 44]));

    }


    public function testSaveObjectPassesThroughArrayDataIntact() {

        $data = [
            "id" => 3,
            "name" => "Pete Jones",
            "phone" => "07878 899899",
            "address" => "The Manor House, Great Milton"
        ];

        $this->mockProvider->returnValue("saveObject", $data, ["bingo", $data]);

        $response = $this->mapper->saveObject("bingo", $data);
        $this->assertEquals($data, $response);

        $this->assertTrue($this->mockProvider->methodWasCalled("saveObject", ["bingo", $data]));


    }


    public function testSaveObjectConvertsObjectsToArraysAndPassesThese() {
        $exampleObject = new ExampleObject(22, "Mark Jones", "07676 878778", "3 My Lane, Oxford");

        $expectedData = [
            "id" => 22,
            "name" => "Mark Jones",
            "phone" => "07676 878778",
            "address" => "3 My Lane, Oxford"
        ];

        $this->mockProvider->returnValue("saveObject", $expectedData, ["bingo", $expectedData]);

        $response = $this->mapper->saveObject("bingo", $exampleObject);
        $this->assertEquals($exampleObject, $response);

        $this->assertTrue($this->mockProvider->methodWasCalled("saveObject", ["bingo", $expectedData]));


    }


    public function testSaveMultipleObjectsPassesThroughArrayDataIntact() {
        $data =
            [
                [
                    "id" => 3,
                    "name" => "Pete Jones",
                    "phone" => "07878 899899",
                    "address" => "The Manor House, Great Milton"
                ],
                [
                    "id" => 4,
                    "name" => "Mark Jones",
                    "phone" => "01111 899899",
                    "address" => "Buckingham Palace"
                ]];

        $this->mockProvider->returnValue("saveMultipleObjects", $data, ["bingo", $data]);

        $response = $this->mapper->saveMultipleObjects("bingo", $data);
        $this->assertEquals($data, $response);

        $this->assertTrue($this->mockProvider->methodWasCalled("saveMultipleObjects", ["bingo", $data]));


    }

    public function testSaveMultipleObjectsMapsArraysOfObjectsCorrectly() {

        $data =
            [
                new ExampleObject(1, "Piggy Bell", "01123 345454", "The Old Vicarage"),
                new ExampleObject(2, "Taco Jimmy", "07565 123456", "The Old Bank")
            ];

        $expectedData = [
            [
                "id" => 1,
                "name" => "Piggy Bell",
                "phone" => "01123 345454",
                "address" => "The Old Vicarage"
            ],
            [
                "id" => 2,
                "name" => "Taco Jimmy",
                "phone" => "07565 123456",
                "address" => "The Old Bank"
            ]
        ];


        $this->mockProvider->returnValue("saveMultipleObjects", $expectedData, ["bingo", $expectedData]);

        $response = $this->mapper->saveMultipleObjects("bingo", $data);
        $this->assertEquals($data, $response);

        $this->assertTrue($this->mockProvider->methodWasCalled("saveMultipleObjects", ["bingo", $expectedData]));


    }


    public function testGetObjectReturnsMappedObjectIfClassPassed() {

        $data = [
            "id" => 44,
            "name" => "Simon Smith",
            "phone" => "06767 879989",
            "address" => "3 Spring Lane"
        ];

        $this->mockProvider->returnValue("getObject", $data, ["bingo", 44]);


        $arrayResult = $this->mapper->getObject("bingo", 44);
        $this->assertEquals($data, $arrayResult);
        $this->assertTrue($this->mockProvider->methodWasCalled("getObject", ["bingo", 44]));

        $this->mockProvider->resetMethodCallHistory("getObject");

        $objectResult = $this->mapper->getObject("bingo", 44, ExampleObject::class);
        $this->assertEquals(new ExampleObject(44, "Simon Smith", "06767 879989", "3 Spring Lane"), $objectResult);
        $this->assertTrue($this->mockProvider->methodWasCalled("getObject", ["bingo", 44]));

    }


    public function testResultsFromQueriesAreMappedToObjectsIfClassPassed() {

        $data =
            [
                [
                    "id" => 44,
                    "name" => "Simon Smith",
                    "phone" => "06767 879989",
                    "address" => "3 Spring Lane"
                ], [
                "id" => 55,
                "name" => "John Brown",
                "phone" => "01223 879989",
                "address" => "The Lodge"
            ], [
                "id" => 66,
                "name" => "James White",
                "phone" => "05656 879989",
                "address" => "Peterhouse College"
            ]

            ];


        $query = new DatastoreQuery([new DatastoreFilter("name","test")], [new DatastoreOrdering("address")], 20, 3);
        $this->mockProvider->returnValue("listObjects", $data, ["bingo", [new DatastoreFilter("name","test")], [new DatastoreOrdering("address")], 20, 3]);

        $arrayResults = $this->mapper->queryForObjects("bingo", $query);
        $this->assertEquals($data, $arrayResults);
        $this->assertTrue($this->mockProvider->methodWasCalled("listObjects",["bingo", [new DatastoreFilter("name","test")], [new DatastoreOrdering("address")], 20, 3]));

        $this->mockProvider->resetMethodCallHistory("listObjects");

        $objectResults = $this->mapper->queryForObjects("bingo", $query, ExampleObject::class);
        $this->assertEquals([
            new ExampleObject(44, "Simon Smith", "06767 879989", "3 Spring Lane"),
            new ExampleObject(55, "John Brown", "01223 879989", "The Lodge"),
            new ExampleObject(66, "James White", "05656 879989", "Peterhouse College")
        ], $objectResults);
        $this->assertTrue($this->mockProvider->methodWasCalled("listObjects",["bingo", [new DatastoreFilter("name","test")], [new DatastoreOrdering("address")], 20, 3]));




    }


}
