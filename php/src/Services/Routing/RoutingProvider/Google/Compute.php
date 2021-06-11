<?php


namespace Kinihost\Services\Routing\RoutingProvider\Google;


use Google_Client;
use Google_Service_Compute_Resource_SslCertificates;

class Compute extends \Google_Service_Compute {

    /**
     * Constructs the internal representation of the Compute service.
     *
     * @param Google_Client $client The client used to deliver requests.
     * @param string $rootUrl The root URL used for requests to the service.
     */
    public function __construct(Google_Client $client, $rootUrl = null) {
        parent::__construct($client, $rootUrl);
        $this->servicePath = 'compute/beta/projects/';

        // Reconstruct ssl certificates to call the right path.
        $this->sslCertificates = new Google_Service_Compute_Resource_SslCertificates(
            $this,
            $this->serviceName,
            'sslCertificates',
            array(
                'methods' => array(
                    'aggregatedList' => array(
                        'path' => '{project}/aggregated/sslCertificates',
                        'httpMethod' => 'GET',
                        'parameters' => array(
                            'project' => array(
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ),
                            'filter' => array(
                                'location' => 'query',
                                'type' => 'string',
                            ),
                            'maxResults' => array(
                                'location' => 'query',
                                'type' => 'integer',
                            ),
                            'orderBy' => array(
                                'location' => 'query',
                                'type' => 'string',
                            ),
                            'pageToken' => array(
                                'location' => 'query',
                                'type' => 'string',
                            ),
                        ),
                    ), 'delete' => array(
                        'path' => '{project}/global/sslCertificates/{sslCertificate}',
                        'httpMethod' => 'DELETE',
                        'parameters' => array(
                            'project' => array(
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ),
                            'sslCertificate' => array(
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ),
                            'requestId' => array(
                                'location' => 'query',
                                'type' => 'string',
                            ),
                        ),
                    ), 'get' => array(
                        'path' => '{project}/global/sslCertificates/{sslCertificate}',
                        'httpMethod' => 'GET',
                        'parameters' => array(
                            'project' => array(
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ),
                            'sslCertificate' => array(
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ),
                        ),
                    ), 'insert' => array(
                        'path' => '{project}/global/sslCertificates',
                        'httpMethod' => 'POST',
                        'parameters' => array(
                            'project' => array(
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ),
                            'requestId' => array(
                                'location' => 'query',
                                'type' => 'string',
                            ),
                        ),
                    ), 'list' => array(
                        'path' => '{project}/global/sslCertificates',
                        'httpMethod' => 'GET',
                        'parameters' => array(
                            'project' => array(
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ),
                            'filter' => array(
                                'location' => 'query',
                                'type' => 'string',
                            ),
                            'maxResults' => array(
                                'location' => 'query',
                                'type' => 'integer',
                            ),
                            'orderBy' => array(
                                'location' => 'query',
                                'type' => 'string',
                            ),
                            'pageToken' => array(
                                'location' => 'query',
                                'type' => 'string',
                            ),
                        ),
                    ),
                )
            )
        );

    }


}
