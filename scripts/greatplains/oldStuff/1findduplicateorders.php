<?php
ini_set("memory_limit", "512M");
require_once '../../app/Mage.php';

umask(0);

Mage::app('default');


$user="root";
$password="xcyte79";
$database="icctest";
mysql_connect(localhost,$user,$password);
@mysql_select_db($database) or die( "Unable to select database");













$orders = array();

$q = "SELECT * FROM `sales_flat_order`";

echo $q;

$result = mysql_query($q);


while ($row = mysql_fetch_assoc($result)) {

	//print_r($row);
	if($row['old_order_id_a'] == "")
		continue;
	$orders[$row['old_order_id_a']][] = $row['entity_id'];
}

$fp = fopen('duplicates.csv', 'w');

foreach($orders as $a=>$orderida)
{
	if(count($orderida)>1)
	{
		echo count($orderida)." ".$a."\n";
		//process($orderida);


		foreach($orderida as $o)
		{
			$line = array();
			$hasserials = false;
			$order = Mage::getModel('sales/order')->load($o);

			$line[] = $a;
			$line[] = $o;
			$items = $order->getAllItems();

			//print_r($items);


			foreach ($items as $itemId => $item)
			{
				//print_r($item->getData());
				//die;
					
				if($item->getProductType() == 'downloadable')
				{
					$connection = Mage::getSingleton('core/resource') -> getConnection('core_read');

					$select = $connection -> select() -> from('ecodes_downloadable', array('*')) -> where('order_item_id=?', $item->getItemId());

					$rowsArray = $connection -> fetchAll($select);

					$tline = $item->getItemId();
					if(count($rowsArray)>0)
					{
						$oldserial = $rowsArray[0]['serial'];
						$tline .= " / ".$oldserial;
					}
					$line[] = $tline;
				}else{
					$line[] = "NOT DOWNLOADABLE";
						
				}

			}
			fputcsv($fp, $line);
		}
	}
}

fclose($fp);



$file = file('duplicates.csv');




foreach($file as $line)
{
	if(strpos($line,"/"))
	{

		continue;
	}
	if(strpos($line,"NOT DOWNLOADABLE"))
	{
		continue;
	}

	//echo $line;
	//echo "\n";
	$split = explode(',',$line);

	$id = $split[1];

	$q = "DELETE FROM sales_flat_order WHERE entity_id='$id'";
	echo $q;
	$result = mysql_query($q);
	echo "\n";

}

