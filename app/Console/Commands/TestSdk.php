<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use App\Services\ClientService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use MyPromo\Connect\SDK\Exceptions\ApiRequestException;
use MyPromo\Connect\SDK\Exceptions\ApiResponseException;
use MyPromo\Connect\SDK\Exceptions\CarrierException;
use MyPromo\Connect\SDK\Exceptions\CountryException;
use MyPromo\Connect\SDK\Exceptions\InvalidResponseException;
use MyPromo\Connect\SDK\Exceptions\LocaleException;
use MyPromo\Connect\SDK\Exceptions\ProductException;
use MyPromo\Connect\SDK\Exceptions\ProductExportException;
use MyPromo\Connect\SDK\Exceptions\GeneralException;
use MyPromo\Connect\SDK\Exceptions\StateException;
use MyPromo\Connect\SDK\Exceptions\TimezoneException;
use MyPromo\Connect\SDK\Repositories\Orders\OrderRepository;
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
        dd('end');
        */


        # Test Orders Module
        #$this->testOrdersModule();
        #$this->info('');


        # Test products
        #$this->testProducts();
        #$this->info('');

        # Test product export
        $this->testProductExport();
        $this->info('');

        dd('end');


        # Test product import
        $this->testProductImport();
        $this->info('');

        # Test configuratior
        $this->testProductConfigurator();
        $this->info('');

        # Test production
        $this->testProduction();
        $this->info('');

        # Test Miscellaneous
        $this->testMiscellaneous();
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
        } catch (ClientException | InvalidArgumentException $e) {
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
        } catch (DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        // Create Design
        try {
            $this->info('Create design....');
            $designRepository->create($design);

            if ($design->getId()) {
                $this->info('Design with ID ' . $design->getId() . ' created successfully!');
            }
        } catch (DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        // Submit Design
        try {
            $this->info('Submitting design....');
            $designRepository->submit($design->getId());
            $this->info('Design submitted successfully!');
        } catch (DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        // Get Preview
        try {
            $this->info('Trying to get preview.....');
            $designRepository->getPreviewPDF($design->getId());
            $this->info('Preview received successfully!');
        } catch (DesignException | InvalidArgumentException $e) {
            $this->warn($e->getMessage());
            $this->stopMessage();
        }

        // Save Preview
        try {
            $this->info('Trying preview save .....');
            $designRepository->savePreview($design->getId(), 'preview.pdf');
            $this->info('Preview saved successfully!');
        } catch (DesignException | InvalidArgumentException $e) {
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

        $this->info('Create a new design');

        $designRepository = new DesignRepository($this->client);

        $design = new Design();

        try {
            $this->info('Create a new design user');
            $hash = $designRepository->createEditorUserHash($design);
        } catch (DesignException | InvalidArgumentException $e) {
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

        try {
            $this->info('Create a design with the design user');
            $designResponse = $designRepository->create($design);
        } catch (DesignException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }

        $this->info("Editor start URL : " . $designResponse['editor_start_url']);

        try {
            $this->info('Submit the design');
            $designResponse = $designRepository->submit($design->getId());
            $this->info(print_r($designResponse, 1));
        } catch (DesignException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }

        $this->info('Create an order');

        $orderRepository = new OrderRepository($this->client);

        $recipientAddress = new \MyPromo\Connect\SDK\Models\Address();
        $recipientAddress->setAddressId(null);
        $recipientAddress->setAddressKey(null);
        $recipientAddress->setReference('your-reference-code');
        $recipientAddress->setCompany('Sample Company');
        $recipientAddress->setDepartment(null);
        $recipientAddress->setSalutation(null);
        $recipientAddress->setGender(null);
        $recipientAddress->setDateOfBirth(new \DateTime(date('Y-m-d H:i:s')));
        $recipientAddress->setFirstname('Sam');
        $recipientAddress->setMiddlename(null);
        $recipientAddress->setLastname('Sample');
        $recipientAddress->setStreet('Sample Street 1');
        $recipientAddress->setCareOf('Street Add');
        $recipientAddress->setZip(12345);
        $recipientAddress->setCity('Sample Town');
        $recipientAddress->setStateCode('NW');
        $recipientAddress->setDistrict('your-disctrict');
        $recipientAddress->setCountryCode('DE');
        $recipientAddress->setPhone('your-phone');
        $recipientAddress->setFax('your-fax');
        $recipientAddress->setMobile('your-mobile');
        $recipientAddress->setEmail('sam@sample.com');
        $recipientAddress->setVatId('DE1234567890');
        $recipientAddress->setEoriNumber('55555555555');
        $recipientAddress->setAccountHolder('account-holder');
        $recipientAddress->setIban('your-iban');
        $recipientAddress->setBicOrSwift('your-bic-or-swift');
        $recipientAddress->setCommercialRegisterEntry('your-commercial-register-entry');


        $order = new \MyPromo\Connect\SDK\Models\Order();
        $order->setReference('your-order-reference');
        $order->setReference2('your-order-reference2');
        $order->setComment('your comment for order here');
        //$order->setShipper($shipperAddress);
        $order->setRecipient($recipientAddress);
        //$order->setExport($exportAddress);
        //$order->setInvoice($invoiceAddress);

        # Optional parameters
        $order->setFakePreflight(true);
        $order->setFakeShipment(true);


        try {
            $this->info('Sending order');
            $orderResponse = $orderRepository->create($order);
            $this->info(print_r($orderResponse, 1));
        } catch (OrderException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }

        dd('stop here');

        $this->info('Create an item');

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


    }


    /*
     * test sdk module for product export
     */
    public function testProductExport()
    {
        $this->startMessage('Product Export Module testing...');


        $requestExportRepository = new \MyPromo\Connect\SDK\Repositories\ProductFeeds\ProductExportRepository($this->client);

        /*
         * TODO error in API - CO2291
         *
         */
        $this->startMessage('Requesting new export...');

        $productExport = new \MyPromo\Connect\SDK\Models\ProductExport();

        $productExport->setTempletaId(null);
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

        try {
            $this->info('Sending Export Request');

            $requestExportResponse = $requestExportRepository->requestExport($productExport);
            $this->info(print_r($requestExportResponse, 1));

            if ($productExport->getId()) {
                $this->info('Export with ID ' . $productExport->getId() . 'created successfully!');
            }
        } catch (ApiResponseException | InvalidResponseException $e) {
            $this->error('Creating Export Request failed: ' . $e->getMessage() . ' - Errors: ' . print_r($e->getErrors(),true) .' - HHTP_CODE: ' . $e->getCode());
            return 0;
        } catch (ApiRequestException $e) {
            $this->error($e->getMessage());
            return 0;
        }

        dd('die export');


        /*
        $this->startMessage('Request data of newly created export...');

        // TODO: Add try catch after the solution has been fixed - see CO-2293
        $requestExportByIdResponse = $requestExportRepository->find($productExport->getId());
        $this->info(print_r($requestExportByIdResponse, 1));

        // TODO: add cancel, delete

        */

        $this->startMessage('Request data of all exports...');

        // TODO: Add try catch after the solution has been fixed - see CO-2293
        $productExportOptions = new \MyPromo\Connect\SDK\Helpers\ProductExportOptions();
        $productExportOptions->setPage(1); // get data from this page number
        $productExportOptions->setPerPage(5);
        $productExportOptions->setPagination(false);
        #$productExportOptions->setCreatedFrom(new \DateTime(date('Y-m-d H:i:s')));
        #$productExportOptions->setCreatedTo(new \DateTime(date('Y-m-d H:i:s')));

        $this->info(print_r($productExportOptions->toArray(), 1));

        $requestExportAllResponse = $requestExportRepository->all($productExportOptions);

        $this->info(print_r($requestExportAllResponse, 1));


    }

    /*
     * test sdk module for product import
     */
    public function testProductImport()
    {
        $this->startMessage('Product Import Module testing...');

        $requestImportRepository = new \MyPromo\Connect\SDK\Repositories\ProductFeeds\ProductImportRepository($this->client);

        /*
        $productImport = new \MyPromo\Connect\SDK\Models\ProductImport();
        $productImport->setTempletaId(null);
        $productImport->setTempletaKey('prices');
        $productImport->setDryRun(false);
        $productImport->setDateExecute(null);

        $productImportInput = new \MyPromo\Connect\SDK\Helpers\ProductImportInput();
        $productImportInput->setUrl('https://downloads.test.mypromo.com/feeds/Merchant-Prices.xlsx');
        $productImportInput->setFormat('xlsx');
        $productImport->setInput($productImportInput);

        $callback = new \MyPromo\Connect\SDK\Models\Callback();
        $callback->setUrl("https://webhook.site/40b38be3-a76b-4dae-83cc-7bb1a5b7f8a3");
        $productImport->setCallback($callback);

        // TODO: payload looks goot but not working !!
        dd($productImport->toArray());

        try {
            $this->info('Sending Import Request');

            $requestImportResponse = $requestImportRepository->requestImport($productImport);
            $this->info(print_r($requestImportResponse, 1));

            if ($productImport->getId()) {
                $this->info('Import with ID ' . $productImport->getId() . 'created successfully!');
            }
        } catch (GuzzleException $e) {

            $this->error($e->getMessage());
            return 0;
        } catch (ProductImportException | InvalidArgumentException $e) {

            $this->error($e->getMessage());
            return 0;
        }
        */


        /*
        $this->startMessage('Request data of newly created import...');

        // TODO: Add try catch after the solution has been fixed - see CO-2293
        $requestImportByIdResponse = $requestImportRepository->find($productImport->getId());
        $this->info(print_r($requestImportByIdResponse, 1));

        // TODO: add confirm, validate, cancel, delete

        */

        $this->startMessage('Request data of all imports...');

        // TODO: Add try catch after the solution has been fixed - see CO-2293
        $productImportOptions = new \MyPromo\Connect\SDK\Helpers\ProductImportOptions();
        $productImportOptions->setPage(1); // get data from this page number
        $productImportOptions->setPerPage(5);
        $productImportOptions->setPagination(false);
        #$productImportOptions->setCreatedFrom(new \DateTime(date('Y-m-d H:i:s')));
        #$productImportOptions->setCreatedTo(new \DateTime(date('Y-m-d H:i:s')));

        $this->info(print_r($productImportOptions->toArray(), 1));

        $requestImportAllResponse = $requestImportRepository->all($productImportOptions);

        $this->info(print_r($requestImportAllResponse, 1));

    }

    /*
     * testProducts
     */
    public function testProducts()
    {
        $this->startMessage('TODO - testProducts');

        $productsRepository = new \MyPromo\Connect\SDK\Repositories\Products\ProductRepository($this->client);


        $this->testDetail('get all products');
        $productsOptions = new \MyPromo\Connect\SDK\Helpers\ProductOptions();
        $productsOptions->setPage(1);
        $productsOptions->setPerPage(5);
        $productsOptions->setPagination(false);
        $productsOptions->setShippingFrom('DE');

        try {
            $productsResponse = $productsRepository->all($productsOptions);
            $this->info(print_r($productsResponse, true));
        } catch (ProductException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }


        $this->testDetail('get seo overwrites');

        $seoOptions = new \MyPromo\Connect\SDK\Helpers\SeoOptions();
        $seoOptions->setPage(1);
        $seoOptions->setPerPage(5);
        $seoOptions->setPagination(false);
        //$seoOptions->setSku('MP-F10005-C0000001');

        try {
            $productsResponse = $productsRepository->getSeo($seoOptions);
            $this->info(print_r($productsResponse, true));
        } catch (ProductException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }


        $this->error('Some more tests have to be added here !!!');

    }


    /*
     * testProductConfigurator
     */
    public function testProductConfigurator()
    {
        $this->startMessage('TODO - testProductConfigurator');

        $this->error('Could not find any repository for the configurator routes !!!');
    }


    /*
     * testProduction
     */
    public function testProduction()
    {
        // TODO
        $this->startMessage('TODO - testProduction');
    }

    /*
     * testMiscellaneous
     */
    public function testMiscellaneous()
    {
        $this->startMessage('testMiscellaneous');

        $this->testDetail('get api status');
        $generalRepository = new \MyPromo\Connect\SDK\Repositories\Miscellaneous\GeneralRepository($this->client);

        try {
            $apiStatusResponse = $generalRepository->apiStatus();
            $this->info(print_r($apiStatusResponse, true));
        } catch (GeneralException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }


        // TODO - just identifier or complete urls required ???
        $url = "A8ru29";

        try {
            // TODO - does not work!
            //$fileContent = $generalRepository->downloadFile($url);

            // TODO - this is results in error - sdk implementation is wrong!
            // TypeError
            //  MyPromo\Connect\SDK\Repositories\Miscellaneous\GeneralRepository::downloadFile(): Return value must be of type array, null returned

            // TODO: create save method similar to $designRepository->savePreview($design->getId(), 'preview.pdf');
            // alternativly offer savetodisk option and filename in the method
            // eg. downloadFile($url, true, '/path/to/file.ext')

        } catch (GeneralException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }


        $this->testDetail('get carriers');
        $carrierRepository = new \MyPromo\Connect\SDK\Repositories\Miscellaneous\CarrierRepository($this->client);

        $carrierOptions = new \MyPromo\Connect\SDK\Helpers\CarrierOptions();
        $carrierOptions->setPage(1);
        $carrierOptions->setPerPage(5);
        $carrierOptions->setPagination(false);

        try {
            $carrierResponse = $carrierRepository->all($carrierOptions);
            $this->info(print_r($carrierResponse, true));
        } catch (CarrierException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }


        $this->testDetail('get countries');
        $countryRepository = new \MyPromo\Connect\SDK\Repositories\Miscellaneous\CountryRepository($this->client);

        $countryOptions = new \MyPromo\Connect\SDK\Helpers\CountryOptions();
        $countryOptions->setPage(1);
        $countryOptions->setPerPage(5);
        $countryOptions->setPagination(false);

        try {
            $countryResponse = $countryRepository->all($countryOptions);
            $this->info(print_r($countryResponse, true));
        } catch (CountryException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }


        $this->testDetail('get locales');
        $localeRepository = new \MyPromo\Connect\SDK\Repositories\Miscellaneous\LocaleRepository($this->client);

        $localeOptions = new \MyPromo\Connect\SDK\Helpers\LocaleOptions();
        $localeOptions->setPage(1);
        $localeOptions->setPerPage(5);
        $localeOptions->setPagination(false);

        try {
            $localeResponse = $localeRepository->all($localeOptions);
            $this->info(print_r($localeResponse, true));
        } catch (LocaleException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }


        $this->testDetail('get states');
        $stateRepository = new \MyPromo\Connect\SDK\Repositories\Miscellaneous\StateRepository($this->client);

        $stateOptions = new \MyPromo\Connect\SDK\Helpers\StateOptions();
        $stateOptions->setPage(1);
        $stateOptions->setPerPage(5);
        $stateOptions->setPagination(false);

        try {
            $stateResponse = $stateRepository->all($stateOptions);
            $this->info(print_r($stateResponse, true));
        } catch (StateException | InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 0;
        }


        $this->testDetail('get timezones');
        $timeZonesRepository = new \MyPromo\Connect\SDK\Repositories\Miscellaneous\TimezoneRepository($this->client);

        $timeZonesOptions = new \MyPromo\Connect\SDK\Helpers\TimezoneOptions();
        $timeZonesOptions->setPage(1);
        $timeZonesOptions->setPerPage(5);
        $timeZonesOptions->setPagination(false);

        try {
            $timeZonesResponse = $timeZonesRepository->all($timeZonesOptions);
            $this->info(print_r($timeZonesResponse, true));
        } catch (TimezoneException | InvalidArgumentException $e) {
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

    public function testDetail($title)
    {
        $this->info('------------------------------------------------------------------------------------------------------------------------------------------------');
        $this->info($title);
        $this->info('------------------------------------------------------------------------------------------------------------------------------------------------');
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
