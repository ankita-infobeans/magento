<?php
ini_set("memory_limit", "2048M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');

$apui = new fixAddresses();
$apui->run();

class fixAddresses
{
	const FILE_DOWNLOADABLE = "csvs/ecodes_data_history_07112012.csv";
	const FILE_PREMIUM = "csvs/ecodes_premium_fixes_07232012.csv";
	const FILE_DOE = "csvs/ecodes_data_history_07112012_DOE.csv";
	const LOGFILE = "logging2.txt";


	private $conn = null;
	private $fh = null;
	private $orderArray = null;

	private $data;
	private $customers;

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

		$total = count($this->customers);
		$n = 0;

		foreach($this->customers as $c)
		{
			$n++;
			try
			{
				echo $n." of ".$total. " ". $c['email']."...........................................................................\r";
				//print_r($order);


				if($c['email'] == "")
				{
					continue;
					//echo $order['email']."\n";
					//print_r($order);
					//die;
				}
			if(!$this->check_email_address($c['email']))
				continue;
				//continue;
				$customer = $this->getCustomer($c);
				if($customer == null)
					continue;
				$add = $customer->getPrimaryBillingAddress();
				if($add)
				{
					$add->setData('region',$c['bill_state']);
					$add->save();
				}else{
					$_custom_address = array (
							'firstname' =>  $c['first_name'],
							'lastname' =>  $c['last_name'],
							'street' => array (
									'0' =>  $c['bill_street'],
							),

							'city' =>  $c['bill_city'],
							'region_id' => $c['bill_state'],
							'region' => '',
							'postcode' => $c['bill_zip'],
							'country_id' => $c['bill_country'],
							'telephone' =>  $c['bill_phone'],
					);

					$customAddress = Mage::getModel('customer/address')
					//echo "-------";print_r($customAddress);

					->setData($_custom_address)
					->setCustomerId($customer->getId())
					->setIsDefaultBilling('1')
					->setIsDefaultShipping('1')
					->setSaveInAddressBook('1');


					$customAddress->save();
				}
			}catch(Exception $e)
			{
			}


			//$customer = $this->getCustomer($c);
			//print_r($customer->getPrimaryBillingAddress()->getData());

			//	die;

		}

		echo "\n--------------DONE-------------------\n";

	}



	function check_email_address($email) {
		// First, we check that there's one @ symbol,
		// and that the lengths are right.
		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
			// Email invalid because wrong number of characters
			// in one section or wrong number of @ symbols.
			return false;
		}
		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i++) {
			if
			(!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&
					↪'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
					$local_array[$i])) {
				return false;
			}
		}
		// Check if domain is IP. If not,
		// it should be valid domain name
		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) {
				return false; // Not enough parts to domain
			}
			for ($i = 0; $i < sizeof($domain_array); $i++) {
				if
				(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
						↪([A-Za-z0-9]+))$",
						$domain_array[$i])) {
					return false;
				}
			}
		}
		return true;
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
		//$this -> filename = self::FILE_PREMIUM;
		//$this -> loadFile();
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
			$member_nu = $line[8];
			$bill_street = $line[9];
			$bill_city = $line[10];
			$bill_state = $line[11];
			$bill_zip = $line[12];
			$bill_country = $line[13];
			$bill_phone = $line[14];




			$this->customers[$email]['first_name'] = $first_name;
			$this->customers[$email]['last_name'] = $last_name;
			$this->customers[$email]['email'] = $email;
			$this->customers[$email]['member_nu'] = $member_nu;
			$this->customers[$email]['bill_street'] = $bill_street;
			$this->customers[$email]['bill_city'] = $bill_city;
			$this->customers[$email]['bill_state'] = $bill_state;
			$this->customers[$email]['bill_zip'] = $bill_zip;
			$this->customers[$email]['bill_country'] = $bill_country;
			$this->customers[$email]['bill_phone'] = $bill_phone;


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
}