<?php
ini_set("memory_limit", "2048M");
require_once '../../app/Mage.php';

umask(0);

Mage::app('default');


$isio = new insertSerialsIntoOrders();
$isio->run();


class insertSerialsIntoOrders
{
	const FILE_DOWNLOADABLE = "ecodes_data_history_07112012.csv";
	//const FILE_DOWNLOADABLE = "fixed.csv";
	const FILE_PREMIUM = "ecodes_data_history_07112012_premium.csv";
	const FILE_DOE = "ecodes_data_history_07112012_DOE.csv";

	public $data = array();
	public $filename = "";
	public $orderArray = array();
	private $defaultemail = "historicalOrders@iccsafe.org";

	private $starttime = "";
	private $endtime = "";
	private $laptime = "";


	private $blankserialCount = 10000;
	private $currentProductList;
	

	function run()
	{
		echo "Loading files into memory\n";
		$this->loadFilesIntoMemory();

		echo "Done loading files into memory\n";
		$orders = $this->processLinesToOrderArray($this->data);

		foreach($orders as $order)
		{
			$this->processOrder($order);
		}
	}



	function processOrder($order)
	{
		$orderid = $order['old_order_id_a'];
		echo $orderid."\n";
		$products = $order['products'];

		$keys = $this->getSerials($products);

		//print_r($keys);
		$magento_order = $this->loadOrderByOldOrderId($orderid);
		if($magento_order == false)
		{
			echo "CANNOT FIND ORDER\n";
			return false;
		}
		$items = $magento_order->getAllItems();
		foreach ($items as $itemId => $item)
		{
			echo ".";
			$ordersku=$item->getSku();
				

			$key = array_pop($keys[$ordersku]);
			$this->addSerial($key,$item);
			//print_r( $key);
			if(is_null($key))
			{
				echo "Error key is null\n";
				print_r($keys);
				echo $ordersku;
				//die;
					
			}

		}
		//print_r($order);
		//die;
	}

function addSerial($product,$item)
	{
		//print_r($product);
		
		if( $product['download_serial_number'] == "")
		{
			"Download serial number is false";
			return false;
		}
		$this->blankserialCount++;
		if($product['product_name'] == "")
			$product['product_name'] = "None";
		if($product['eCodes_ID'] =="" || $product['product_sku'] =="")
		{
			echo "ecodes id or product sku is false\n";
			return false;
		
		}
		$data = array(
				'id' => NULL,
				'serial' => $product['download_serial_number'],
				'order_item_id' => $item->getItemId(),
				'enabled' => '1',
				'updated_at' => CURRENT_TIMESTAMP,
				'created_at' => CURRENT_TIMESTAMP,
				'gp_sku' => $product['product_sku'],
				'document_id' => $product['eCodes_ID'],
				'product_title' => mysql_real_escape_string($product['product_name'])
		);

		//print_r($data);
		//die;
		try
		{
			$write = Mage::getSingleton('core/resource') -> getConnection('core_write');
			$write -> insert('ecodes_downloadable', $data);

			return true;

		}
		catch(Exception $e)
		{
			echo "\n-------------------222-----------------\n";
			echo $e->getMessage();
			print_r($data);
			die;
			return false;

		}
	}
	function getSerials($products)
	{
		$p = array();
		foreach($products as $product)
		{
			//print_r($product);
			//die;
			//if($product['download_serial_number'] == "")
			//$product['download_serial_number'] = "NOSERIAL_".$this->blankserialCount;
			
			$p[$product['eCodes_ID']][] =
			array(
			'product_sku'=>$product['product_sku'],
			'eCodes_ID'=>$product['eCodes_ID'],
			'product_name'=>$product['product_name'],
			'download_serial_number'=>$product['download_serial_number']
			
			);
		}
		return $p;
	}



	function loadFilesIntoMemory() {
		$this -> data = array();

		/*
		 * load files into temp array
		 */
		$this -> filename = self::FILE_DOWNLOADABLE;
		$this -> loadFile();
		$this -> filename = self::FILE_DOE;
		$this -> loadFile();


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

	function processLinesToOrderArray($data) {
		$notfound = array();
		$orderArray = array();
		$product = array();
		//Mage::getModel('catalog/product');
		foreach ($data as $line) {


			$old_order_id_a = $line[4];
			$old_order_id_b = $line[5];
			$order_datetime = $line[6];
			$status = $line[7];
			$member_nu = $line[8];

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





			$orderArray[$old_order_id_a]['old_order_id_a'] = $old_order_id_a;
			$orderArray[$old_order_id_a]['old_order_id_b'] = $old_order_id_b;
			$orderArray[$old_order_id_a]['products'][] = $product;

		}
		unset($this->data);
		unset($data);
		return $orderArray;


		//print_r ($notfound);
	}

	function loadOrderByOldOrderId($ida)
	{
		$order = Mage::getModel('sales/order')
		->getCollection()
		->addAttributeToSelect('old_order_id_a')
		->addAttributeToSelect('old_order_id_b')
		->addAttributeToSelect('entity_id')
		->addFieldToFilter(
				'old_order_id_a',array('eq'=>$ida)
		)->getFirstItem();

		$entityid = $order->getEntityId();
		if ($entityid == 0 || $entityid == "")
		{
			return false;
		}
		$order = Mage::getModel('sales/order')->load($order->getEntityId());

		return $order;


		//print_r($order->getData());
	}
}


?>
