<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use App\Services\ClientService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use MyPromo\Connect\SDK\Exceptions\ProductExportException;
use Psr\Cache\InvalidArgumentException;

use MyPromo\Connect\SDK\Exceptions\DesignException;
use MyPromo\Connect\SDK\Models\Design;
use MyPromo\Connect\SDK\Repositories\Designs\DesignRepository;
use MyPromo\Connect\SDK\Models\ProductExport;
use MyPromo\Connect\SDK\Helpers\ProductExportFilterOptions;

class TestSdk extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'test:sdk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This script will test connect SDK all methods one by one.';

    /**
     * This will be the url which we used as connect endpoint to access data.
     * You can set this in .env file against variable (CONNECT_ENDPOINT_URL)
     *
     * @var $connectEndPointUrl
     */
    protected $connectEndPointUrl;

    /**
     * @var ClientService
     */
    public $clientService;

    /**
     * @var CLient
     */
    public $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ClientService $clientService)
    {
        parent::__construct();
        $this->clientService = $clientService;
        $this->connectEndPointUrl = config('connect.endpoint_url');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        # Introduction to tool
        $this->info('This script will test all methods of "Mypromo Connect SDK" ony by one!');
        $this->info('Testing Start........');
        $this->info('');

        # Start Testing

        # Test Connection
        $this->makeConnectionWithClient();
        $this->info('');

        /*
        # Test Design Module
        $this->testDesignModule();
        $this->info('');

        # Test Orders Module
        $this->testOrdersModule();
        $this->info('');
        */

        # Test product export
        $this->testProductExport();
        $this->info('');

        return 0;
    }

    /**
     * This method will test and return connection of client with endpoint
     */
    public function makeConnectionWithClient()
    {
        $this->startMessage("Build client connection. This module will test and create connection with '{$this->connectEndPointUrl}'");
        $clientId = config('connect.client_id');
        $clientSecret = config('connect.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            $this->warn('Client ID or Client Secret is missing. Please fill this detail in .env file and try again!');
            $this->stopMessage();
        }

        try {
            $this->client = $this->clientService->connect($clientId, $clientSecret);
            $status = $this->client->status();

            if ($status['message'] !== 'OK') {
                $this->warn('Connection failed!');
                return 0;
            }

            $this->info('Connection created successfully!');
        } catch (Exception $ex) {
            $this->warn($ex->getMessage());
            $this->stopMessage();
        } catch (GuzzleException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
        }

        $this->info('Client connection testing finished!');
    }

    /**
     * This method will test design API/Module of SDK
     */
    public function testDesignModule()
    {
        $this->startMessage('Design module testing start......');
        $designRepository = new DesignRepository($this->client);

        $design = new Design();
        $design->setEditorUserHash(md5('hashing_string'));
        $design->setReturnUrl(config('connect.shop_url'));
        $design->setCancelUrl(config('connect.shop_url'));
        $design->setSku('MP-F10005-C0000001');
        $design->setIntent('customize');
        $design->setOptions([
            'example-key' => 'example-value'
        ]);

        // Create editor user hash
        try {
            $this->info('Generating Editor user hash....');
            $userHash = $designRepository->createEditorUserHash($design);
            $design->setEditorUserHash($userHash['editor_user_hash']);
            $this->info('Editor user hash generate successfully!');
        } catch (GuzzleException | DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        // Create Design
        try {
            $this->info('Create design....');
            $designRepository->create($design);

            if ($design->getId()) {
                $this->info('Design with ID ' . $design->getId() . 'created successfully!');
            }
        } catch (GuzzleException | DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        // Submit Design
        try {
            $this->info('Submitting design....');
            $designRepository->submit($design->getId());
            $this->info('Design submitted successfully!');
        } catch (GuzzleException | DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        // Get Preview
        try {
            $this->info('Trying to get preview.....');
            $designRepository->getPreviewPDF($design->getId());
            $this->info('Preview received successfully!');
        } catch (GuzzleException | DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        // Save Preview
        try {
            $this->info('Trying preview save .....');
            $designRepository->savePreview($design->getId(), 'preview.pdf');
            $this->info('Preview saved successfully!');
        } catch (GuzzleException | DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        $this->info('Design module testing finished!');
    }

    /**
     * test sdk module for orders
     */
    public function testOrdersModule()
    {
        $this->startMessage('Orders module testing under development...');

        $orderItem = new \MyPromo\Connect\SDK\Models\OrderItem();
        $orderItem->setReference('your-reference');
        $orderItem->setQuantity(35);
        $orderItem->setOrderId(1);
        $orderItem->setSku('product-sku');
        $orderItem->setComment('comment for order item here');

        # To add service item mention order_item_id in relation
        $orderItemRelation = new \MyPromo\Connect\SDK\Models\OrderItemRelation();
        $orderItemRelation->setOrderItemId(22);

        # To set relation pass object of orderItemRelation after setting up order_item_id which is added previously in order
        $orderItem->setRelation($orderItemRelation->toArray());


        $designRepository = new DesignRepository($this->client);

        $design = new Design();

        try {
            $hash = $designRepository->createEditorUserHash($design);
        } catch (GuzzleException | DesignException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }

        if (!isset($hash['editor_user_hash'])) {
            $this->error('Editor hash not found.');
            return 0;
        }

        $design->setEditorUserHash($hash['editor_user_hash']);
        $design->setReturnUrl('https://yourshop.com/basket/TPD123LD02LAXALOP/{DESIGNID}/add/{INTENT}/{USERHASH}/{INTENT}/{DESIGNID}');
        $design->setCancelUrl('https://yourshop.com/product/TPD123LD02LAXALOP/design/{DESIGNID}/user/{USERHASH}');
        $design->setSku('MP-F10005-C0000001');
        $design->setIntent('customize');

        $designResponse = $designRepository->create($design);

        $this->info("Editor start URL : " . $designResponse['editor_start_url']);

        try {
            $designResponse = $designRepository->submit($design->getId());
            $this->info(print_r($designResponse, 1));
        } catch (GuzzleException $e) {
            $this->error($e->getMessage());
            return 0;
        } catch (DesignException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }
    }


    /*
     * test sdk module for product export
     */
    public function testProductExport()
    {
        $this->startMessage('Product Export Module testing...');

        $productExport = new \MyPromo\Connect\SDK\Models\ProductExport();

        $productExport->setTempletaKey('prices');
        $productExport->setFormat('xslx');

        $productExportFilterOptions = new \MyPromo\Connect\SDK\Helpers\ProductExportFilterOptions();
        $productExportFilterOptions->setCategoryId(null);
        $productExportFilterOptions->setCurrency('EUR');
        $productExportFilterOptions->setLang('DE');
        $productExportFilterOptions->setProductTypes($productExportFilterOptions::ProductExportFilterOptionsProductTypeAll);
        $productExportFilterOptions->setSearch(null);
        $productExportFilterOptions->setSku(null);
        $productExportFilterOptions->setShippingFrom('DE');
        $productExport->setFilters($productExportFilterOptions);

        $callback = new \MyPromo\Connect\SDK\Models\Callback();
        $callback->setUrl("https://webhook.site/40b38be3-a76b-4dae-83cc-7bb1a5b7f8a3");
        $productExport->setCallback($callback);

        $requestExportRepository = new \MyPromo\Connect\SDK\Repositories\ProductFeeds\ProductExportRepository($this->client);


        try {
            $this->info('Sending Export Request');

            $requestExportResponse = $requestExportRepository->requestExport($productExport);
            $this->info(print_r($requestExportResponse, 1));

            if ($productExport->getId()) {
                $this->info('Export with ID ' . $productExport->getId() . 'created successfully!');
            }
        } catch (GuzzleException $e) {
            $this->error($e->getMessage());
            return 0;
        } catch (ProductExportException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }

    }

    /**
     * Start testing of new modules (Show hiding)
     *
     * @param $title
     */
    public function startMessage($title)
    {
        $this->info('************************************************************************************************************************************************');
        $this->info($title);
        $this->info('************************************************************************************************************************************************');
    }

    /**
     * This method can be used to stop testing
     */
    public function stopMessage(): int
    {
        $this->error('Testing stopped!');
        return 0;
    }
}
