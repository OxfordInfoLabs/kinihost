<?php

namespace Kinihost\Services\Datastore\DatastoreProvider;


use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinihost\ValueObjects\Datastore\DatastoreFilter;
use Kinihost\ValueObjects\Datastore\DatastoreOrdering;
use Kinihost\TestBase;

include_once "autoloader.php";

class FirestoreDatastoreProviderTest extends TestBase {

    /**
     * @var FirestoreDatastoreProvider
     */
    private $dataStoreProvider;


    /**
     * @var FirestoreClient
     */
    private $firestoreClient;

    public function setUp(): void {
        $this->dataStoreProvider = new FirestoreDatastoreProvider();
        $this->firestoreClient = new FirestoreClient([
            'keyFilePath' => Configuration::readParameter("google.keyfile.path")
        ]);

        /**
         * Scrub all documents
         *
         * @var DocumentReference $document
         */
        foreach ($this->firestoreClient->collection("unit-tests")->listDocuments() as $document) {
            $document->delete();
        }

        foreach ($this->firestoreClient->collection("unit\\tests")->listDocuments() as $document) {
            $document->delete();
        }

    }


    public function testCanCreateRetrieveUpdateAndDeleteObjectsWithAutoPrimaryKeys() {

        $savedData = $this->dataStoreProvider->saveObject("unit-tests",
            [
                "name" => "Marko Polo",
                "age" => 30,
                "phone" => "07595 111111"
            ]);


        $this->assertNotNull($savedData["id"]);

        $primaryKey = $savedData["id"];

        $data = $this->firestoreClient->collection("unit-tests")->document($primaryKey)->snapshot();

        $this->assertEquals([
            "name" => "Marko Polo",
            "age" => 30,
            "phone" => "07595 111111"
        ], $data->data());


        $this->dataStoreProvider->saveObject("unit-tests", [
            "id" => $primaryKey,
            "name" => "Marko Polo",
            "age" => 55,
            "phone" => "07822 323232"
        ]);


        $data = $this->firestoreClient->collection("unit-tests")->document($primaryKey)->snapshot();

        $this->assertEquals([
            "name" => "Marko Polo",
            "age" => 55,
            "phone" => "07822 323232"
        ], $data->data());


        $reObject = $this->dataStoreProvider->getObject("unit-tests", $primaryKey);

        $this->assertEquals([
            "name" => "Marko Polo",
            "age" => 55,
            "phone" => "07822 323232",
            "id" => $primaryKey
        ], $reObject);


        // Delete object
        $this->dataStoreProvider->deleteObject("unit-tests", $primaryKey);

        // Check for failure here
        try {
            $this->dataStoreProvider->getObject("unit-tests", $primaryKey);
            $this->fail("Should have thrown here");
        } catch (ItemNotFoundException $e) {
            // Success
        }

        // Allow for silent delete failure
        $this->dataStoreProvider->deleteObject("unit-tests", "non-existent");

    }


    public function testCanCreateUpdateRetrieveAndDeleteObjectsWithExplicitPrimaryKeys() {

        $savedData = $this->dataStoreProvider->saveObject("unit-tests",
            [
                "id" => "PICKLE",
                "name" => "Marky Babes",
                "age" => 44,
                "phone" => "01865 788898"
            ]);


        $data = $this->firestoreClient->collection("unit-tests")->document("PICKLE")->snapshot();

        $this->assertEquals([
            "name" => "Marky Babes",
            "age" => 44,
            "phone" => "01865 788898"
        ], $data->data());

        $savedData["age"] = 23;
        $savedData["phone"] = "01223 355355";

        $this->dataStoreProvider->saveObject("unit-tests", $savedData);

        $data = $this->firestoreClient->collection("unit-tests")->document("PICKLE")->snapshot();

        $this->assertEquals([
            "name" => "Marky Babes",
            "age" => 23,
            "phone" => "01223 355355"
        ], $data->data());

        $reObject = $this->dataStoreProvider->getObject("unit-tests", "PICKLE");

        $this->assertEquals([
            "name" => "Marky Babes",
            "age" => 23,
            "phone" => "01223 355355",
            "id" => "PICKLE"
        ], $reObject);

        $this->dataStoreProvider->deleteObject("unit-tests", "PICKLE");

        // Check for failure here
        try {
            $this->dataStoreProvider->getObject("unit-tests", "PICKLE");
            $this->fail("Should have thrown here");
        } catch (ItemNotFoundException $e) {
            // Success
        }

    }


    public function testCanSaveMultipleItems() {


        $savedData = $this->dataStoreProvider->saveMultipleObjects("unit-tests",
            [
                [
                    "id" => "PICKLE",
                    "name" => "Marky Babes",
                    "age" => 44,
                    "phone" => "01865 788898"
                ],
                [
                    "name" => "Testing 1,2,3",
                    "age" => 23,
                    "phone" => "07565 787878"
                ],
                [
                    "name" => "Trials and temptations",
                    "age" => 66,
                    "phone" => "01777 878788"
                ]
            ]

        );


        $this->assertEquals(3, sizeof($savedData));
        $this->assertEquals("PICKLE", $savedData[0]["id"]);

        $secondId = $savedData[1]["id"];
        $thirdId = $savedData[2]["id"];
        $this->assertNotNull($secondId);
        $this->assertNotNull($thirdId);

        $this->assertEquals([
            "id" => "PICKLE",
            "name" => "Marky Babes",
            "age" => 44,
            "phone" => "01865 788898"
        ], $savedData[0]);

        $this->assertEquals([
            "name" => "Testing 1,2,3",
            "age" => 23,
            "phone" => "07565 787878",
            "id" => $secondId
        ], $savedData[1]);

        $this->assertEquals([
            "name" => "Trials and temptations",
            "age" => 66,
            "phone" => "01777 878788",
            "id" => $thirdId
        ], $savedData[2]);

    }


    public function testCanStoreNestedObjects() {

        $savedData = $this->dataStoreProvider->saveObject("unit-tests", [
            "name" => "Jamie Smith",
            "age" => 33,
            "address" => [
                "street" => "50 My Lane",
                "city" => "London",
                "postcode" => "NW1 6RE",
                "country" => [
                    "code" => "GB",
                    "name" => "United Kingdom"
                ]
            ]
        ]);

        $reData = $this->dataStoreProvider->getObject("unit-tests", $savedData["id"]);

        $this->assertEquals([
            "id" => $savedData["id"],
            "name" => "Jamie Smith",
            "age" => 33,
            "address" => [
                "street" => "50 My Lane",
                "city" => "London",
                "postcode" => "NW1 6RE",
                "country" => [
                    "code" => "GB",
                    "name" => "United Kingdom"
                ]
            ]], $reData);


    }

    public function testCanListFilterSortAndLimitObjects() {

        $item1 = [
            "id" => "ITEM1",
            "name" => "Marky Babes",
            "age" => 44,
            "phone" => "01865 788898",
            "department" => "HR",
            "skills" => [
                "Payroll",
                "Finance",
                "Admin"
            ]
        ];

        $item2 = [
            "id" => "ITEM2",
            "name" => "Jane Smith",
            "age" => 23,
            "department" => "HR",
            "phone" => "07565 787878",
            "skills" => [
                "Finance",
                "Admin",
                "Book Keeping"
            ]
        ];

        $item3 = [
            "id" => "ITEM3",
            "name" => "James Brown",
            "age" => 66,
            "department" => "Support",
            "phone" => "01777 878788",
            "skills" => [
                "Admin",
                "Phone Answering"
            ]
        ];

        $item4 = [
            "id" => "ITEM4",
            "name" => "Joe Bloggs",
            "age" => 77,
            "department" => "Tech",
            "phone" => "09676 878788",
            "skills" => [
                "Finance",
                "Project Management"
            ]
        ];


        $this->dataStoreProvider->saveMultipleObjects("unit-tests",
            [
                $item1,
                $item2,
                $item3,
                $item4
            ]

        );

        // Full list
        $fullList = $this->dataStoreProvider->listObjects("unit-tests");
        $this->assertEquals([$item1, $item2, $item3, $item4], $fullList);

        // Ordered lists
        $orderedList = $this->dataStoreProvider->listObjects("unit-tests", [], [
            new DatastoreOrdering("name")
        ]);
        $this->assertEquals([$item3, $item2, $item4, $item1], $orderedList);


        $orderedList = $this->dataStoreProvider->listObjects("unit-tests", [], [
            new DatastoreOrdering("name", DatastoreOrdering::DESCENDING)
        ]);
        $this->assertEquals([$item1, $item4, $item2, $item3], $orderedList);


        // Limited results
        $limitedResults = $this->dataStoreProvider->listObjects("unit-tests", [], [], 2);
        $this->assertEquals([$item1, $item2], $limitedResults);

        $limitedResults = $this->dataStoreProvider->listObjects("unit-tests", [], [], 2, 2);
        $this->assertEquals([$item3, $item4], $limitedResults);


        // Filtered results
        $filteredResults = $this->dataStoreProvider->listObjects("unit-tests", [
            new DatastoreFilter("department", "HR")
        ]);
        $this->assertEquals([$item1, $item2], $filteredResults);


        $filteredResults = $this->dataStoreProvider->listObjects("unit-tests", [
            new DatastoreFilter("age", 50, DatastoreFilter::GREATER_THAN)]);
        $this->assertEquals([$item3, $item4], $filteredResults);


        // Hybrid filter,sort,limit,offset
        $filteredResults = $this->dataStoreProvider->listObjects("unit-tests",
            [new DatastoreFilter("skills", "Admin", DatastoreFilter::CONTAINS)],
            [new DatastoreOrdering("age")], 2, 1);

        $this->assertEquals([$item1, $item3], $filteredResults);

    }


    public function testCanCreateListAndUpdateItemsWithForwardSlashPrimaryKeysSeemlessly() {

        $savedData = $this->dataStoreProvider->saveObject("unit/tests",
            [
                "id" => "/help/test",
                "name" => "Marko Polo",
                "age" => 30,
                "phone" => "07595 111111"
            ]);

        $this->assertEquals("/help/test", $savedData["id"]);

        // Now retrieve the item by pk
        $retrieved = $this->dataStoreProvider->getObject("unit/tests", "/help/test");
        $this->assertEquals($savedData, $retrieved);

        // Listings
        $list = $this->dataStoreProvider->listObjects("unit/tests");
        $this->assertEquals($savedData, $list[0]);

        // Delete by pk
        $this->dataStoreProvider->deleteObject("unit/tests", "/help/test");

        try {
            $this->dataStoreProvider->getObject("unit/tests", "/help/test");
            $this->fail("Should have failed here");
        } catch (ItemNotFoundException $e) {
            // As expected
        }
    }


    public function testCanListAllEntities() {

        $this->dataStoreProvider->saveObject("unit-tests",
            [
                "id" => "PICKLE",
                "name" => "Marky Babes",
                "age" => 44,
                "phone" => "01865 788898"
            ]);


        // List all entities
        $entities = $this->dataStoreProvider->listEntities();

        $this->assertTrue(sizeof($entities) > 0);
        $this->assertTrue(is_numeric(array_search("unit-tests", $entities)));

    }


    public function testCanListAllEntitiesWithPrefix() {
        $this->dataStoreProvider->saveObject("unit/tests",
            [
                "id" => "PICKLE",
                "name" => "Marky Babes",
                "age" => 44,
                "phone" => "01865 788898"
            ]);


        // List all entities
        $entities = $this->dataStoreProvider->listEntities("unit/");

        $this->assertEquals(1, sizeof($entities));
        $this->assertEquals("unit/tests", $entities[0]);

    }


    public function testCanDeleteEntity() {

        $this->dataStoreProvider->saveObject("unit/tests",
            [
                "id" => "PICKLE",
                "name" => "Marky Babes",
                "age" => 44,
                "phone" => "01865 788898"
            ]);


        $this->dataStoreProvider->saveObject("unit/tests",
            [
                "id" => "PICKLE2",
                "name" => "Marky Babes",
                "age" => 44,
                "phone" => "01865 788898"
            ]);

        $this->assertEquals(2, $this->firestoreClient->collection("unit\\tests")->documents()->size());


        $this->dataStoreProvider->deleteEntity("unit/tests");
        $this->assertEquals(0, sizeof($this->dataStoreProvider->listEntities("unit/")));


    }


    public function testSubCollectionsCanBeReferencedByUsingDoubleColonSeparationInEntityName() {

        $savedData = $this->dataStoreProvider->saveObject("unit-testing::subdocument::unit/tests",
            [
                "id" => "/help/test",
                "name" => "Marko Polo",
                "age" => 30,
                "phone" => "07595 111111"
            ]);

        $this->assertEquals("/help/test", $savedData["id"]);


        // Check saved correctly
        $data = $this->firestoreClient->collection("unit-testing")->document("subdocument")->collection("unit\\tests")
            ->document("\\help\\test")->snapshot();

        $this->assertEquals([
            "name" => "Marko Polo",
            "age" => 30,
            "phone" => "07595 111111"
        ], $data->data());


        // Now retrieve the item by pk
        $retrieved = $this->dataStoreProvider->getObject("unit-testing::subdocument::unit/tests", "/help/test");
        $this->assertEquals($savedData, $retrieved);

        // Listings
        $list = $this->dataStoreProvider->listObjects("unit-testing::subdocument::unit/tests");
        $this->assertEquals($savedData, $list[0]);

        // Delete by pk
        $this->dataStoreProvider->deleteObject("unit-testing::subdocument::unit/tests", "/help/test");

        try {
            $this->dataStoreProvider->getObject("unit-testing::subdocument::unit/tests", "/help/test");
            $this->fail("Should have failed here");
        } catch (ItemNotFoundException $e) {
            // As expected
        }


    }


}
