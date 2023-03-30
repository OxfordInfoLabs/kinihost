<?php

namespace Kinihost\Services\Build;

use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\Exception\ConcurrentBuildException;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Services\Build\Runner\BuildRunner;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;
use Kinihost\Services\Source\SiteSourceService;

class BuildService {

    /**
     * @var SiteService
     */
    private $siteService;

    /**
     * @var QueuedTaskService
     */
    private $queuedTaskService;

    /**
     * @var SecurityService
     */
    private $securityService;


    /**
     * @var EmailService
     */
    private $emailService;


    /**
     * BuildService constructor.
     *
     * @param SiteService $siteService
     * @param QueuedTaskService $queuedTaskService
     * @param SecurityService $securityService
     * @param EmailService $emailService
     */
    public function __construct($siteService, $queuedTaskService, $securityService, $emailService) {
        $this->siteService = $siteService;
        $this->queuedTaskService = $queuedTaskService;
        $this->securityService = $securityService;
        $this->emailService = $emailService;
    }


    /**
     * Create a new build for a site - optionally pass extra configuration.
     *
     * @param int $siteId
     */
    public function createBuild($siteKey, $type, $status = Build::STATUS_QUEUED, $buildData = null) {

        $site = $this->siteService->getSiteByKey($siteKey);
        $site->incrementLastBuildNumber();

        $initiatingUser = $this->securityService->getLoggedInSecurableAndAccount()[0] ?? null;
        $initiatingUserId = $initiatingUser ? $initiatingUser->getId() : null;

        $build = new Build($site, $type, $status, $initiatingUserId, $buildData);
        $build->save();


        // If queued, queue the build
        if ($status == Build::STATUS_QUEUED) {
            $this->queueBuild($build->getId());
        } else if ($status == Build::STATUS_RUNNING) {
            $this->runBuild($build->getId());
        }

        return $build;
    }


    /**
     * Create a preview build
     *
     * @param $siteKey
     */
    public function createPreviewBuild($siteKey) {
        return $this->createBuild($siteKey, Build::TYPE_PREVIEW);
    }


    /**
     * Create a production build
     *
     * @param $siteKey
     */
    public function createProductionBuild($siteKey) {
        return $this->createBuild($siteKey, Build::TYPE_PUBLISH);
    }


    /**
     * Create a version revert build
     *
     * @param $siteKey
     * @param $targetVersion
     */
    public function createVersionRevertBuild($siteKey, $targetVersion) {
        $this->createBuild($siteKey, Build::TYPE_VERSION_REVERT, Build::STATUS_QUEUED, ["targetVersion" => $targetVersion]);
    }


    /**
     * Queue a build which has already been created in pending status.
     *
     * @param $buildId
     */
    public function queueBuild($buildId) {

        // Get the build
        $build = Build::fetch($buildId);

        $this->queuedTaskService->queueTask(Configuration::readParameter("queue.name"), "run-site-build",
            "Run Build: " . $buildId, ["buildId" => $buildId]);

        // Register the status change to queued status.
        $build->registerStatusChange(Build::STATUS_QUEUED);
    }


    /**
     * Actually execute a build - throws a concurrent build exception
     * if a build is already in progress for the current site
     *
     * @param $buildId
     * @objectInterceptorDisabled
     */
    public function runBuild($buildId) {

        try {

            /**
             * @var Build $build
             */
            $build = Build::fetch($buildId);

            // Site
            $site = Site::fetch($build->getSiteId());

            $otherBuilds = Build::values("COUNT(*)", "WHERE id <> ? AND site_id = ? AND status = ?",
                $buildId, $build->getSiteId(), Build::STATUS_RUNNING);

            // If at least one more build running, throw an exception
            if ($otherBuilds[0] > 0) {
                throw new ConcurrentBuildException($site->getSiteKey());
            }

            // Mark as running
            $build->registerStatusChange(Build::STATUS_RUNNING);

            $buildRunner = Container::instance()->getInterfaceImplementation(BuildRunner::class, $build->getBuildType());
            $buildRunner->runBuild($build, $site);

            // Mark as succeeded if completes all steps successfully.
            $build->registerStatusChange(Build::STATUS_SUCCEEDED);

            if ($build->getBuildType() == Build::TYPE_PREVIEW) {
                $site->registerPreviewBuild();
            } else if ($build->getBuildType() == Build::TYPE_PUBLISH) {
                $site->registerPublishedBuild();
            }

            $site = Site::fetch($build->getSiteId());

            if ($build->getInitiatingUserId())
                $this->emailService->send(new UserTemplatedEmail($build->getInitiatingUserId(),
                    "build-success", [
                        "build" => $build,
                        "site" => $site
                    ]));


        } catch (\Exception $e) {

            if ($e instanceof ConcurrentBuildException)
                throw $e;

            $build->registerStatusChange(Build::STATUS_FAILED, $e->getMessage());

            if ($build->getInitiatingUserId()) {

                $site = Site::fetch($build->getSiteId());

                $this->emailService->send(new UserTemplatedEmail($build->getInitiatingUserId(),
                    "build-failure", [
                        "build" => $build,
                        "site" => $site
                    ]));
            }
        }


    }

    /**
     * Return a filtered list of builds for a given site
     *
     * @param $siteId
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function listBuildsForSite($siteId, $limit = 10, $offset = 0) {
        return Build::filter("WHERE siteId = ? 
            ORDER BY createdDate DESC LIMIT $limit OFFSET $offset",
            $siteId);
    }


    /**
     * Get a build by ID
     *
     * @param $buildId
     * @return mixed
     */
    public function getBuild($buildId) {
        return Build::fetch($buildId);
    }
}
