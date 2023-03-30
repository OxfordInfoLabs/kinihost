<?php

namespace Kinihost\Services\Build\Runner;

use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;

/**
 *
 * @implementation CURRENT Kinihost\Services\Build\Runner\CurrentBuildRunner
 * @implementation PREVIEW Kinihost\Services\Build\Runner\CurrentBuildRunner
 * @implementation PUBLISH Kinihost\Services\Build\Runner\CurrentBuildRunner
 * @implementation SOURCE_UPLOAD Kinihost\Services\Build\Runner\SourceUploadBuildRunner
 * @implementation VERSION_REVERT Kinihost\Services\Build\Runner\VersionRevertBuildRunner
 *
 *
 * Interface BuildRunner
 */
interface BuildRunner {


    /**
     * Run the build for the supplied site on the supplied build target.
     * Runner specific data is supplied to be used if required.
     *
     * @param Build $build
     * @param Site $site
     */
    public function runBuild($build, $site);


}
