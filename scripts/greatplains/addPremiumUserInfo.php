<?php
ini_set("memory_limit", "2048M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');

$apui = new addPremiumUserInfo();
$apui->run();

class addPremiumUserInfo
{
	const FILE_DOWNLOADABLE = "csvs/ecodes_data_history_07112012.csv";
	const FILE_PREMIUM = "csvs/ecodes_premium_fixes_07232012.csv";
	const LOGFILE = "logging2.txt";
	
	
//	private $dbUser = "root";/
//	private $dbPass = "xcyte79";
//	private $dbDb = "iccsafe";
//	private $dbHost = "127.0.0.1";
//	private $conn = null;
	private $fh = null;
	private $orderArray = null;
	
	private $data;
	
	
	function run()
	{
		$this->fh = fopen(self::LOGFILE, 'a') or die("can't open file");
		$starttime = time();
		$this->log("Starting Import Historical Orders");
		$this->log("Loading files into memory");
		$this->loadFilesIntoMemory();
		
		$total = count($this->data);
		$this->log("Processing $total lines");
		
		$this->processLinesToOrderArray();
		
		$totalOrders = count($this->orderArray);
		
		$cnt = 0;
		
		foreach($this->orderArray as $order)
		{
			//print_r($order);
			$cnt++;
			echo "Processing $cnt out of $totalOrders..............\r";
			
			$orderida = $order['old_order_id_a'];
			
			$magentoorder = $this->loadOrderByOldOrderId($orderida);
			$items = $magentoorder -> getAllItems();
			
			
			//echo $order['email']."\n";
			if($order['email'] == "")
			{
				continue;
				//echo $order['email']."\n";
				//print_r($order);
				//die;
			}
			//continue;
			$customer = $this->getCustomer($order);
			
			//print_r($customer->getData());
			//die;
			//$c = new ICC_Customer_Model_Customer();
			$helper = Mage::helper('ecodes');
			$customer->setData('ecodes_master_user', $order['subscription_master_user_name']) ;
			$customer->setData('ecodes_master_pass', Mage::helper('core')->encrypt($order['subscription_master_password'])) ;
			
			$customer->save();
			
			
			$user = Mage::getModel('ecodes/premiumusers')->load($order['subscription_master_user_name'],'user');
			
			//print_r($user->getData());
			
			//echo $order['subscription_master_user_name']."\n";

			
			

			
			
			
			$user->setData("firstname",$customer->getFirstname());
			$user->setData("lastname",$customer->getLastname());
			$user->setData("email",$order['email']);
			$user->setData("user",$order['subscription_master_user_name']);
			$user->setData("pass",Mage::helper('core')->encrypt($order['subscription_master_password']));
			$user->save();
			
			$userid = $user->getId();
			
			
			
			foreach($items as $magentoitem)
			{
				//print_r($magentoitem->getData());
				$itemid = $magentoitem->getItemId();
				
				
				$premiumsubs = Mage::getModel('ecodes/premiumsubs')->load($itemid,'order_item_id');
				$subid = $premiumsubs->getId();
				
				
				
				$subuser = Mage::getModel('ecodes/premiumsubusers');
				$subuser->setUserId($userid);
				$subuser->setSubsId($subid);
				$subuser->save();
			//	print_r($subuser->getData());
				
			}
			
			//die;
			/*
			
			*/
			
			
			//echo "customer isdata\n";
			//print_r($customer->getData());
			//die;
			//$c -> load($customer -> getEntityId());
			//$c -> createEcodesMasterAccount($order['subscription_master_user_name'], $order['subscription_master_password'], $order['subscription_master_password']);
		}
		
		echo "--------------DONE-------------------\n";
		
	}
	
	
	function setupDb()
	{


		$this->conn = mysql_connect($this->dbHost, $this->dbUser, $this->dbPass);

		if (!$this->conn) {
			echo "Could not connect to server\n";
			trigger_error(mysql_error(), E_USER_ERROR);
			die;
		} else {
			echo "Connection established\n";
		}


		if (!mysql_select_db($this->dbDb)) {
			echo "Unable to select mydbname: " . mysql_error();
			exit;
		}

	}












	function loadFilesIntoMemory() {
		$this -> data = array();

		/*
		 * load files into temp array
		*/
		//$this -> filename = self::FILE_DOWNLOADABLE;
		//$this -> loadFile();
		$this -> filename = self::FILE_PREMIUM;
		$this -> loadFile();
		//$this -> filename = self::FILE_DOE;
		//$this -> loadFile();


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
		//echo "got customer : ";

		// $customer -> checkAddress($custdata);

		return $customer;
	}

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

	function log($d) {
		$stringData = $this->toDate(time())." : ".$d."\n";
		echo $stringData;
		fwrite($this->fh, $stringData);
	}
	function toDate($time)
	{
		return date(DATE_RFC822,$time);
	}
	
	
	function loadOrderByOldOrderId($ida)
	{
		$orders = Mage::getModel('sales/order')
		->getCollection()
		->addAttributeToSelect('old_order_id_a')
		->addAttributeToSelect('old_order_id_b')
		->addAttributeToSelect('entity_id')
		->addFieldToFilter(
				'old_order_id_a',array('eq'=>$ida)
		);
	
		foreach($orders as $o)
		{
			$order = Mage::getModel('sales/order')->load($o->getEntityId());
		}
	
	
		return $order;
	
	
	}
}