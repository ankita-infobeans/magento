<?php
ini_set("memory_limit", "1024M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');

$process = new newProcessHistoricalOrders();
$process->run();

class newProcessHistoricalOrders {

	const TASK_DOWNLOADABLE = "downloadable";
	const TASK_PREMIUM = "premium";
	const TASK_DOE = "doe";

	const FILE_DOWNLOADABLE = "ecodes_data_history_07112012.csv";
	//const FILE_DOWNLOADABLE = "fixed.csv";
	const FILE_PREMIUM = "ecodes_data_history_07112012_premium.csv";
	const FILE_DOE = "ecodes_data_history_07112012_DOE.csv";

	const ERROR_SPREADSHEET = "errors.csv";
	const LOGFILE = "logging.txt";

	public $data = array();
	public $filename = "";
	public $orderArray = array();
	private $defaultemail = "historicalOrders@iccsafe.org";

	private $starttime = "";
	private $endtime = "";
	private $laptime = "";

	private $fh = null;
	public function run()
	{
		$this->fh = fopen(self::LOGFILE, 'a') or die("can't open file");
		$starttime = time();
		$this->log("Starting Import Historical Orders");
		$this->log("Loading files into memory");
		$this->loadFilesIntoMemory();

		$total = count($this->data);
		$this->log("Processing $total lines");
		$this->processLinesToOrderArray();
		//echo "done";
		$totalorders = count($this->orderArray);
		$this->log("$totalorders orders found");
		
		$this->createOrders();
		fclose($this->fh);

	}

	/* Array
	 (
	 		[0] => email
	 		[1] => first_name
	 		[2] => last_name
	 		[3] => coupon_number
	 		[4] => old_order_id_a
	 		[5] => old_order_id_b
	 		[6] => order_datetime
	 		[7] => status
	 		[8] => member_nu
	 		[9] => bill_street
	 		[10] => bill_city
	 		[11] => bill_state
	 		[12] => bill_zip
	 		[13] => bill_country
	 		[14] => bill_phone
	 		[15] => product_name
	 		[16] => eCodes_ID   <= magento sku
	 		[17] => product_sku <- gp sku
	 		[18] => line_item_total
	 		[19] => product_qty
	 		[20] => download_serial_number
	 		[21] => download_remaining_downloads
	 		[22] => subscription_start_date
	 		[23] => subscription_end_date
	 		[24] => subscription_num_users
	 		[25] => subscription_master_user -user name
	 		[26] => subscription_master_password
	 		[27] => Download_subscription

	 		*/









	/*
	 * Order creation functions
	*/




	function createOrders() {

		echo "\n----------------Starting to create orders-------------------\n";
		$this->log("Creating ".count($this->orderArray)." orders");
		$count = 1;
		foreach ($this -> orderArray as $oldorderida => $order) {
			$this->laptime = time();
			echo "Creating order $count of ".count($this->orderArray)." : ";
			$this->log("Creating order $count of ".count($this->orderArray));
			
			$error = $this -> createOrder($order);

			if ($error) {

				//$this->logFailure( "Error creating order $oldorderida : " . $error );
				
			}
			$count++;
			$crosstime = time() - $this->laptime;
			echo "took $crosstime seconds";
			if($error)
			{
				echo "\r\n";
				
			}else{
				echo "\r";
			}
			
		}
		
		echo "\n----------------Done with $count orders-------------------\n";
	}

	
	
	function createOrder($_order) {
		
		
		$time = strtotime($_order['order_datetime']);

		$old_order_id = $_order['old_order_id_a'] . " / " . $_order['old_order_id_b'];

		$_customer = $this -> getCustomer($_order);
		
		//return;
		
		
		/*
		 * Simple product check to make sure product is in inventory
		* and product gp sku;s match
		*/
		
		$errors = $this -> checkProducts($_order['products']);
		if ($errors) {
			$this->logFailure($_order['old_order_id_a'].",".$errors,$_order);
			return $errors;
		}

		
		$transaction = Mage::getModel('core/resource_transaction');
		$storeId = $_customer -> getStoreId();
		$reservedOrderId = Mage::getSingleton('eav/config') -> getEntityType('order') -> fetchNewIncrementId($storeId);

		$this->log("reserve order id is $reservedOrderId");

		$order = Mage::getModel('sales/order') -> setIncrementId($reservedOrderId) -> setStoreId($storeId) -> setQuoteId(0);
		$order -> setCustomer_email($_customer -> getEmail()) -> setCustomerFirstname($_customer -> getFirstname()) -> setCustomerLastname($_customer -> getLastname()) -> setCustomerGroupId($_customer -> getGroupId()) -> setCustomer_is_guest(0) -> setCustomer($_customer);

		$customer_address = $_customer -> checkAddress($_order);

		try {

			$customAddress = Mage::getModel('sales/order_address');
			$customAddress -> setData($customer_address -> getData()) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING) -> setCustomerId($_customer -> getId()) -> setIsDefaultBilling('1') -> setIsDefaultShipping('1') -> setSaveInAddressBook('1');

			$order -> setBillingAddress($this -> processBillingAddress($customer_address, $_customer));

			$customAddress = Mage::getModel('sales/order_address');
			$customAddress -> setData($customer_address -> getData()) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) -> setCustomerId($_customer -> getId()) -> setIsDefaultBilling('1') -> setIsDefaultShipping('1') -> setSaveInAddressBook('1');
			$order -> setShippingAddress($this -> processShippingAddress($customer_address, $_customer));
		} catch(Exception $e) {
			//echo "failure " . $e -> getMessage() . "\n";
			$this->logFailure($_order['old_order_id_a'].",Error creating customer address," .$e -> getMessage(),$_order);
			
			//$this -> logFailure("Error creating customer address " . $e -> getMessage(),$_order);

		}
		$paymenttype = "checkmo";
		if ($_order['status'] == "credit_card") {
				$paymenttype = "ccsave";
		}
		if ($_order['status'] == "purchase_order") {
				$paymenttype = "purchaseorder";
		}

		$orderPayment = Mage::getModel('sales/order_payment') 
		-> setStoreId($storeId) 
		-> setCustomerPaymentId(0) 
		-> setMethod($paymenttype);
		
		$order -> setPayment($orderPayment);
		$subTotal = 0;

		//echo "starting to add products\n";

		foreach ($_order['products'] as $_product) {

			//echo "adding product\n";
			$product = Mage::getModel('catalog/product');

			$this -> log("Added Product ", $_product['eCodes_ID']);
			$product -> load($product -> getIdBySku($_product['eCodes_ID']));

			if (!$product) {
				//echo "cannot find product\n";
				$this->logFailure($_order['old_order_id_a'].",cannot find product,". $_product['eCodes_ID'],$_order);
				return "cannot find product " . $_product['eCodes_ID'];
			}

			$qty2 = $_product['product_qty'];
			if ($_product['product_qty'] == 0 || $_product['product_qty'] == 'n/a') {
				$qty2 = 1;
			}
			$qty = 1;
			//echo "product qty " . $qty, "\n";
			$price = $_product['line_item_total'] / $qty2;
			$rowTotal = $price;

			$orderItem = Mage::getModel('sales/order_item') 
			-> setStoreId($storeId) 
			-> setQuoteItemId(0) 
			-> setQuoteParentItemId(NULL) 
			-> setProductId($product -> getId()) 
			-> setProductType($product -> getTypeId()) 
			-> setQtyBackordered(NULL) 
			-> setTotalQtyOrdered($qty) 
			-> setQtyOrdered($qty) 
			-> setName($product -> getName()) 
			-> setSku($product -> getSku()) 
			-> setPrice($price) 
			-> setBasePrice($price) 
			-> setOriginalPrice($price) 
			-> setRowTotal($rowTotal) 
			-> setBaseRowTotal($rowTotal) 
			-> setProductOptions(array($options));

			$subTotal += $rowTotal;
			$order -> addItem($orderItem);
			//echo "done adding product\n";

		}

		$order -> setSubtotal($subTotal) 
		-> setBaseSubtotal($subTotal) 
		-> setGrandTotal($subTotal) 
		-> setBaseGrandTotal($subTotal);
		

		$order -> save();

		$this->log( "starting transaction");
		$transaction -> addObject($order);

		$transaction -> addCommitCallback(array($order, 'place'));

		$transaction -> addCommitCallback(array($order, 'save'));

		$order -> save();
		$this->log( "saved order");
		$order -> load();
		$this->log( "order loaded");
		$this -> orderId = $order -> getId();

		try {
			$this->log( "creating Invoice");
			$invoice = Mage::getModel('sales/service_order', $order) -> prepareInvoice();
			$invoice -> setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
			$invoice -> register();

			$transaction = Mage::getModel('core/resource_transaction') -> addObject($invoice) -> addObject($invoice -> getOrder());
			$this->log( "saving Invoice");
			$transaction -> save();
		} catch(Exception $e) {
			//echo "fail " . $e -> getMessages();
			//$this -> fail_reason = $e -> getMessage(); 
			$this->logFailure($_order['old_order_id_a'].",Error creating invoice,".$e -> getMessage() ,$_order);
			$this -> logFailure("Creating Invoice error : " . $e -> getMessage(),$_order);

		}
		//print_r($data);
		//die;
		/*
		 if ($this -> currentRunningTask == self::TASK_DOWNLOADABLE || $this -> currentRunningTask == self::TASK_DOE) {

		$this -> addDownloadableData($order, $data);
		}
		if ($this -> currentRunningTask == self::TASK_PREMIUM) {
		$this -> addPremiumData($_orderhistory, $_customer);
		}
		*/
		$order -> setOldOrderIdA($_order['old_order_id_a']);
		if ($_order['old_order_id_b'] != "NULL")
			$order -> setOldOrderIdB($_order['old_order_id_b']);

		$date = Mage::getModel('core/date') -> timestamp($time);
		$order -> setCreatedAt($date);

		$this -> log("Saving order");

		try {
			//echo " saving 1\n";
			$order -> setStatus('complete');
			//echo " saving 2\n";
			
			$order -> addStatusToHistory($order -> getStatus(),"Payment type: ". $_order['status'] , false);
			//echo " saving 3\n";
			$order -> save();
			//echo " saving 4\n";
			$this -> success = true;
			//echo " saving 5\n";
		} catch(Exception $e) {
			//echo("Error changing status : " . $e -> getMessage());
			$this -> fail_reason = $e -> getMessage();
			//$this -> logFailure("Saving Order error : " . $e -> getMessage(),$_order);

			$this -> logFailure("Saving Order error : " . $e -> getMessage(),$_order);
			
		}
		
		
		/*
		 * Do ecodes Specific stuff
		 */
		
		
		if($_order['subscription_master_user'] != "")
		{
			$this->addPremiumData($_order, $_customer);
		}

		
		return false;
	}


	/*
	 * data processing functions
	*
	*/

	function loadFilesIntoMemory() {
		$this -> data = array();

		/*
		 * load files into temp array
		*/
		$this -> filename = self::FILE_DOWNLOADABLE;
		$this -> loadFile();
		$this -> filename = self::FILE_PREMIUM;
		$this -> loadFile();
		$this -> filename = self::FILE_DOE;
		$this -> loadFile();


	}

	function processLinesToOrderArray() {
		$notfound = array();
		$orderArray = array();
		$product = array();
		//Mage::getModel('catalog/product');
		$count = 1;
		
		echo "--------------------Processing lines-------------------------\n";
		foreach ($this->data as $line) {

		
			echo "processing ".$count." of ".count($this->data)."\r";
			$email = $line[0];
			if ($email == "")
				$email = $this -> defaultemail;

			$first_name = $line[1];
			$last_name = $line[2];
			$coupon_number = $line[3];
			$old_order_id_a = $line[4];
			$old_order_id_b = $line[5];
			$order_datetime = $line[6];
			$status = $line[7];
			$member_nu = $line[8];
			$bill_street = $line[9];
			$bill_city = $line[10];
			$bill_state = $line[11];
			$bill_zip = $line[12];
			$bill_country = $line[13];
			$bill_phone = $line[14];
			$product_name = $line[15];
			$eCodes_ID = $line[16];
			$product_sku = $line[17];
			$line_item_total = $line[18];
			$product_qty = $line[19];
			$download_serial_number = $line[20];
			$download_remaining_downloads = $line[21];
			$subscription_start_date = $line[22];
			$subscription_end_date = $line[23];
			$subscription_num_users = $line[24];
			$subscription_master_user_name = $line[25];
			$subscription_master_password = $line[26];
			$download_subscription = $line[27];

			$product['product_name'] = $product_name;
			$product['eCodes_ID'] = $eCodes_ID;
			$product['product_sku'] = $product_sku;
			$product['line_item_total'] = $line_item_total;
			$product['product_qty'] = $product_qty;
			$product['download_serial_number'] = $download_serial_number;
			$product['download_remaining_downloads'] = $download_remaining_downloads;
			$product['subscription_start_date'] = $subscription_start_date;
			$product['subscription_end_date'] = $subscription_end_date;
			$product['subscription_num_users'] = $subscription_num_users;
			$product['subscription_master_user_name'] = $subscription_master_user_name;
			$product['subscription_master_password'] = $subscription_master_password;
			$product['download_subscription'] = $download_subscription;
			
			$this->orderArray[$old_order_id_a]['first_name'] = $first_name;
			$this->orderArray[$old_order_id_a]['last_name'] = $last_name;
			$this->orderArray[$old_order_id_a]['email'] = $email;
			$this->orderArray[$old_order_id_a]['member_nu'] = $member_nu;
			$this->orderArray[$old_order_id_a]['bill_street'] = $bill_street;
			$this->orderArray[$old_order_id_a]['bill_city'] = $bill_city;
			$this->orderArray[$old_order_id_a]['bill_state'] = $bill_state;
			$this->orderArray[$old_order_id_a]['bill_zip'] = $bill_zip;
			$this->orderArray[$old_order_id_a]['bill_country'] = $bill_country;
			$this->orderArray[$old_order_id_a]['bill_phone'] = $bill_phone;

			$this->orderArray[$old_order_id_a]['first_name'] = $first_name;
			$this->orderArray[$old_order_id_a]['last_name'] = $last_name;

			$this->orderArray[$old_order_id_a]['member_nu'] = $member_nu;
			$this->orderArray[$old_order_id_a]['status'] = $status;
			$this->orderArray[$old_order_id_a]['old_order_id_a'] = $old_order_id_a;
			$this->orderArray[$old_order_id_a]['old_order_id_b'] = $old_order_id_b;
			$this->orderArray[$old_order_id_a]['order_datetime'] = $order_datetime;
			$this->orderArray[$old_order_id_a]['status'] = $status;
			$this->orderArray[$old_order_id_a]['member_nu'] = $member_nu;
			$this->orderArray[$old_order_id_a]['bill_street'] = $bill_street;
			$this->orderArray[$old_order_id_a]['bill_city'] = $bill_city;
			$this->orderArray[$old_order_id_a]['bill_state'] = $bill_state;
			$this->orderArray[$old_order_id_a]['bill_zip'] = $bill_zip;
			$this->orderArray[$old_order_id_a]['bill_country'] = $bill_country;
			$this->orderArray[$old_order_id_a]['bill_phone'] = $bill_phone;
			$this->orderArray[$old_order_id_a]['products'][] = $product;

			$this->orderArray[$old_order_id_a]['subscription_master_user_name'] = $subscription_master_user_name;
			$this->orderArray[$old_order_id_a]['subscription_master_password'] = $subscription_master_password;
			//echo ".";
			$count++;

		}

		echo "\n--------------------Done Processing $count lines-------------------------\n";
		unset($this -> data);

	}



	/*
	 * Product verification functions
	*/

	function checkProducts($products) {
		//print_r($products);
		$failreason = "";
		foreach ($products as $_product) {

			//echo "checking product\n";
			$product = Mage::getModel('catalog/product');
			//echo "loaded product model\n\n";
			$product -> load($product -> getIdBySku($_product['eCodes_ID']));
			// echo $product->getSku()." ".$_product['eCodes_ID']."\n";
			if (!$product -> getSku()) {
				$this -> fail_reason = $_product['eCodes_ID'];
				return "Cannot find " . $_product['eCodes_ID'];

			}
			if ($_product['product_sku'] != $product -> getGpSku()) {
				if($product -> getGpSku() == "")
				return "gpsku empty,".$_product['eCodes_ID'].",".$_product['product_sku'];
				//return "for sku : ".$_product['eCodes_ID']. " - ". $_product['product_sku'] . " is empty";
				else
				return "gpsku mismatch,".$_product['eCodes_ID'].",".$_product['product_sku'].",". $product -> getGpSku();
				//return "for sku : ".$_product['eCodes_ID']. " - ". $_product['product_sku'] . " and " . $product -> getGpSku() . " do not match";
				
			}

		}
		return false;
	}

	/*
	 * customer functions
	*/

	function getCustomer($custdata) {
		// print_r($custdata);

		if ($custdata['email'] == "") {
			$email = $defaultemail;
		} else {
			$email = $custdata['email'];
		}
		//echo "\n\n".$email . "\n";
		$customer = new Local_Customer();

		$customer = $customer -> loadCustomerByEmail($email);

		if (!$customer) {
			$this->log("Creating new customer ".$email);
			//echo "Creating new customer\n\n\n";
			$customer = new Local_Customer();
			$customer = $customer -> createNewCustomer($custdata);
		}
		echo "got customer : ";

		// $customer -> checkAddress($custdata);

		return $customer;
	}

	function addPremiumData($data, $_customer) {
		//echo "--------------------\n\n\n\n";
		//print_r($data);
		//die;
		$c = new ICC_Customer_Model_Customer();
		$c -> load($_customer -> getId());
		$c -> createEcodesMasterAccount($data['subscription_master_user_name'], $data['subscription_master_password'], $data['subscription_master_password']);
	
	}

	
	function addSerial($item,$serialdata)
	{
		$model = Mage::getModel('ecodes/downloadable');
		$model->setProductTitle($item->getName());
		$model->setSerial($serialdata['serial']);
		$model->setOrderItemId($item->getItemId());
		$model->setEnabled(1);
		$model->setDocumentId($item->getSku());
		$model->setGpSku($serialdata['gp_sku']);
		if($model->getSerial() != "")
		{
			//echo "Saving Model\n";
			$model->save();
		}else{
		//	$this->logFailure( "Cannot save model ".print_r($model->debug(),true),$_order);
		//$this->logFailure('$_model, $_order)
			//die;
		}
	}

	/*
	 *
	* Generic Functions
	*/



	function loadFile() {
		//echo "Loading file ";
		$tmpdata = array();
		if (($handle = fopen($this -> filename, "r")) !== FALSE) {

			echo $this -> filename."\n";
			$tempdata = array();

			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$tmpdata[] = $data;

			}
			fclose($handle);

		}
		array_shift($tmpdata);
		foreach($tmpdata as $td)
			$this->data[] =$td;

	}

	function processBillingAddress($address, $customer) {
		$billingAddress = Mage::getModel('sales/order_address') -> setStoreId($this -> storeId) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING) -> setCustomerId($customer -> getId()) -> setPrefix($address -> getPrefix()) -> setFirstname($address -> getFirstname()) -> setMiddlename($address -> getMiddlename()) -> setLastname($address -> getLastname()) -> setSuffix($address -> getSuffix()) -> setCompany($address -> getCompany()) -> setStreet($address -> getStreet()) -> setCity($address -> getCity()) -> setCountry_id($address -> getCountryId()) -> setRegion($address -> getRegion()) -> setRegion_id($address -> getRegionId()) -> setPostcode($address -> getPostcode()) -> setTelephone($address -> getTelephone()) -> setFax($address -> getFax());
		return $billingAddress;

	}
	function toDate($time)
	{
		return date(DATE_RFC822,$time);
	}

	function processShippingAddress($address, $customer) {
		$billingAddress = Mage::getModel('sales/order_address') -> setStoreId($this -> storeId) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) -> setCustomerId($customer -> getId()) -> setPrefix($address -> getPrefix()) -> setFirstname($address -> getFirstname()) -> setMiddlename($address -> getMiddlename()) -> setLastname($address -> getLastname()) -> setSuffix($address -> getSuffix()) -> setCompany($address -> getCompany()) -> setStreet($address -> getStreet()) -> setCity($address -> getCity()) -> setCountry_id($address -> getCountryId()) -> setRegion($address -> getRegion()) -> setRegion_id($address -> getRegionId()) -> setPostcode($address -> getPostcode()) -> setTelephone($address -> getTelephone()) -> setFax($address -> getFax());
		return $billingAddress;

	}
	function logFailure($e,$order) {
		echo "ERROR : ".$e."\n";
		
		
		$fb = fopen(self::ERROR_SPREADSHEET, 'a') or die("can't open file");
		//$stringData = $this->toDate(time())." : ".$e."\n".print_r($order,true);
		//echo $stringData;
		fwrite($fb, $e);
		$this->log("ERROR : ".$e);
		fclose($fb);
		
		//echo "WTF $e";
		//die ;
	}

	function log($d) {

		

		$stringData = $this->toDate(time())." : ".$d."\n";
		//echo $stringData;
		fwrite($this->fh, $stringData);

		//fclose($fh);

	}

}
