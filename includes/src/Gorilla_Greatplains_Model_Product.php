<?php
/*
 * Attribute Sets

  [0] => Array
  (
  [attribute_set_id] => 1
  [attribute_set_name] => Default
  )

  [1] => Array
  (
  [attribute_set_id] => 2
  [attribute_set_name] => Default
  )

  [2] => Array
  (
  [attribute_set_id] => 3
  [attribute_set_name] => Default
  )

  [3] => Array
  (
  [attribute_set_id] => 4
  [attribute_set_name] => Default
  )

  [4] => Array
  (
  [attribute_set_id] => 5
  [attribute_set_name] => Default
  )

  [5] => Array
  (
  [attribute_set_id] => 6
  [attribute_set_name] => Default
  )

  [6] => Array
  (
  [attribute_set_id] => 7
  [attribute_set_name] => Default
  )

  [7] => Array
  (
  [attribute_set_id] => 8
  [attribute_set_name] => Default
  )

  [8] => Array
  (
  [attribute_set_id] => 9
  [attribute_set_name] => Default
  )

  [9] => Array
  (
  [attribute_set_id] => 10
  [attribute_set_name] => Clothing
  )

  [10] => Array
  (
  [attribute_set_id] => 11
  [attribute_set_name] => Event
  )

  [11] => Array
  (
  [attribute_set_id] => 12
  [attribute_set_name] => Exam
  )

  [12] => Array
  (
  [attribute_set_id] => 13
  [attribute_set_name] => Book
  )

  [13] => Array
  (
  [attribute_set_id] => 14
  [attribute_set_name] => PremiumACCESS Subscription
  )

  [14] => Array
  (
  [attribute_set_id] => 15
  [attribute_set_name] => Downloadable
  )

  [15] => Array
  (
  [attribute_set_id] => 16
  [attribute_set_name] => Grouped Configurable
  )

  [16] => Array
  (
  [attribute_set_id] => 17
  [attribute_set_name] => CD-ROM
  )
 *
 */

class Gorilla_Greatplains_Model_Product {

    private $groupids;
    private $attributesets;
    private $gp;
    private $_workingAttributeSet;
    private $_workingSkus;
    private $_skus;
    public $skus = "";
    public $attId = "";
    private $gpSkuDataCache  = array();
    public $startTime       = null;
    private $scriptStartTime = null;

    const ATTRIBUTE_CODE        = 'tier_price';
    const MAX_SKUS_PER_GP_CALL  = 50;
    const MAX_SKUS_PER_RUN      = 5000;

    // DEBUGGING
    private $cannotfind;
    private $logname = "foo.txt";
    private $totalProducts  = 0;
    private $numProdsWData  = 0;
    private $numProdsNoData = 0;

    const XML_PATH_GREATPLAINS_PRODUCT_UDATEDATA_CRON_NOTIFICATION_ACTIVE  = 'greatplains/product_updatedata_cron/active';
    const XML_PATH_GREATPLAINS_PRODUCT_UDATEDATA_CRON_RECIPIENT_EMAIL = 'greatplains/product_updatedata_cron/notification_recipient_email';
    const XML_PATH_GREATPLAINS_PRODUCT_UDATEDATA_CRON_CC_EMAIL        = 'greatplains/product_updatedata_cron/notification_cc_email';
    const XML_PATH_GREATPLAINS_PRODUCT_UDATEDATA_CRON_SENDER_EMAIL    = 'greatplains/product_updatedata_cron/notification_sender_email';

    public function _construct() {
        //parent::_construct();
    }

    /**
     * Logging for this model.
     *
     * @param string $message
     */
    function log($message) {
        //print_r($message);
        Mage::Log($message, Zend_Log::DEBUG, 'gp_product_cron.log');
    }

    private function setScriptStartTime() {
        $this->scriptStartTime = date("Y-m-d H:i:s", time());
    }

    private function getScriptStartTime() {
        if (is_null($this->scriptStartTime)) {
            $this->setScriptStartTime();
        }
        return $this->scriptStartTime;
    }

    /**
     */
    private function setStartTime() {
        $this->startTime = date("Y-m-d H:i:s", time() - (24 * 60 * 60)); // 24 hours ago
    }

    /**
     *
     * @return string - timestamp in MySQL format.
     */
    private function getStartTime() {
        if (is_null($this->startTime)) {
            $this->setStartTime();
        }
        return $this->startTime;
    }

    /**
     * Return a product collection that has some basic filtering applied for this model.
     * Additional filtering can be applied to the object returned.
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    private function getProductCollection() {
        $collection = Mage::getModel('catalog/product')->getCollection()
                ->addFieldToFilter('type_id', array('in' => array('simple', 'virtual', 'downloadable')))
                ->addAttributeToFilter(array(
                    array(// last update time is more than 24 hours before we
                        'attribute' => 'last_gp_update', // started running this script
                        'to' => $this->getStartTime(),
                        'datetime' => true
                    ), // OR
                    array(// last update isn't set for this product
                        'attribute' => 'last_gp_update',
                        'null' => true
                    ),
                    array(// OR
                        'attribute' => 'last_gp_update', // last update is set, but empty
                        'eq' => ''
                    )
                        ), null, 'left')                                             // make sure to left join.
                ->addAttributeToSelect("gp_sku")
                ->setPage(1, self::MAX_SKUS_PER_RUN);
        return $collection;
    }

    /**
     * Check if this customer group is elevated (e.g. member or reseller).
     *
     * @param $custGroupId
     * @return bool
     */
    private function isCustGroupElevated($custGroupId) {
        switch ($custGroupId) {
            case $this->groupids['ALL']:            // falls through to return false
            case $this->groupids['NOT LOGGED IN']:  // falls through to return false
            case $this->groupids['General']:
                return false;

            default:
                return true;
        }
    }

    /**
     * Check if this customer group is one that gets pricing updates from GP
     * This is a whitelist. It assumes any groups not in the list are special groups that we don't get from GP.
     *
     * @param $custGroupId
     * @return bool
     */
    private function isCustGroupPricingUpdateFromGP($custGroupId) {
        switch ($custGroupId) {
            case $this->groupids['ALL']:            // falls through to return false
            case $this->groupids['NOT LOGGED IN']:  // falls through to return false
            case $this->groupids['General']:        // falls through to return false
            case $this->groupids['Member']:         // falls through to return false
            case $this->groupids['Reseller']:       // falls through to return false
            case $this->groupids['HIDDEN_Reseller']:
                return true;

            default:
                return false;
        }
    }

    /**
     * Updates Magento products with data from GP.
     *
     * @param $data - data from GP.
     * @return bool
     */
    public function _updateProductData($data, $gpSku = null) {


        /* Get downloadable tax class */

        static $downloadableClass;
        if (is_null($downloadableClass)) {
            $classes = Mage::getModel('tax/class')->getCollection()
                    ->addFieldToFilter('class_name', array('eq' => 'Downloadable'))
                    ->load();
            if ($classes->count()) {
                $downloadableClass = $classes->getFirstItem();
            } else {
                $downloadableClass = Mage::getModel('tax/class');
                $downloadableClass->setData(array(
                    'class_name' => 'Downloadable',
                    'class_type' => 'PRODUCT',
                ));
                $downloadableClass->save();
            }
        }
        //  Mage::Log($data, Zend_Log::DEBUG, 'gp_product_cron.log');

        /*
         * Check for empty data sets. If all are empty, return false for this sku.
         */
        $gotData = false;
        $gotDescription = false;
        $gotWeight = false;
        $gotInventory = false;
        $gotTaxability = false;
        $gotTierPricing = false;
        if (isset($data->Description) && $data->Description != "") {
            $gotDescription = true;
            $gotData = true;
        }
        if (isset($data->Weight) && $data->Weight != "") {
            $gotWeight = true;
            $gotData = true;
        }
        if (isset($data->Inventory) && $data->Inventory != "") {
            $gotInventory = true;
            $gotData = true;
        }
        if (isset($data->Taxability) && $data->Taxability != "") {
            $gotTaxability = true;
            $gotData = true;
        }
        if (!empty($data->TierPricing) && is_object($data->TierPricing)) {
            $gotTierPricing = true;
            $gotData = true;
        }

        if (!$gotData) {
            $this->log("No data from GP for gp_sku = " . $gpSku);
        } else {
            $this->log("Data found from GP for gp_sku = " . $gpSku);
        }

        /*
         * Get the set of Magento products that have this GP_SKU
         */
        $sku = (empty($data->SKU)) ? $gpSku : $data->SKU;
        $originalPrice = 0;
        $products = $this->getProductCollection();
        $products = $products->addAttributeToFilter('gp_sku', $sku)
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('visibility')
                ->addTierPriceData()
                ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);


        /*
         * Update product information for each product with this gp_sku
         */
        foreach ($products as $product) {
            if ($gotData) {
                $this->numProdsWData++;
            } else {
                $this->numProdsNoData++;
            }
            $this->log("\tFound product with sku = " . $product->getSku());


            /*
             * Process and update pricing from GP.
             */
            $originalPrice = $product->getPrice();
            if ($gotTierPricing) {
                $this->log("\t\tProcessing new tier prices");
                $tierpricing = $data->TierPricing;
                $tierpricing = (array) $tierpricing;

                // Loop through GP tier pricing data and accumulate it in newTierPrices
                $newTierPrices = array();
                foreach ($tierpricing as $group => $prices) {
                    foreach ($prices->PriceTier as $gpPriceTier) {
                        $gpPriceTier = (array) $gpPriceTier;
                        $mageTiers = $this->processPrice($group, $gpPriceTier);
                        foreach ($mageTiers as $singleTier) {
                            $newTierPrices[] = $singleTier; // Iron Eyes Cody
                        }
                    }
                }
                // Mage::Log($newTierPrices, Zend_Log::DEBUG, 'gp_product_test.log');
                // Set product's regular price to first tier that meets qualifications.
                // There should only be one anyhow.
                foreach ($newTierPrices as $tier) {
                    if ($tier['price_qty'] == 1 && !$this->isCustGroupElevated($tier['cust_group'])) {
                        $originalPrice = $tier['price'];
                        break;
                    }
                }

                /*
                 * Retain existing tiers that are not part of the set of tiers that are updated from GP.
                 */
                $existingTierPrice = $product->getData('tier_price');
                Mage::Log($existingTierPrice, Zend_Log::DEBUG, 'gp_product_test.log');
                if ($existingTierPrice) {
                    $this->log("\t\tHad existing tiers. Special values will be retained.");
                    foreach ($existingTierPrice as $tp) {
                        if (!$this->isCustGroupPricingUpdateFromGP($tp['cust_group'])) {
                            $this->log("\t\tNot In group list - ALL,NOT LOGGED IN,General,Member,Reseller,HIDDEN_Reseller will not overwritten");
                            $newTierPrices[] = $tp;
                        } else {
                            //$this->log("\t\tIn group list - ALL,NOT LOGGED IN,General,Member,Reseller,HIDDEN_Reseller will overwritten");
                            $this->log("\t\tIn group list - ALL,NOT LOGGED IN,General,Member,Reseller,HIDDEN_Reseller will overwritten if such a tier already exists");

                            if (!$this->checkTierExists($tp, $newTierPrices)) {
                                $newTierPrices[] = $tp;
                            }
                        }
                    }
                    Mage::Log($newTierPrices, Zend_Log::DEBUG, 'gp_product_test.log');
                }
            }

            /*
             * Process and update weight
             * Ignores weights of 0 from GP.
             */
            if ($gotWeight) {
                if ($data->Weight > 0) {
                    $this->log("\t\tGot a new non-zero weight.");
                    if ($product->getTypeId() == 'simple')
                        $product->setWeight($data->Weight);
                }
            }

            /* ticket#2014112010000245 - only update inventory and taxability when product is not "virtual" and "event" */
            $isEvent = ($product->getData('type_id') == 'virtual' && $product->getData('attribute_set_id') == 11);

            /*
             * Process and update inventory
             */
            $stockData = array();
            
            if ($gotInventory && !$isEvent) {
                $this->log("\t\tSetting new inventory data");
                $stockData['qty'] = $data->Inventory;

                // Set is_in_stock properly based on qty and (if necessary) backorderable
                // Note: if qty <= 0 and it is backorderable, we leave is_in_stock as is.
                if ($data->Inventory > 0) {
                    $stockData['is_in_stock'] = 1;
                } else {
                    $isBackorderable = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getBackorders();
                    if ($isBackorderable == "0") {
                        $stockData['is_in_stock'] = 0;
                    }
                }

                /*                 * * start. artem. gorilla. ticket 2012120710000352 ** */
                //$product->setStockItem($stockData);
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $product->setStockItem($stockItem);
                $product->setStockData($stockData);
                /*                 * * end. artem. gorilla. ticket 2012120710000352 ** */
            } else {
                $this->log("\t\tgotInventory -- ".$gotInventory." --- isevent".$isEvent);
                $stockData = $product->getStockItem();
                if ($stockData->getUseConfigManageStokck() == 0) {
                    $stockData->setData('qty', 0);
                    $stockData->setData('is_in_stock', 1);
                    $stockData->setData('manage_stock', 0);
                    $stockData->setData('use_config_manage_stock', 0);
                    //  $stockData->save(); // This enough to save stock data.
                    $product->setStockItem($stockData);
                }
            }

            /*
             * Process and update taxability
             */
            if ($product->getTypeId() == 'downloadable') {
                $product->setTaxClassId($downloadableClass->getId());
            } else {
                if ($gotTaxability && !$isEvent) {
                    $this->log("\t\tSetting new tax data --- ".$data->Taxability);

                    switch ($data->Taxability) {
                        
                        case "Basedoncustomer" :
                        case "BasedOnCustomer" :
                            $this->log("\t\tnew tax data -- 2");
                            $product->setTaxClassId(2);
                            break;

                        case "NonTaxable" :
                        case "Nontaxable" :
                            $this->log("\t\tnew tax data -- 0");
                            $product->setTaxClassId(0);
                            break;

                        default:
                            $this->log("\t\tnew tax data -- 2");
                            $product->setTaxClassId(2);
                            break;
                    }
                }
            }

            /*
             * Update with time we ran this.
             */
            $product->setLastGpUpdate($this->getScriptStartTime());

            /**
             * Save the product.
             */
            try {
                $this->log("\t\tSaving product");
                $product->setPrice($originalPrice);
                                
                // If we have new tier pricing data, there's a two step save process:
                //      1) first we unset the current tier price, then we save,
                //      2) then we set the new tier price, and then go with the regular save
                if ($gotTierPricing) {
                    $this->log("\t\tGotTierPricing");
                    $product->setData(self::ATTRIBUTE_CODE, array());
                    $product->setData('tier_price_changed', 1);
                    $this->log("\t\tbefore save 1");
                    $product->save();
                    $this->log("\t\tafter save 1");
                    time_nanosleep(0, 10000000); // this is 1/100th of a second.
                    $this->log("\t\after nano");
                    $product->setData(self::ATTRIBUTE_CODE, $newTierPrices);
                    $this->log("\t\tafter setting new tiers");
                }
                
                $product->save();
                $this->log("\t\tSaved");
                
            } 
            catch (Exception $e) 
            {
                $line = "Integrity constraint violation"; // We are supressing this error.
                if (strpos($e->getMessage(), $line) === false) {
                    $this->log("Error saving product. Sku = " . $product->getSku . ". Error: " . $e->getMessage());
                }
            }
        }

        return $gotData;
    }

    //infobeans added
    public function checkTierExists($tp, $newTierPrices) {
        foreach ($newTierPrices as $newTier) {
            if ($tp['website_id'] == $newTier['website_id']
                && $tp['cust_group'] == $newTier['cust_group'] 
                && $tp['price_qty'] == $newTier['price_qty'] 
            ) 
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Process GP tier pricing and translate it to a format that's useful to us.
     * Assumes a zero price is valid.
     *
     * @param $group - GP name of customer group
     * @param $price - GP price array, including Qty and Price
     * @return array - array of prices we got out of the GP data
     */
    private function processPrice($group, $price) {

        $unitprice = $price['Price'];
        $quantity = (string) $price['Qty'];
        $newTierPrices = array();
        //  Mage::Log($group, Zend_Log::DEBUG, 'gp_product_test.log');
        //  Mage::Log($price, Zend_Log::DEBUG, 'gp_product_test.log');
        switch ($group) {

            case "DIST" :
                if ($unitprice >= 0) {
                    /*
                     * See member pricing for RESELLER QTY 1
                     */
                    $newTierPrices[] = array(
                        'website_id' => 0,
                        'cust_group' => $this->groupids['HIDDEN_Reseller'],
                        'price_qty' => '1',
                        'price' => $unitprice,
                        'website_price' => $unitprice
                    );
                    $newTierPrices[] = array(
                        'website_id' => 0,
                        'cust_group' => $this->groupids['Reseller'],
                        'price_qty' => '40',
                        'price' => $unitprice,
                        'website_price' => $unitprice
                    );
                }
                break;

            case "MEM" :
                if ($unitprice >= 0) {
                    $newTierPrices[] = array(
                        'website_id' => 0,
                        'cust_group' => $this->groupids['Member'],
                        'price_qty' => $quantity,
                        'price' => $unitprice,
                        'website_price' => $unitprice
                    );
                    // Set Reseller Qty=1 to Member Qty=1
                    if ((int) $quantity == 1) {
                        $newTierPrices[] = array(
                            'website_id' => 0,
                            'cust_group' => $this->groupids['Reseller'],
                            'price_qty' => '1',
                            'price' => $unitprice,
                            'website_price' => $unitprice
                        );
                    }
                }
                break;

            case "NON" :
                if ($unitprice >= 0) {
                    $newTierPrices[] = array(
                        'website_id' => 0,
                        'cust_group' => $this->groupids['ALL'],
                        'price_qty' => $quantity,
                        'price' => $unitprice,
                        'website_price' => $unitprice
                    );
                }
                break;
        }

        return $newTierPrices;
    }

    /**
     * Grabs product data for this sku from GP
     * Or from the local cache for this run if available.
     *
     * @param $sku - gp sku of product to request.
     * @return mixed - data from GP / cache.
     */
    private function getGPProductData($sku) {
        //echo "Getting $sku product data\n";
        if (!isset($this->gpSkuDataCache[$sku])) {
            $this->gpSkuDataCache[$sku] = $this->gp->getProductBySku($sku);
        }
        return $this->gpSkuDataCache[$sku];
    }

    /**
     * Keeps a count of the number of skus we've run through so far.
     * Returns a boolean of whether we've hit the max.
     *
     * @param int $add - amount to add to our count
     * @return bool
     */
    private function pastTotalSkusForThisRun($add = 0) {
        $this->totalProducts += $add;
        return ($this->totalProducts >= self::MAX_SKUS_PER_RUN);
    }

    /**
     * Get's data from GP (or cache) and then updates the products
     * Called from $this->processAttSet.
     *
     * @param $skus - array of gp skus to update from GP.
     */
    function processSkus($skus) {
        foreach ($skus as $sku) {
            $this->log("Processing gp_sku = " . $sku);
            $past = $this->pastTotalSkusForThisRun(1);
            if ($past) {
                return; // lets stop the run
            }

            // Get GP data
            $data = $this->getGPProductData($sku);
            $data->mySku = $sku;

            // update data in Magento.
            $this->_updateProductData($data, $sku);
        }
    }

    /**
     * Given an attribute set, returns an array of GP skus of products that qualify to get pricing updates.
     * Specifically, the products must have a nonempty value for gp_sku and be of type simple, virtual, or downloadable.
     *
     * Called by $this->processAttSet()
     *
     * @param $att - Attribute set for which to grab set of products.
     * @return array skus - the skus that were matched.
     */
    function getProductSkusByAttributeSet($att) {
        $this->_workingAttributeSet = $att;

        $products = $this->getProductCollection();
        $products = $products->addFieldToFilter('attribute_set_id', $att['attribute_set_id'])
                ->addAttributeToFilter('gp_sku', array('notnull' => true))
                ->addAttributeToFilter('gp_sku', array('neq' => ""))
                ->setOrder('gp_sku');
        $products->load();

        $skus = array();
        foreach ($products as $product) {
            if ($product->getGpSku() != "") {
                $skus[] = $product->getGpSku();
            } else {
                //echo "not gp_SKU=" . $product->getId();
            }
        }
//echo "skus are ".print_r($skus);
//die;

        return $skus;
    }

    /**
     * Processes an attribute set worth of products
     * Calls $this->processSkus()
     *
     * @param $attrSet - the attribute set to process.
     * @return Gorilla_Greatplains_Model_Product
     */
    function processAttSet($attrSet) {
        $this->logname = $attrSet['attribute_set_name'];
        $this->log("Running attribute set: " . $this->logname);

        // Get all gp skus for this attribute set that are valid for GP info update.
        $skus = $this->getProductSkusByAttributeSet($attrSet);
        $this->log("Count of skus is " . count($skus));

        // if there are any skus returned
        if (count($skus) > 0) {

            $skusForThisCall = array();
            $count = 0;
            foreach ($skus as $sku) {
                // check to make sure we haven't already passed the limit of skus to update.
                $past = $this->pastTotalSkusForThisRun(0);
                if ($past) {
                    return $this;
                }

                $count++;
                $skusForThisCall[] = $sku;
                if ($count >= self::MAX_SKUS_PER_GP_CALL) {

                    // process these skus
                    $this->processSkus($skusForThisCall);

                    // reset array and counter
                    $skusForThisCall = array();
                    $count = 0;
                }
            }
            /*
             * If there's any skus that weren't processed yet
             * because there were less than MAX_SKUS_PER_GP_CALL in the last run of the loop
             * Let's run them now.
             */
            if (count($skusForThisCall) > 0) {
                $this->processSkus($skusForThisCall);
            }
        }
        return $this;
    }

    /**
     * Grab list of attribute sets from Magento and set them to the attributesets class variable.
     *
     * @return Gorilla_Greatplains_Model_Product
     */
    private function getAttributeSets() {
        $this->log(__METHOD__ . ": Start");

        $attributeSet = Mage::getModel("eav/entity_attribute_set")->getCollection();

        $this->attributesets = array();
        foreach ($attributeSet as $at) {
            $this->attributesets[] = array(
                'attribute_set_id' => $at->getAttributeSetId(),
                'attribute_set_name' => $at->getAttributeSetName()
            );
        }
        $this->log(print_r($this->attributesets, true));
        $this->log(__METHOD__ . ": Done");

        return $this;
    }

    /**
     * Grab list of customer groups from Magento and set them to the groupids class variable.
     *
     * @return Gorilla_Greatplains_Model_Product
     */
    public function setGroupIds() {
        $this->log(__METHOD__ . ": Start");

        $this->groupids = array();
        $groups = Mage::getModel('customer/group')->getCollection();
        foreach ($groups as $group) {
            $this->groupids[$group['customer_group_code']] = $group['customer_group_id'];
        }
        $this->groupids['ALL'] = 32000;

        $this->log($this->groupids, true);
        $this->log(__METHOD__ . ": Done");

        return $this;
    }

    /**
     * Sets some internals of the script. Called by $this->run()
     *
     * Sets up soap connection to GP.
     * Grabs customer groups
     * Grabs attribute sets
     */
    public function initProductUpdate() {
        $this->log(__METHOD__ . ": Start");
        // Set GP soap connection.
        $this->gp = new Gorilla_Greatplains_Model_Soap();

        $this->setGroupIds();
        $this->getAttributeSets();
        //$this->initCSV();

        $this->log(__METHOD__ . ": Done");
    }

    /**
     * The main loop of the product update. This is called by $this->updateProductData()
     *
     * Initializes some internal variables and then runs through each attribute set.
     */
    private function run() {
        $this->log("Start of product update. Main Loop.");

        $this->setScriptStartTime();
        $this->setStartTime();
        
        $this->log("setScriptStartTime --- " . $this->getScriptStartTime());
        $this->log("StartTime --- " . $this->getStartTime());


        // Setting some Magento internal stuff.
        Mage::app('default')->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        Mage::register('isSecureArea', true);

        $this->initProductUpdate();

        if ($this->attId == "") {
            $this->log("Running ALL attributes");
            foreach ($this->attributesets as $attrSet) {
                $past = $this->pastTotalSkusForThisRun(0);
                if ($past) {
                    return; // lets stop the run
                }
                $this->processAttSet($attrSet);
            }
        } else {
            $this->log("Running attribute $this->attId");
            foreach ($this->attributesets as $attrSet) {
                if ($attrSet['attribute_set_id'] == $this->attId) {
                    $past = $this->pastTotalSkusForThisRun(0);
                    if ($past) {
                        return; // lets stop the run
                    }
                    $this->processAttSet($attrSet);
                    break;
                }
            }
        }

        $this->log("Total number of prods matchd in Magento: " . $this->totalProducts);
        $this->log("Total number of products update from GP: " . $this->numProdsWData);
        $this->log("Total number of gp_skus not found in GP: " . $this->numProdsNoData);
        $this->log("End of product update. Main Loop.");
    }

    /**
     * Main function.
     * Calling this runs the script to get updates for all the products.
     * After a successful run, it will reindex the price index and clear all the caches,
     * so changes made can be seen on the frontend.
     */
    public function UpdateProductData() {
        try {
            $this->run();
            $this->log("End of update. About to run price reindex.");
            Mage::getModel('catalog/product_indexer_price')->reindexAll();
            $this->log("Price reindex done. Clearing cache.");
            Mage::getModel('enterprise_pagecache/observer')->cleanCache();
            $emailBody = "UpdateProductData cron was successfully finished! " . date("Y-m-d H:i:s");
            $this->sendNotificationEmail("UpdateProductData cron was successfully finished! ", $emailBody);
        } catch (Exception $e) {
            $this->log("Problem running product update :");
            $this->log(print_r($e, true));
            $emailBody = "UpdateProductData cron was failed! " . date("Y-m-d H:i:s");
            $emailBody .="\nReason: " . $e->getMessage();
            $this->sendNotificationEmail("UpdateProductData cron was failed! ", $emailBody);
        }
    }

    /**
     * @deprecated
     */
    private function initCSV() {
        //  $line = array("gp_sku", "sku", "hasDescription", "hasTierPricing", "hasTaxes", "hasWeight", "hasInventory");
        // $fp = fopen('productupdate.'.$this->logname.".csv", 'w');
        //fputcsv($fp, $line);
        //fclose($fp);
    }

    /**
     * @deprecated
     */
    private function writeCSV($line) {
        //     $fp = fopen('productupdate.'.$this->logname.".csv", 'a');
        //    fputcsv($fp, $line);
        //   fclose($fp);
    }

    /**
     * @deprecated
     */
    private function updateProductBySku($sku) {
        $data = $this->getDataFromGP($sku);
        $this->updateProductData($data);
        return $this;
    }

    /**
     * @deprecated
     */
    private function getDataFromGP($sku) {
        return $this->gp->getProductBySku($sku);
    }

    /**
     * Called by $this->processSkus() (which is just a wrapper for this one).
     *
     * @param $skus - array of gp_skus to update from GP.
     * @deprecated
     */
    function getProductsDataFromGp($skus) {
//        $datacache = array("gp_sku" => null, 'data' => null);

        foreach ($skus as $sku) {
//            if ($datacache['gp_sku'] != $sku) {
//                $datacache['gp_sku'] = $sku;
//                $datacache['data'] = $this->gp->getProductBySku($sku);
//            }
//            $data = $datacache['data'];
            // Make call to GP soap for this product
            $data = $this->getGPProductData($sku);
            $data->mySku = $sku;

            // update data in Magento.
            $ret = $this->_updateProductData($data);
            if (!$ret) {
                $this->log("No data for " . $sku);
            }
        }
    }

    /**
     * @deprecated
     */
    function getProductByGpSku($gpsku) {

        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('gp_sku');

        $collection->addFieldToFilter(array(array('attribute' => 'gp_sku', 'eq' => $gpsku)));
        if ($collection->getSize() > 0) {

            $firstproduct = $collection->getFirstItem();
            $product = Mage::getModel('catalog/product');

            $sk = $firstproduct->getSku();

            $productId = $product->getIdBySku($sk);

            return $product->load($productId);
        }

        $product = Mage::getModel('catalog/product');

        $productId = $product->getIdBySku($gpsku);
        return $product->load($productId);
    }

    /**
     * Returns skus that were not matched in GP.
     * These skus should be accumulated as the script runs.
     *
     * @deprecated
     * @return mixed
     */
    public function getOnesNotFound() {
        return $this->cannotfind;
    }

    /**
     * Send notifications
     *
     *
     */
    public function sendNotificationEmail($subject = "Notification", $body = "") {
        if (Mage::getStoreConfigFlag('system/smtp/disable')) {
            return $this;
        }

        if (!Mage::getStoreConfig(self::XML_PATH_GREATPLAINS_PRODUCT_UDATEDATA_CRON_NOTIFICATION_ACTIVE)) {
            //echo "Module is disabled!";
            return;
        }

        $to = (string) Mage::getStoreConfig(self::XML_PATH_GREATPLAINS_PRODUCT_UDATEDATA_CRON_RECIPIENT_EMAIL);
        if (empty($to)) {
            //echo "Can't send email. Please fill Notifications Recipient Email!";
            Mage::log("Can't send email. Please fill Notifications Recipient Email!", null, $this->logname, true);
            return;
        }

        $mail = new Zend_Mail();
        $mail->addTo($to);

        $cc = (string) Mage::getStoreConfig(self::XML_PATH_GREATPLAINS_PRODUCT_UDATEDATA_CRON_CC_EMAIL);
        if (!empty($cc)) {
            $mail->addCc($cc);
        }
        $mail->setBodyText($body);
        $mail->setSubject($subject);
        $mail->setFrom((string) Mage::getStoreConfig(self::XML_PATH_GREATPLAINS_PRODUCT_UDATEDATA_CRON_SENDER_EMAIL));

        try {
            $mail->send();
            Mage::log("Email was successfully send ", null, $this->logname, true);
        } catch (Exception $e) {
            Mage::log("Can't send email. Error: " . $e->getMessage(), null, $this->logname, true);
        }
    }

}