<?php


namespace Kinihost\Objects\Site;


class SiteConfig {

    /**
     * The publish directory (relative to the source root) which will be processed when
     * creating deployment versions.
     *
     * @var string
     */
    private $publishDirectory;


    /**
     * The index page for this site - defaults to "index.html"
     *
     * @var string
     */
    private $indexPage = "index.html";


    /**
     * The not found page for this site - defaults to "404.html"
     *
     * @var string
     */
    private $notFoundPage = "404.html";


    /**
     * SiteConfig constructor.
     *
     * @param string $publishDirectory
     */
    public function __construct($publishDirectory = null, $indexPage = "index.html", $notFoundPage = "404.html") {
        $this->publishDirectory = $publishDirectory;
        $this->indexPage = $indexPage;
        $this->notFoundPage = $notFoundPage;
    }


    /**
     * @return string
     */
    public function getPublishDirectory() {
        return $this->publishDirectory;
    }

    /**
     * @param string $publishDirectory
     */
    public function setPublishDirectory($publishDirectory) {
        $this->publishDirectory = $publishDirectory;
    }

    /**
     * @return string
     */
    public function getIndexPage() {
        return $this->indexPage;
    }

    /**
     * @param string $indexPage
     */
    public function setIndexPage($indexPage) {
        $this->indexPage = $indexPage;
    }

    /**
     * @return string
     */
    public function getNotFoundPage() {
        return $this->notFoundPage;
    }

    /**
     * @param string $notFoundPage
     */
    public function setNotFoundPage($notFoundPage) {
        $this->notFoundPage = $notFoundPage;
    }


}
