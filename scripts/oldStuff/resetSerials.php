<?php
ini_set("memory_limit", "512M");
require_once '../../app/Mage.php';

umask(0);

Mage::app('default');
//$data = loadFile();


$reset = new resetSerials();
$reset->run();

/*
 foreach($orders as $orderId => $order)
 {
$m_order = loadOrderByOldOrderId($orderId);
//print_r($order);
//continue;
if($m_order)
{
foreach($m_order as $o)
{
$items = $o->getAllItems();



echo "-----------------------------ORDER  $oldorderid------------------------------\n";


foreach($items as $item)
{

$_product = Mage::getModel('catalog/product');
//echo $item->getSku()."\n";
$_product->load($_product->getIdBySku($item->getSku()));
//print_r($_product->debug());
if($_product->getTypeId() != 'downloadable')
{
echo "continuing ".$_product->getTypeId()."\n";
continue;
}
//print_r($order);
$usedserial = array_pop($order[$item->getSku()]);
echo $usedserial."\n";
resetSerial($usedserial);
}
}
}



}

*/


class resetSerials
{

	public $conn;


	public $serials;
	public $orders;

	function run()
	{
		$this->setupDb();

		$this->processFile();
		$this->resetSerials();
		$this->createSerials();


	}

	function createSerials()
	{


		echo "-----------CREATING ORDERS --------------\n";

		$count = 0;
		$total = count($this->orders);

		foreach($this->orders as $orderId => $order)
		{
			echo "-------------------- $count of $total------------------\n";
			$m_order = $this->loadOrderByOldOrderId($orderId);
			//print_r($order);
			//continue;
			if($m_order)
			{

				foreach($m_order as $o)
				{
					$items = $o->getAllItems();



					echo "-----------------------------ORDER  $oldorderid------------------------------\n";


					foreach($items as $item)
					{

						$_product = Mage::getModel('catalog/product');
						//echo $item->getSku()."\n";
						$_product->load($_product->getIdBySku($item->getSku()));

						//print_r($_product->debug());
						if($_product->getTypeId() != 'downloadable')
						{
							echo "continuing ".$_product->getTypeId()."\n";
							continue;
						}
						//print_r($order);
						$single_order_item = array_pop($order[$item->getSku()]);
						$usedserial = $single_order_item[20];
						echo $usedserial."\n";
						$model = Mage::getModel('ecodes/downloadable');
						$model->setSerial($usedserial);
						$model->setOrderItemId($item->getItemId());
						$model->setEnabled(1);
						$model->setProductTitle($item->getName());
						$model->setDocumentId($item->getSku());
						$model->setGpSku($single_order_item[17]);
						$model->save();



							






					}
				}
			}
			else{
				echo "\n\n\n\n\n-----------------------------------------------------------------------------\n";
				echo "CANNOT find order $orderId\n";
				echo "-----------------------------------------------------------------------------\n\n\n\n\n";
			}

			$count++;

		}

	}
	function resetSerials()
	{

		/*
		 $count = 0;
		$total = count($this->serials);
		$cc = 0;
		$q = "DELETE FROM ecodes_downloadable WHERE serial='".$serial."' OR ";

		foreach($this->serials as $serial)
		{
		if($cc >500)
		{
		$q .= "serial='111111111111111111111111111'";
		echo $q."\n";
		$result = mysql_query($q,$this->conn);
		if (!$result) {
		echo "Could not successfully run query ($q) from DB: " . mysql_error();
		exit;
		}
		$q = "DELETE FROM ecodes_downloadable WHERE serial='".$serial."' OR ";
		$cc = 0;
		}else{
		$q .= "serial='".$serial."' OR ";
		$cc++;
		}
			
		echo " $count of $total\n";
		//continue;
			
		$count++;
			
		}

		*/
		$tcount = 0;
		$count = 1;
		$total = count($this->orders);


		$q = "UPDATE
		ecodes_downloadable
		SET
		order_item_id='',enabled='1' WHERE ";

		foreach($this->orders as $orderId=>$order)
		{
			echo $orderId;
			$m_order = $this->loadOrderByOldOrderId($orderId);
				
				
			/*
			 * Delete order comments
			*/
				
			//print_r($m_order->getData());
			//die;
			if($m_order)
			{
				foreach($m_order as $mo)
					$this->deleteCommentsOfOrder($mo->getEntityId());
			}
			continue;
			/*
			 * Done deleting order comments
			*/
				
				
			//print_r($order);
			//continue;
			if($tcount< 25) // 25 orders at a time
			{
				$tcount++;
			}
			else{
				$q .= "order_item_id = '111111111111111'"; /// dummy account to cap off OR in mysql query
				echo $q."\n";
				//continue;
				$result = mysql_query($q,$this->conn);
				if (!$result) {
					echo "Could not successfully run query ($sql) from DB: " . mysql_error();
					exit;
				}

				$q = "UPDATE
				ecodes_downloadable
				SET
				order_item_id='',enabled='1' WHERE ";
				$tcount = 0;
			}
			if($m_order)
			{
				foreach($m_order as $o)
				{
					$items = $o->getAllItems();



					echo "-----------------------------ORDER  $orderId---------------------$count of $total---------\n";
						

					foreach($items as $item)
					{


						$q .="order_item_id='".$item->getItemId()."' OR ";



					}
						
						
					echo "----------------FIN-----------\n";
				}
			}
			echo "ASDFASDFASDFASDFAsdf\n";
			$count++;


		}


	}


	/*
	 * Array
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

	function processFile()
	{
		global $allSerials;
		$data = $this->loadFile();
		//print_r($data);
		$this->orders = array();
		foreach($data as $line)
		{
			//print_r($line);
			/*$item = array('product_name'=> $line[15],
					'eCodes_ID'=>$line[16],
					'download_serial_number'=>$line[20]

			);
			*/
			$this->serials[$line[20]] = $line[20];
			$this->orders[$line[4]][$line[16]][] = $line;


		}


		return $orders;
	}


	function loadFile() {
		//echo "Loading file ";
		$filename = "serials.csv";
		$filename = "downloadable_ecodes.csv";

		if (($handle = fopen($filename, "r")) !== FALSE) {


			$d = array();

			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

				$d [] = $data;
			}
			fclose($handle);

		}
		array_shift($d );
		return $d ;

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
			$order[] = Mage::getModel('sales/order')->load($o->getEntityId());
		}


		return $order;


	}
	/*

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
	*/

	function deleteCommentsOfOrder($orderid)
	{
		$q = "DELETE FROM sales_flat_order_status_history WHERE `parent_id` = '".$orderid."'";
		echo $q."\n";
		$result = mysql_query($q,$this->conn);
		if (!$result) {
			echo "Could not successfully run query ($q) from DB: " . mysql_error();
			exit;
		}

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

