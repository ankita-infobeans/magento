<?php
ini_set("memory_limit", "1024M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');
$rpeo = new removePremiumEcodesOrders();
$rpeo->DELETEALLTHISSTUFF();

class removePremiumEcodesOrders
{

	const FILE_PREMIUM = "ecodes_data_history_07112012_premium.csv";


	const ERROR_SPREADSHEET = "errors.csv";
	const LOGFILE = "logging.txt";
	public $data = array();
	public $filename = "";
	public $orderArray = array();
	private $defaultemail = "historicalOrders@iccsafe.org";

	private $starttime = "";
	private $endtime = "";
	private $laptime = "";
	public $conn;
	private $fh = null;

	function DELETEALLTHISSTUFF()
	{
		$this->loadFilesIntoMemory();
		$this->processLinesToOrderArray();



		$this->setupDb();



		foreach($this->orderArray as $orderid=>$order)
		{
			//echo $orderid."\n";
			$this->delete($orderid);
				
		}
			
	}
	function delete($o)
	{
		if($o == "")
		{
			echo "WTF MARON IT IS EMPTY!!!!\n\n\n\n";
			die;
		}
		$query = "DELETE FROM `sales_flat_order` WHERE `old_order_id_a` = '".$o."'";
		echo $query."\n";
		//return;
		$result = mysql_query($query,$this->conn);
		if (!$result) {
			echo "Could not successfully run query ($query) from DB: " . mysql_error();
			exit;
		}
	}
	function loadFilesIntoMemory() {
		$this -> data = array();

		/*
		 * load files into temp array
		*/
		$this -> filename = self::FILE_PREMIUM;
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

	function setupDb()
	{
		$host = "localhost";
		$user = "root";
		$pass = "xcyte79";
		$db = "icctest2";
		$this->conn = mysql_connect($host, $user, $pass);


		if (!$this->conn) {
			echo "Could not connect to server\n";
			trigger_error(mysql_error(), E_USER_ERROR);
		} else {
			echo "Connection established\n";
		}


		if (!mysql_select_db($db)) {
			echo "Unable to select mydbname: " . mysql_error();
			exit;
		}
	}

}