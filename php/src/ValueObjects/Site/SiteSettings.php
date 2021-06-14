<?php


namespace Kinihost\ValueObjects\Site;


class SiteSettings {

    /**
     * @var string
     */
    private $indexPage;

    /**
     * @var string
     */
    private $notFoundPage;

    /**
     * @var string
     */
    private $publishDirectory;


    /**
     * SiteSettings constructor.
     *
     * @param string $primaryDomain
     * @param string $indexPage
     * @param string $notFoundPage
     * @param string $publishDirectory
     */
    public function __construct($indexPage = null, $notFoundPage = null, $publishDirectory = null) {

        $this->indexPage = $indexPage;
        $this->notFoundPage = $notFoundPage;
        $this->publishDirectory = $publishDirectory;
    }


    /**
     * @return mixed
     */
    public function getIndexPage() {
        return $this->indexPage;
    }


    /**
     * @return mixed
     */
    public function getNotFoundPage() {
        return $this->notFoundPage;
    }


    /**
     * @return mixed
     */
    public function getPublishDirectory() {
        return $this->publishDirectory;
    }



}
