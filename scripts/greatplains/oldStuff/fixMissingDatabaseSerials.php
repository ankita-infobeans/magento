<?php
ini_set("memory_limit", "512M");
require_once '../../app/Mage.php';

umask(0);

Mage::app('default');
$data = loadFile();





$host = "localhost";
$user = "root";
$pass = "xcyte79";
$db = "icctest2";
$conn = mysql_connect($host, $user, $pass);

if (!$conn) {
	echo "Could not connect to server\n";
	trigger_error(mysql_error(), E_USER_ERROR);
} else {
	echo "Connection established\n";
}


if (!mysql_select_db($db)) {
	echo "Unable to select mydbname: " . mysql_error();
	exit;
}





$orders = array();

foreach($data as $line)
{

	if($line[2] ==1)// && $line[3] == 0)
	{
		$orders[$line[4]][$line[0]][] = $line[1];
	}
}


$totalorders = count($orders);
$currentcount = 0;

foreach($orders as $oldorderid=>$order)
{

	$os = loadOrderByOldOrderId($oldorderid);

	foreach($os as $o)
	{
		$items = $o->getAllItems();

		
		
		echo "-----------------------------ORDER  $oldorderid------------------------------\n";

		print_r($order);
		foreach($items as $item)
		{
			$_product = Mage::getModel('catalog/product');
			$_product->load($_product->getIdBySku($item->getSku()));
			//print_r($_product->debug());
			if($_product->getProductTypeId() != 'downloadable')
			{
				continue;
			}
			//die;
			$gpSku =  $_product->getGpSku();

			print_r($order[$gpSku]);


			$itemid = $item->getItemId();


			$serial = array_pop($order[$gpSku]);
			print_r($order[$gpSku]);
			
			echo $oldorderid." ".$item->getItemId()." ".$gpSku." ".$item->getSku()." ".$serial."\n";

			/*
			 * Reset any serials that dont match
			*/


			$q = "SELECT * FROM ecodes_downloadable WHERE order_item_id='".$itemid."'";

			echo $q."\n";;

			$result = mysql_query($q,$conn);

			if (!$result) {
				echo "Could not successfully run query ($sql) from DB: " . mysql_error();
				exit;
			}

			$found = false;
			while ($row = mysql_fetch_assoc($result))
			{
				if($row['serial'] == $serial)
				{
					$found = true;
				}else{
					$q2 = "UPDATE ecodes_downloadble SET order_item_id = '',enabled = 0 WHERE id= ".$row['id'];
					mysql_query($q2,$conn);
					echo $q2."\n";
				}
					
				//print_r($row);
			}

			if(!$found)
			{
				$model = Mage::getModel('ecodes/downloadable');
				$model->setProductTitle($item->getName());
				$model->setSerial($serial);
				$model->setOrderItemId($item->getItemId());
				$model->setEnabled(1);
				$model->setDocumentId($item->getSku());
				$model->setGpSku($gpSku);
				if($model->getSerial() != "")
				{
					echo "Saving Model\n";
					$model->save();
				}else{
					echo "Cannot save model ".print_r($model->debug());
					die;
				}
			}
			//die;
		}
		echo "------------------------- DONE ORDER ---------------------------------------\n";

	}
	$currentcount++;
	echo "----------order $currentcount out of $totalorders\n\n";
	//die;
}












function loadFile() {
	//echo "Loading file ";
	$filename = "serials.csv";
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