<?php
require_once '../app/Mage.php';
umask(0);
/* not Mage::run(); */
Mage::app('default');

//$product = Mage::getModel('catalog/product');

$products = array();


$found = array();
$none = array();
$multiple = array();




if (($handle = fopen ( "ecodes_data_history_2.csv", "r" )) !== FALSE) {
	$d = array ();
	while ( ($data = fgetcsv ( $handle, 1000, "," )) !== FALSE ) {

		//$d [] = $data;
		//print_r($d);
		$products[$data[16]] = "";
		//getByName($data);

	}
	fclose ( $handle );

	
}

foreach($products as $name=>$nothing)
{
	$products[$name] = getProductBySku(trim($name));
}


//echo "<pre>";
//print_r($products);
//echo "</pre>";



echo"found : ".count($found)."<br>\nnone : ".count($none)."<br>\nmultiple : ".count($multiple)."<br>";

echo "----------------Found---------------<br>";
echo "<pre>";
print_r($found);
echo "</pre>";

echo "---------------Multiple--------------<br>";

echo "<pre>";
print_r($multiple);
echo "</pre>";

/*
 * 
 echo "--------------- None -----------------<br>";
echo "<pre>";
print_r($none);
echo "</pre>";
*/

function getProductBySku($sku)
{
	echo "checking $sku";
	global $found,$none,$multiple;

	$model = Mage::getModel('catalog/product');
	$collection = $model->getCollection();

	$collection->addFieldToFilter(array(
			//array('attribute'=>'name','like'=>'%'.$name.'%'),
			array('attribute'=>'sku','like'=>"%".trim($sku)),
	));

	$count = $collection->count();
	//return $count;
	echo " $count ";
	if($count == 1)
	{
		$found[] = array("name"=>$sku,"magento_sku"=>$collection->getFirstItem()->getSku());
		//$found[]['magento_sku']	=


		echo "\n";
		return $collection->getFirstItem()->getSku();
	}
	
	
	
	$model = Mage::getModel('catalog/product');
	$collection = $model->getCollection();
	
	$collection->addFieldToFilter(array(
			//array('attribute'=>'name','like'=>'%'.$name.'%'),
			array('attribute'=>'sku','like'=>"%".trim($sku)."%"),
	));
	
	$count = $collection->count();
	echo " $count ";
	//return $count;
	if($count == 1)
	{
		echo "\n";
		$found[] = array("name"=>$sku,"magento_sku"=>$collection->getFirstItem()->getSku());
		//$found[]['magento_sku']	=
	
	
	
		return $collection->getFirstItem()->getSku();
	}
	
	
	
	echo "\n";
	
	
	if($count>1)
	{
		$model = Mage::getModel('catalog/product');
		$collection = $model->getCollection();

		$collection->addFieldToFilter(array(
				array('attribute'=>'name','like'=>'%'.trim($sku)),
		));
		$count2 = $collection->count();
		//return $count;
		if($count2 == 1)
		{
			$found[] = $sku;
			//$found++;
			return $collection->getFirstItem()->getSku();
		}
		$multiple[] = $sku;
		//$multiple++;
		return $count;
		//echo $name ." - ".$count."<br>\n";
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

}


function getProductById($name)
{
	global $found,$none,$multiple;

	$model = Mage::getModel('catalog/product');
	$collection = $model->getCollection();

	$collection->addFieldToFilter(array(
			//array('attribute'=>'name','like'=>'%'.$name.'%'),
			array('attribute'=>'name','like'=>'%'.$name.'%'),
	));

	$count = $collection->count();
	//return $count;
	if($count == 1)
	{
		$found[] = array("name"=>$name,"magento_sku"=>$collection->getFirstItem()->getSku());
		//$found[]['magento_sku']	=



		return $collection->getFirstItem()->getSku();
	}
	if($count>1)
	{
		$model = Mage::getModel('catalog/product');
		$collection = $model->getCollection();

		$collection->addFieldToFilter(array(
				array('attribute'=>'name','like'=>'%'.$name),
		));
		$count2 = $collection->count();
		//return $count;
		if($count2 == 1)
		{
			$found[] = $name;
			//$found++;
			return $collection->getFirstItem()->getSku();
		}
		$multiple[] = $name;
		//$multiple++;
		return $count;
		//echo $name ." - ".$count."<br>\n";
	}
	$none[] = $name;
	//$none++;
	return "Cant Find Yo";
}

function getByName($d)
{
	//print_r($d);
	$name = $d[15];
	$model = Mage::getModel('catalog/product');
	$collection = $model->getCollection();
	
	$collection->addFieldToFilter(array(
			array('attribute'=>'name','like'=>'%'.$name.'%'),
	));
	
	$count = $collection->count();
	if($count == 1)
		echo $name ." - ".$count."<br>\n";
}





function process($array) {
	$customers = array ();
	foreach ( $array as $data ) {
		
		$email = $data [0];
		
		$first_name = $data [1];
		$last_name = $data [2];
		$coupon_number = $data [3];
		$old_order_id_a = $data [4];
		$old_order_id_b = $data [5];
		$order_datetime = $data [6];
		$status = $data [7];
		$member_nu = $data [8];
		$bill_street = $data [9];
		$bill_city = $data [10];
		$bill_state = $data [11];
		$bill_zip = $data [12];
		$bill_country = $data [13];
		$bill_phone = $data [14];
		$product_name = $data [15];
		$eCodes_ID = $data [16];
		$product_sku = $data [17];
		$line_item_total = $data [18];
		$product_qty = $data [19];
		$download_serial_number = $data [20];
		$download_remaining_downloads = $data [21];
		$subscription_start_date = $data [22];
		$subscription_end_date = $data [23];
		$subscription_num_users = $data [24];
		$subscription_master_user_name = $data [25];
		$subscription_master_password = $data [26];
		$download_subscription = $data [27];
		
		$customer = array ();
		
		$product ['product_name'] = $product_name;
		$product ['eCodes_ID'] = $eCodes_ID;
		$product ['product_sku'] = $product_sku;
		$product ['line_item_total'] = $line_item_total;
		$product ['product_qty'] = $product_qty;
		$product ['download_serial_number'] = $download_serial_number;
		$product ['download_remaining_downloads'] = $download_remaining_downloads;
		$product ['subscription_start_date'] = $subscription_start_date;
		$product ['subscription_end_date'] = $subscription_end_date;
		$product ['subscription_num_users'] = $subscription_num_users;
		$product ['subscription_master_user_name'] = $subscription_master_user_name;
		$product ['subscription_master_password'] = $subscription_master_password;
		$product ['download_subscription'] = $download_subscription;
		
		$customers [$email] ['first_name'] = $first_name;
		$customers [$email] ['last_name'] = $last_name;
		
		$customers [$email] [$old_order_id_a] ['member_nu'] = $member_nu;
		$customers [$email] [$old_order_id_a] ['status'] = $status;
		$customers [$email] [$old_order_id_a] ['old_order_id_b'] = $old_order_id_b;
		$customers [$email] [$old_order_id_a] ['order_datetime'] = $order_datetime;
		$customers [$email] [$old_order_id_a] ['status'] = $status;
		$customers [$email] [$old_order_id_a] ['member_nu'] = $member_nu;
		$customers [$email] [$old_order_id_a] ['bill_street'] = $bill_street;
		$customers [$email] [$old_order_id_a] ['bill_city'] = $bill_city;
		$customers [$email] [$old_order_id_a] ['bill_state'] = $bill_state;
		$customers [$email] [$old_order_id_a] ['bill_zip'] = $bill_zip;
		$customers [$email] [$old_order_id_a] ['bill_country'] = $bill_country;
		$customers [$email] [$old_order_id_a] ['bill_phone'] = $bill_phone;
		$customers [$email] [$old_order_id_a] ['product'] [] = $product;
	}
	return $customers;
}






		