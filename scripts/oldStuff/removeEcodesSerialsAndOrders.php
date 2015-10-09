<?php
ini_set("memory_limit", "1024M");
require_once '../../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

Mage::app('default');

$resao = new removeEcodesSerialsAndOrders();


$resao->removeOrders();

$resao->removeSerials();
$resao->resetExistingSerials();


class removeEcodesSerialsAndOrders
{
	/*
	private $dbUser = "root";
	private $dbPass = "xcyte79";
	private $dbDb = "icctest2";
	private $dbHost = "localhost";
	private $conn = null;
	*/
	private $dbUser = "icc";
	private $dbPass = "phuWepOov5";
	private $dbDb = "iccshop";
	private $dbHost = "10.30.2.172";
	private $conn = null;
	

	private $orders;
	private $serials;
	private $skus;


	private $filename;

	//const FILE_DOWNLOADABLE = "downloadable_ecodes.csv";
	const FILE_DOWNLOADABLE = "combined.csv";
	const FILE_PREMIUM = "premium_ecodes.csv";
	const FILE_DOE = "doe_ecodes.csv";

/*
 *   <host><![CDATA[10.30.2.172]]></host>
                    <username><![CDATA[icc]]></username>
                    <password><![CDATA[phuWepOov5]]></password>
                    <dbname><![CDATA[iccshop]]></dbname>
                    <initStatements><![CDATA[SET NAMES utf8]]></initStatements>
                    <model><![CDATA[mysql4]]></model>
                    <type><![CDATA[pdo_mysql]]></type>
                    <pdoType><![CDATA[]]></pdoType>
                    <active>1</active>

 */

	function removeSerials()
	{
		$this->filename = self::FILE_DOWNLOADABLE;


		if(!$this->conn)
		{
			$this->setupDb();
		}
		//$this->loadFile(false,true,false);

		$data = $this->loadFile();
		$query = "DELETE FROM `ecodes_downloadable` WHERE ";
		$count = 0;
		
		foreach($data as $line)
		{
			//print_r($serial);

			if($count >1000)
			{
				$query .= "`serial` = '11111111111111111111111111111'";
				$count = 0;
				echo $query."\n\n\n\n\n\n\n";
				$result = mysql_query($query,$this->conn);
				$query = "DELETE FROM `ecodes_downloadable` WHERE ";
				
			}else{
				$query .= "`serial` = '".$line[20]."' OR ";
				$count++;
			}
			//$query = "DELETE FROM `ecodes_downloadable` WHERE `serial` = '".$line[20]."'";
		//	if($line[20] == 'MDOVE7DEJR8A7SKM')
				
		}
	}
	
	
	function resetExistingSerials()
	{
		if(!$this->conn)
		{
			$this->setupDb();
		}
		//$query = "UPDATE 'ecodes_downlodable SET enabled='1'";
		$query = "SELECT * FROM `ecodes_downloadable` WHERE `order_item_id` IS NOT NULL  AND `order_item_id` != '0'";

		echo $query."\n";
		$result = mysql_query($query,$this->conn);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		
		while ($row = mysql_fetch_assoc($result))
		{
			print_r($row);
			$q = "SELECT count(*) as cnt FROM `sales_flat_order_item` WHERE item_id ='".$row['order_item_id']."'";
			
			$res2 = mysql_query($q,$this->conn);
			if (!$res2) {
				die('Invalid query: ' . mysql_error());
			}
				
			$r = mysql_fetch_assoc($res2);
			print_r($r);
			//die;
			echo "$q ".$r['cnt']."\n";
			if($r['cnt']==0)
			{
				$q3 = "UPDATE `ecodes_downloadable` SET `order_item_id` = '' WHERE `id` = '".$row['id']."'";
				echo $q3."\n\n\n";
				$res2 = mysql_query($q3,$this->conn);
				if (!$res2) {
					die('Invalid query: ' . mysql_error());
				}
			}
		}


		$query = "UPDATE 'ecodes_downlodable' SET enabled='1'";
		echo $query."\n";
		mysql_query($query,$this->conn);



	}
	function removeOrders()
	{
		if(!$this->conn)
		{
			$this->setupDb();
		}

		$query = "DELETE FROM `sales_flat_order` WHERE `old_order_id_a` != ''";
		$result = mysql_query($query,$this->conn);

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
	function loadFile() {
		echo "Loading file ".$this -> filename."\n";
		if (($handle = fopen($this -> filename, "r")) !== FALSE) {
			$tempdata = array();
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$tempdata[] = $data;
			}
			fclose($handle);

		}
		array_shift($tempdata);
		
		//$this->processLinesToOrderArray($loadOrders,$loadSerials,$loadSkus,$tempdata);
		return $tempdata;

	}


	function processLinesToOrderArray($loadOrders = false,$loadSerials = false,$loadSkus = false) {
		$data = $this->loadFile();
		$notfound = null;
		$orderArray = null;
		$product = null;
		//Mage::getModel('catalog/product');
		$this->serials = array();
		$this->skus = array();
		$this->orders = array();
		foreach ($data as $line) {

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
			/*
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

			*
			*
			*/
			$orderArray[$old_order_id_a]['first_name'] = $first_name;
			$orderArray[$old_order_id_a]['last_name'] = $last_name;
			$orderArray[$old_order_id_a]['email'] = $email;
			$orderArray[$old_order_id_a]['member_nu'] = $member_nu;
			$orderArray[$old_order_id_a]['bill_street'] = $bill_street;
			$orderArray[$old_order_id_a]['bill_city'] = $bill_city;
			$orderArray[$old_order_id_a]['bill_state'] = $bill_state;
			$orderArray[$old_order_id_a]['bill_zip'] = $bill_zip;
			$orderArray[$old_order_id_a]['bill_country'] = $bill_country;
			$orderArray[$old_order_id_a]['bill_phone'] = $bill_phone;

			$orderArray[$old_order_id_a]['first_name'] = $first_name;
			$orderArray[$old_order_id_a]['last_name'] = $last_name;

			$orderArray[$old_order_id_a]['member_nu'] = $member_nu;
			$orderArray[$old_order_id_a]['status'] = $status;
			$orderArray[$old_order_id_a]['old_order_id_a'] = $old_order_id_a;
			$orderArray[$old_order_id_a]['old_order_id_b'] = $old_order_id_b;
			$orderArray[$old_order_id_a]['order_datetime'] = $order_datetime;
			$orderArray[$old_order_id_a]['status'] = $status;
			$orderArray[$old_order_id_a]['member_nu'] = $member_nu;
			$orderArray[$old_order_id_a]['bill_street'] = $bill_street;
			$orderArray[$old_order_id_a]['bill_city'] = $bill_city;
			$orderArray[$old_order_id_a]['bill_state'] = $bill_state;
			$orderArray[$old_order_id_a]['bill_zip'] = $bill_zip;
			$orderArray[$old_order_id_a]['bill_country'] = $bill_country;
			$orderArray[$old_order_id_a]['bill_phone'] = $bill_phone;
			$orderArray[$old_order_id_a]['products'][] = $product;

			$orderArray[$old_order_id_a]['subscription_master_user_name'] = $subscription_master_user_name;
			$orderArray[$old_order_id_a]['subscription_master_password'] = $subscription_master_password;
			if($loadSerials)
			{
				//print_r($line);
				$this->serials[] = array(
						'serial'=>$product['download_serial_number'],
						'product_name'=>$product['product_name'],
						'magento_sku'=>$product['eCodes_ID'],
						'gp_sku'=>$product['product_sku'],
						'orderId'=>$old_order_id_a
				);


			}
			if($loadSkus)
			{

				$this->skus[]= $product;
			}

		}

		unset($this -> data);

		if($loadOrders)
			$this ->orders = $orderArray;

		//print_r ($notfound);
	}
}