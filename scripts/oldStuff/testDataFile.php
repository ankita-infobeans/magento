<?php
ini_set("memory_limit", "512M");
require_once '../../app/Mage.php';

umask(0);



Mage::app('default');
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
//$searchsku = "8950P189";

$serials = array();


$matching = array();

$newdata = array();



if (($handle = fopen('downloadable_ecodes.csv', "r")) !== FALSE) {
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		$skunum = $data[16];
		
		$orderid = $data[4];
		/*
		if($skunum == null || $skunum == "NULL")
		{
			//print_r($data);
			$skunum =$data[16];
			$_product = Mage::getModel('catalog/product');
			$_product->load($_product->getIdBySku($data[16]));
			$skunum =  $_product->getGpSku();
			
			
		}
		*/
		if(!isset($newdata[$skunum]))
		{
			$newdata[$skunum] = array();

		}

		
		if(!isset($newdata[$skunum][$data[20]]))
		{
			$newdata[$skunum][$data[20]] = array('csv'=>0,'database'=>0);;
		}

		$newdata[$skunum][$data[20]]['csv'] = 1;
		$newdata[$skunum][$data[20]]['oldorderid'] = $orderid;
		/*
		 if($data[17] == $searchsku)
		 {
		$matching[] = $data;
		}
		*/
	}
	fclose($handle);


}


//print_r($newdata);

//die;



foreach($newdata as $sku => $v)
{
	//$q = "SELECT * FROM ecodes_downloadable WHERE gp_sku = '$sku' and order_item_id!=''";
	$q = "SELECT * FROM ecodes_downloadable WHERE document_id = '$sku' and order_item_id!=''";

	echo $q."\n";;

	$result = mysql_query($q,$conn);

	if (!$result) {
		echo "Could not successfully run query ($sql) from DB: " . mysql_error();
		exit;
	}

	if(!isset($newdata[$sku]))
	{
		$newdata[$sku] = array();
	
	}
	
	while ($row = mysql_fetch_assoc($result)) {

		//print_r($row);
		
		
		
		if(!isset($newdata[$sku][$row['serial']]))
		{
			$newdata[$sku][$row['serial']] = array('csv'=>0,'database'=>0);;
		}
		
		$newdata[$sku][$row['serial']]['database'] = 1;
		$newdata[$sku][$row['serial']]['order_item_id'] = $row['order_item_id'];
		$newdata[$sku][$row['serial']]['created_at'] = $row['created_at'];
		
	}
}
//die;
//echo "Count $count\n";

$fp = fopen('serials2.csv', 'w');

foreach($newdata as $k=>$v)
{
	foreach($v as $s=> $serial)
	{
		$a = array($k,$s,$serial['csv'],$serial['database'],$serial['oldorderid'],$serial['order_item_id'],$serial['created_at']);
		fputcsv($fp, $a);
	}
}
	
fclose($fp);
//print_r($newdata);

