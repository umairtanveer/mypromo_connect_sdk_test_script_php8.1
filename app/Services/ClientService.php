<?php

namespace App\Services;

use MyPromo\Connect\SDK\Client;

class ClientService
{
    /**
     * This will be the url which we used as connect endpoint to access data.
     * You can set this in .env file against variable (CONNECT_ENDPOINT_URL)
     *
     * @var $connectEndPointUrl
     */
    protected $connectEndPointUrl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->connectEndPointUrl = config('connect.endpoint_url');
    }

    /**
     * Connect to SDK client
     *
     * @param $clientId
     * @param $clientSecret
     * @return Client
     */
    public function connect($clientId, $clientSecret): Client
    {
        return new Client('', $clientId, $clientSecret, $this->connectEndPointUrl, true);
    }
}
