<?php
ini_set("memory_limit", "512M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');

$customer = new Local_Customer();

$email = "rsuess@gorillagroup.com";



$process = new processHistoricalOrders();

$process -> run();

class processHistoricalOrders {

	const TASK_DOWNLOADABLE = "downloadable";
	const TASK_PREMIUM = "premium";
	const TASK_DOE = "doe";

	//const FILE_DOWNLOADABLE = "downloadable_ecodes.csv";
	const FILE_DOWNLOADABLE = "fixed.csv";
	const FILE_PREMIUM = "premium_ecodes.csv";
	const FILE_DOE = "doe_ecodes.csv";

	private $currentRunningTask;

	private $data;
	private $orderArray;
	private $notfound = array();
	private $defaultemail = "historicalOrders@iccsafe.org";

	private $storeid;
	public $filename = "downloadable_ecodes.csv";

	private $success = false;
	//
	private $fail_reason = "";

	private $old_order_id = "";
	//
	private $orderId = "";
	//
	private $verified = false;
	private $orderData = "";

	function __construct() {
		//$this -> gp = new Gorilla_Greatplains_Model_Soap();

		Mage::app();

	}

	function setStatusOnOrder() {
		echo "Setting status on order -----------------------------------\n\n\n\n\n";
		$dataset = array();
		if ($this -> success && $this->fail_reason == "") {
			$dataset[] = "Success";
			$dataset[] = date('Y-m-d H:i:s');
			$dataset[] = $this -> orderId;
			$dataset[] = $this -> currentRunningTask;
			$dataset[] = $this -> fail_reason;
		} else {
			$dataset[] = "Fail";
			$dataset[] = date('Y-m-d H:i:s');
			$dataset[] = $this -> old_order_id;
			$dataset[] = $this -> currentRunningTask;
			$dataset[] = $this -> fail_reason;
		}


		$this -> verifyOrder($this -> orderId, $this -> orderData);
		if ($this -> verified) {
			$dataset[] = "Verified";
		} else {
			$dataset[] = "NOT Verified ";
		}

		$dataset[] = $this -> old_order_id;

		print_r($dataset);
		$this -> writeStatusCsv($dataset);

		echo "written\n";
		$this -> success = false;
		$this -> fail_reason = "";
		$this -> orderId = "";
		$this -> orderData = "";
		$this -> verified = false;
		$this -> old_order_id = false;
		echo "Done setting status on order\n";

	}

	function run() {

		$this->log( "Running DOE\n");
		/*
		 *  RUN DOE
		*/
/*
		$this -> currentRunningTask = self::TASK_DOE;
		$this -> filename = self::FILE_DOE;
		$this -> loadFile();
		$this -> processLinesToOrderArray();

		foreach ($this->orderArray as $email => $customerOrders) {
			$customer = $this -> getCustomer($customerOrders);
			foreach ($customerOrders['orders'] as $data) {
				$this -> createOrder($data, $customer);
				$this->setStatusOnOrder();
			}
			unset($customer);
		}
*/
		$this->log( "Running premium\n");
		/*
		 *  RUN PREMIUM
		*/
/*
		$this -> currentRunningTask = self::TASK_PREMIUM;
		$this -> filename = self::FILE_PREMIUM;
		$this -> loadFile();
		$this -> processLinesToOrderArray();

		foreach ($this->orderArray as $email => $customerOrders) {
			$customer = $this -> getCustomer($customerOrders);
			foreach ($customerOrders['orders' ] as $data) {
				$this -> createOrder($data, $customer);
				echo "Done creating order about to set status\n";
				$this->setStatusOnOrder();
			}
			unset($customer);
		}
	*/		
		$this->log( "Running downloadables\n");
		/*
		 *  RUN DOWNLOADABLES
		*/

		$this -> currentRunningTask = self::TASK_DOWNLOADABLE;
		$this -> filename = self::FILE_DOWNLOADABLE;
		$this -> loadFile();
		$this -> processLinesToOrderArray();

		foreach ($this->orderArray as $email => $customerOrders) {
			$customer = $this -> getCustomer($customerOrders);
			foreach ($customerOrders['orders'] as $data) {
				$this -> orderData = $data;
				$this -> createOrder($data, $customer);
				$this -> setStatusOnOrder();
			}
			unset($customer);
		}

	}

	function createOrder($data, $_customer) {
		 echo "creating order\n\n\n";
		$time = strtotime($data['order_datetime']);

		$this -> old_order_id = $data['old_order_id_a'] . " / " . $data['old_order_id_b'];


echo "checking products\n\n\n";
		if (!$this -> checkProducts($data['products'])) {
			$this -> logFailure("------------------------------------------------------");
			$this -> logFailure("Product Not Found");
			//$this->fail_reason = "Product Not Found";
			$this -> logFailure(print_r($data, true));

			return;

		}


		$transaction = Mage::getModel('core/resource_transaction');
		$storeId = $_customer -> getStoreId();
		$reservedOrderId = Mage::getSingleton('eav/config') -> getEntityType('order') -> fetchNewIncrementId($storeId);
		$order = Mage::getModel('sales/order') -> setIncrementId($reservedOrderId) -> setStoreId($storeId) -> setQuoteId(0);
		$order -> setCustomer_email($_customer -> getEmail()) -> setCustomerFirstname($_customer -> getFirstname()) -> setCustomerLastname($_customer -> getLastname()) -> setCustomerGroupId($_customer -> getGroupId()) -> setCustomer_is_guest(0) -> setCustomer($_customer);

		$customer_address = $_customer -> checkAddress($data);

		

		try {

			$customAddress = Mage::getModel('sales/order_address');
			$customAddress -> setData($customer_address -> getData()) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING) -> setCustomerId($_customer -> getId()) -> setIsDefaultBilling('1') -> setIsDefaultShipping('1') -> setSaveInAddressBook('1');

			$order -> setBillingAddress($this -> processBillingAddress($customer_address, $_customer));

			$customAddress = Mage::getModel('sales/order_address');
			$customAddress -> setData($customer_address -> getData()) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) -> setCustomerId($_customer -> getId()) -> setIsDefaultBilling('1') -> setIsDefaultShipping('1') -> setSaveInAddressBook('1');
			$order -> setShippingAddress($this -> processShippingAddress($customer_address, $_customer));
		} catch(Exception $e) {
			echo "failure ".$e->getMessage()."\n";
			$this -> logFailure("Error creating customer address " . $e -> getMessage());

		}

		$orderPayment = Mage::getModel('sales/order_payment') -> setStoreId($storeId) -> setCustomerPaymentId(0) -> setMethod('checkmo');
		$order -> setPayment($orderPayment);
		$subTotal = 0;

		echo "starting to add products\n";

		foreach ($data['products'] as $_product) {

			echo "adding product\n";
			$product = Mage::getModel('catalog/product');

			$this -> log("Added Product ", $_product['eCodes_ID']);
			$product -> load($product -> getIdBySku($_product['eCodes_ID']));

			if (!$product) {
				echo "cannot find product\n";
				$this -> logFailure("cannot find product " . $_product['eCodes_ID'] . "\n");
			}
				

			$qty = $_product['product_qty'];
			if ($_product['product_qty'] == 0 || $_product['product_qty'] == 'n/a') {
				$qty = 1;
			}

			echo "product qty ".$qty,"\n";
			$price = $_product['line_item_total'] / $qty;
			$rowTotal = $_product['line_item_total'];

			$orderItem = Mage::getModel('sales/order_item') -> setStoreId($storeId) -> setQuoteItemId(0) -> setQuoteParentItemId(NULL) -> setProductId($product -> getId()) -> setProductType($product -> getTypeId()) -> setQtyBackordered(NULL) -> setTotalQtyOrdered($qty) -> setQtyOrdered($qty) -> setName($product -> getName()) -> setSku($product -> getSku()) -> setPrice($price) -> setBasePrice($price) -> setOriginalPrice($price) -> setRowTotal($rowTotal) -> setBaseRowTotal($rowTotal) -> setProductOptions(array($options));

			$subTotal += $rowTotal;
			$order -> addItem($orderItem);
			echo "done adding product\n";

		}

		echo "setting subtotal\n";



		$order -> setSubtotal($subTotal) -> setBaseSubtotal($subTotal) -> setGrandTotal($subTotal) -> setBaseGrandTotal($subTotal);
		echo "saving\n";
		$order -> save();

		echo "starting transaction\n";
		$transaction -> addObject($order);

		$transaction -> addCommitCallback(array($order, 'place'));

		$transaction -> addCommitCallback(array($order, 'save'));

		$order -> save();
		echo "saved order\n";
		$order -> load();
		echo "order loaded\n";
		$this -> orderId = $order -> getId();

		try {
			echo "creating order\n";
			$invoice = Mage::getModel('sales/service_order', $order) -> prepareInvoice();
			$invoice -> setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
			$invoice -> register();

			$transaction = Mage::getModel('core/resource_transaction') -> addObject($invoice) -> addObject($invoice -> getOrder());
			echo "saving order\n";
			$transaction -> save();
		} catch(Exception $e) {
			echo "fail ".$e->getMessages();
			$this -> fail_reason = $e -> getMessage();
			$this -> logFailure("Creating Invoice error : " . $e -> getMessage());

		}
		//print_r($data);
		//die;

		if ($this -> currentRunningTask == self::TASK_DOWNLOADABLE || $this -> currentRunningTask == self::TASK_DOE) {

			$this -> addDownloadableData($order, $data);
		}
		if ($this -> currentRunningTask == self::TASK_PREMIUM) {
			$this -> addPremiumData($data, $_customer);
		}


		$order -> setOldOrderIdA($data['old_order_id_a']);
		if ($data['old_order_id_b'] != "NULL")
			$order -> setOldOrderIdB($data['old_order_id_b']);

		$date = Mage::getModel('core/date') -> timestamp($time);
		$order -> setCreatedAt($date);

		$this -> log("Saving order");

		try {
			echo " saving 1\n";
			$order -> setStatus('complete');
			echo " saving 2\n";
			$order -> addStatusToHistory($order -> getStatus(), 'Historicical order completed', false);
			echo " saving 3\n";
			$order -> save();
			echo " saving 4\n";
			$this -> success = true;
			echo " saving 5\n";
		} catch(Exception $e) {
			$this -> logFailure("Error changing status : " . $e -> getMessage());
			$this -> fail_reason = $e -> getMessage();

		}

		return $this;

	}

	function addPremiumData($data, $_customer) {
		//echo "--------------------\n\n\n\n";
		//print_r($data);
		//die;
		$c = new ICC_Customer_Model_Customer();
		$c -> load($_customer -> getId());
		$c -> createEcodesMasterAccount($data['subscription_master_user_name'], $data['subscription_master_password'], $data['subscription_master_password']);

	}

	function addDownloadableData($order, $data) {
		$items = $order -> getAllItems();

		foreach ($data['products' ] as $_product) {

			$added = false;
			foreach ($items as $item) {

				if ($added) {
					continue;
				}
				if ($this -> alreadyHasSerial($item, $_product['download_serial_number'], $data)) {
					continue;
				}

				if ($_product['eCodes_ID'] == $item -> getSku()) {

					$product = Mage::getModel('catalog/product');
					$product -> load($product -> getIdBySku($_product['eCodes_ID']));

					$qty = $_product['product_qty'];
					if ($_product['product_qty'] == 0 || $_product['product_qty'] == 'n/a') {
						$qty = 1;
					}

					$this -> processDownloadable($_product, $item -> getId(), $product, $time, $item);

					/*
					 * Create Link
					*/

					$links = $product -> getTypeInstance(true) -> getLinks($product);

					$linkIds = array();
					foreach ($links as $link) {
						$linkIds[] = $link -> getLinkId();
					}
					$linkPurchased = Mage::getModel('downloadable/link_purchased');

					Mage::helper('core') -> copyFieldset('downloadable_sales_copy_order', 'to_downloadable', $item -> getOrder(), $linkPurchased);

					Mage::helper('core') -> copyFieldset('downloadable_sales_copy_order_item', 'to_downloadable', $item, $linkPurchased);

					$linkSectionTitle = ($product -> getLinksTitle() ? $product -> getLinksTitle() : Mage::getStoreConfig(Mage_Downloadable_Model_Link::XML_PATH_LINKS_TITLE));

					$linkPurchased -> setLinkSectionTitle($linkSectionTitle) -> save();

					foreach ($linkIds as $linkId) {
						if (isset($links[$linkId])) {
							$linkPurchasedItem = Mage::getModel('downloadable/link_purchased_item') -> setPurchasedId($linkPurchased -> getId()) -> setOrderItemId($item -> getId());
							Mage::helper('core') -> copyFieldset('downloadable_sales_copy_link', 'to_purchased', $links[$linkId], $linkPurchasedItem);
							$linkHash = strtr(base64_encode(microtime() . $linkPurchased -> getId() . $item -> getId() . $product -> getId()), '+/=', '-_,');
							$numberOfDownloads = 6;
							$linkPurchasedItem -> setLinkHash($linkHash) -> setNumberOfDownloadsBought($numberOfDownloads) -> setNumberOfDownloadsUsed($numberOfDownloads - $data['download_remaining_downloads']) -> setStatus(Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_AVAILABLE) -> setCreatedAt($item -> getCreatedAt()) -> setUpdatedAt($item -> getUpdatedAt()) -> save();
						}
					}
					$added = true;

				}

			}
		}
		return $this;
	}

	function processDownloadable($historicalproduct, $id, $magentoproduct, $timestamp, $orderItem) {
		$this -> log("processing key");
		$write = Mage::getSingleton('core/resource') -> getConnection('core_write');

		$data = array('id' => NULL, 'serial' => $historicalproduct['download_serial_number'], 'order_item_id' => $id, 'enabled' => '1', 'updated_at' => CURRENT_TIMESTAMP, 'created_at' => date('Y-m-d H:i:s', $timestamp), 'gp_sku' => $magentoproduct -> getGpSku(), 'document_id' => $magentoproduct -> getSku(), 'product_title' => mysql_real_escape_string($magentoproduct -> getName()), );
		$write -> insert('ecodes_downloadable', $data);

		$this -> log("Done Processing Key");

	}

	function getCustomer($custdata) {
		if ($custdata['email'] == "") {
			$email = $defaultemail;
		} else {
			$email = $custdata['email'];
		}
		$customer = new Local_Customer();

		$customer = $customer -> loadCustomerByEmail($email);
		if (!$customer) {
			$this -> log("---------------------------");
			$this -> log("Creating new customer");
			$this -> log(print_r($custdata, true));

			$customer = new Local_Customer();
			$customer = $customer -> createNewCustomer($custdata);
		}
		return $customer;
	}

	function alreadyHasSerial($item, $serial, $original) {
		// print_r($item -> getData());
		//die;
		$connection = Mage::getSingleton('core/resource') -> getConnection('core_read');
		$select = $connection -> select() -> from('ecodes_downloadable', array('*')) -> where('order_item_id=?', $item -> getId());
		$rowsArray = $connection -> fetchAll($select);
		// echo "-----------------\n";
		// print_r($rowsArray);
		//die;
		if (count($rowsArray) > 0) {
			//    echo "ignoring this one already has\n";
			return true;
		}

		$connection = Mage::getSingleton('core/resource') -> getConnection('core_read');
		$select = $connection -> select() -> from('ecodes_downloadable', array('*')) -> where('serial=?', $serial);

		$rowsArray = $connection -> fetchAll($select);
		if ($rowsArray) {

			$this -> logFailure("------------------------------------------------------");
			$this -> logFailure("Serial already taken " . $serial);
			$this -> logFailure(" Item : " . print_r($item -> debug(), true));
			$this -> logFailure("Original " . print_r($original, true));
			//   print_r($rowsArray);

			//   die ;
			return true;
		}
		return false;

	}

	function processBillingAddress($address, $customer) {
		$billingAddress = Mage::getModel('sales/order_address') -> setStoreId($this -> storeId) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING) -> setCustomerId($customer -> getId()) -> setPrefix($address -> getPrefix()) -> setFirstname($address -> getFirstname()) -> setMiddlename($address -> getMiddlename()) -> setLastname($address -> getLastname()) -> setSuffix($address -> getSuffix()) -> setCompany($address -> getCompany()) -> setStreet($address -> getStreet()) -> setCity($address -> getCity()) -> setCountry_id($address -> getCountryId()) -> setRegion($address -> getRegion()) -> setRegion_id($address -> getRegionId()) -> setPostcode($address -> getPostcode()) -> setTelephone($address -> getTelephone()) -> setFax($address -> getFax());
		return $billingAddress;

	}

	function processShippingAddress($address, $customer) {
		$billingAddress = Mage::getModel('sales/order_address') -> setStoreId($this -> storeId) -> setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) -> setCustomerId($customer -> getId()) -> setPrefix($address -> getPrefix()) -> setFirstname($address -> getFirstname()) -> setMiddlename($address -> getMiddlename()) -> setLastname($address -> getLastname()) -> setSuffix($address -> getSuffix()) -> setCompany($address -> getCompany()) -> setStreet($address -> getStreet()) -> setCity($address -> getCity()) -> setCountry_id($address -> getCountryId()) -> setRegion($address -> getRegion()) -> setRegion_id($address -> getRegionId()) -> setPostcode($address -> getPostcode()) -> setTelephone($address -> getTelephone()) -> setFax($address -> getFax());
		return $billingAddress;

	}

	function checkProducts($products) {
		foreach ($products as $_product) {

			$product = Mage::getModel('catalog/product');
			$product -> load($product -> getIdBySku($_product['eCodes_ID']));
			// echo $product->getSku()." ".$_product['eCodes_ID']."\n";
			if (!$product -> getSku()) {
				$this->fail_reason = $_product['eCodes_ID'];
				echo "Cannot find " . $_product['eCodes_ID'] . "\n";
				return false;
			}
		}
		return true;
	}

	function logFailure($message) {
		//   echo "Fail : " . $message . "\n";
		Mage::Log(date("m-d-y G:i:s :: ", time()) . " : " . $message, 3, 'historical_import_failure.log');
	}

	function log($message) {
		echo $message . "\n";
		Mage::Log(date("m-d-y G:i:s :: ", time()) . " : " . $message, 3, 'historical_import.log');
	}

	function verifyOrder($orderId, $orderData) {

		$order = Mage::getModel('sales/order') -> load($orderId);
		if(!$order->getId())
		{
			//echo "NONONONO\n";
			$this->verified = false;
			//die;
			return;
		}
		//print_r($order -> getData());

		//die;
		$this -> verified = true;
		return true;
	}

	function writeStatusCsv($data) {
		echo "Writing status\n";
		try {
			$fp = fopen('historicalOrdersStatusFile.csv', 'a');
			fputcsv($fp, $data);
			fclose($fp);
		} catch(Exception $e) {
			echo $e -> message();
			echo "\n";
		}
		echo "Done Writing Status\n";

	}

	function processLinesToOrderArray() {
		$notfound = array();
		$orderArray = array();
		$product = array();
		//Mage::getModel('catalog/product');
		foreach ($this->data as $line) {

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

			$orderArray[$email]['first_name'] = $first_name;
			$orderArray[$email]['last_name'] = $last_name;
			$orderArray[$email]['email'] = $email;
			$orderArray[$email]['member_nu'] = $member_nu;
			$orderArray[$email]['bill_street'] = $bill_street;
			$orderArray[$email]['bill_city'] = $bill_city;
			$orderArray[$email]['bill_state'] = $bill_state;
			$orderArray[$email]['bill_zip'] = $bill_zip;
			$orderArray[$email]['bill_country'] = $bill_country;
			$orderArray[$email]['bill_phone'] = $bill_phone;
				
				
				
			$orderArray[$email]['orders'][$old_order_id_a]['first_name'] = $first_name;
			$orderArray[$email]['orders'][$old_order_id_a]['last_name'] = $last_name;

			$orderArray[$email]['orders'][$old_order_id_a]['member_nu'] = $member_nu;
			$orderArray[$email]['orders'][$old_order_id_a]['status'] = $status;
			$orderArray[$email]['orders'][$old_order_id_a]['old_order_id_a'] = $old_order_id_a;
			$orderArray[$email]['orders'][$old_order_id_a]['old_order_id_b'] = $old_order_id_b;
			$orderArray[$email]['orders'][$old_order_id_a]['order_datetime'] = $order_datetime;
			$orderArray[$email]['orders'][$old_order_id_a]['status'] = $status;
			$orderArray[$email]['orders'][$old_order_id_a]['member_nu'] = $member_nu;
			$orderArray[$email]['orders'][$old_order_id_a]['bill_street'] = $bill_street;
			$orderArray[$email]['orders'][$old_order_id_a]['bill_city'] = $bill_city;
			$orderArray[$email]['orders'][$old_order_id_a]['bill_state'] = $bill_state;
			$orderArray[$email]['orders'][$old_order_id_a]['bill_zip'] = $bill_zip;
			$orderArray[$email]['orders'][$old_order_id_a]['bill_country'] = $bill_country;
			$orderArray[$email]['orders'][$old_order_id_a]['bill_phone'] = $bill_phone;
			$orderArray[$email]['orders'][$old_order_id_a]['products'][] = $product;
				
			$orderArray[$email]['orders'][$old_order_id_a]['subscription_master_user_name'] = $subscription_master_user_name;
			$orderArray[$email]['orders'][$old_order_id_a]['subscription_master_password'] = $subscription_master_password;
		}

		$this -> orderArray = $orderArray;

		//print_r ($notfound);
	}

	function loadFile() {
		//echo "Loading file ";
		if (($handle = fopen($this -> filename, "r")) !== FALSE) {

			$this -> data = array();

			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$this -> data[] = $data;
			}
			fclose($handle);

		}
		array_shift($this -> data);

	}

}
