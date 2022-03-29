<?php


namespace Kinihost\Services\Workflow\QueuedTask\Processor;

use Google\Cloud\Tasks\V2\AppEngineHttpRequest;
use Google\Cloud\Tasks\V2\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\Queue;
use Google\Cloud\Tasks\V2\Task;
use Google\Protobuf\Timestamp;
use Kiniauth\Services\Workflow\Task\Queued\Processor\QueuedTaskProcessor;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Core\Configuration\Configuration;

/**
 * Task processor using the Google Cloud Task Queue
 *
 * Class GoogleQueuedTaskProcessor
 * @package Kiniauth\Services\Workflow\QueuedTask\Processor
 */
class GoogleCloudQueuedTaskProcessor implements QueuedTaskProcessor {

    /**
     * @var string
     */
    private $projectId;


    /**
     * @var string
     */
    private $region;

    /**
     * @var CloudTasksClient
     */
    private $cloudTasksClient;

    /**
     *
     * Construct and create the task API
     *
     * GoogleCloudQueuedTaskProcessor constructor.
     */
    public function __construct($projectId = null, $region = null, $keyFilePath = null) {
        $this->projectId = $projectId ?? Configuration::readParameter("google.project.id");
        $this->region = $region ?? Configuration::readParameter("google.bucket.region");
        $keyFilePath = $keyFilePath ?? Configuration::readParameter("google.keyfile.path");

        $this->cloudTasksClient = new CloudTasksClient([
            "credentials" => $keyFilePath]);
    }

    /**
     * Queue a task for a specific queue using the specified task identifier and
     * optional configuration.  Returns a string instance identifier
     * for this task if successful or should throw on failures.
     *
     * @param string $identifier
     * @param string[string] $configuration
     * @parm return string
     */
    public function queueTask($queueName, $taskIdentifier, $description, $configuration = [], $startTime = null) {
        $queue = $this->cloudTasksClient->queueName($this->projectId, $this->region, $queueName);

        $httpRequest = new AppEngineHttpRequest();
        $httpRequest->setRelativeUri("/external/google/task");
        $httpRequest->setHttpMethod(HttpMethod::POST);
        $httpRequest->setHeaders([
            "content-type" => "application/json"
        ]);
        $httpRequest->setBody(json_encode([
            "taskIdentifier" => $taskIdentifier,
            "description" => $description,
            "configuration" => $configuration
        ]));

        $task = new Task();
        $task->setAppEngineHttpRequest($httpRequest);

        // If a start time has been passed, set it.
        if ($startTime) {
            $timestamp = new Timestamp();
            $timestamp->fromDateTime($startTime);
            $task->setScheduleTime($timestamp);
        }

        $task = $this->cloudTasksClient->createTask($queue, $task);


        return $task->getName();
    }


    /**
     * Get a single task by queue name and task instance identifier.
     *
     * @param $queueName
     * @param $taskInstanceIdentifier
     * @return QueueItem
     */
    public function getTask($queueName, $taskInstanceIdentifier) {
        $item = $this->cloudTasksClient->getTask($taskInstanceIdentifier, ["responseView" => Task\View::FULL]);
        $payload = json_decode($item->getAppEngineHttpRequest()->getBody(), true);
        return new QueueItem($queueName, $item->getName(), $payload["taskIdentifier"], $payload["description"], $item->getCreateTime()->toDateTime(),
            QueueItem::STATUS_PENDING, $payload["configuration"], $item->getScheduleTime()->toDateTime());
    }


    /**
     * De-queue a task using the task instance identifier
     *
     * @param $taskInstanceIdentifier
     * @return mixed
     */
    public function deQueueTask($queueName, $taskInstanceIdentifier) {
        try {
            $this->cloudTasksClient->deleteTask($taskInstanceIdentifier);
        } catch (\Exception $e) {
            // OK
        }
    }


    /**
     * List all queued tasks for a queue.
     *
     * @param string $queueName
     * @return mixed
     */
    public function listQueuedTasks($queueName) {

        $queue = $this->cloudTasksClient->queueName($this->projectId, $this->region, $queueName);

        $items = [];
        foreach ($this->cloudTasksClient->listTasks($queue, ["responseView" => Task\View::FULL])->getIterator() as $item) {
            $payload = json_decode($item->getAppEngineHttpRequest()->getBody(), true);
            $items[] = new QueueItem($queueName, $item->getName(), $payload["taskIdentifier"], $payload["description"], $item->getCreateTime()->toDateTime(), QueueItem::STATUS_PENDING, $payload["configuration"],
                $item->getScheduleTime()->toDateTime());
        }

        return $items;
    }


    /**
     * Register task status change - nothing to do here.
     *
     * @param string $queueName
     * @param string $taskInstanceIdentifier
     * @param string $status
     */
    public function registerTaskStatusChange($queueName, $taskInstanceIdentifier, $status) {
    }
}
