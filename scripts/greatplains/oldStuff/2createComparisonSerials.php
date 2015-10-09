<?php
ini_set("memory_limit", "512M");
require_once '../../app/Mage.php';

umask(0);

Mage::app('default');

$compare = new compare();
$compare->run();


class compare
{


	function run()
	{

		echo "Start\n";

		$lines = $this->loadFile();
		$orders = $this->processLinesToOrderArray($lines);
		foreach($orders as $order)
		{
			//if($order['old_order_id_a']=='1027839-96716744')
			//{

					
				$m_order = $this->loadOrderByOldOrderId($order['old_order_id_a']);

				$productcount = 0;

				$items = $m_order -> getAllItems();

				foreach($items as $item)
				{
					//echo $item->getSku();
					//echo "\n";
					//continue;

					//echo "\n\n\n--------------------NEW ITEM------------------\n";
					$csv_item = $order['products'][$productcount];

					$serialItemObject = $this->getSerialObjectByItemId($item->getItemId());



					//print_r($csv_item);
					//echo "------1--------\n";
					//print_r($serialItemObject);
					//echo "-------------2----------------------\n\n\n";
					//continue;
					echo "comparing : ".$csv_item['download_serial_number']." and ". $serialItemObject['serial'];
					if($csv_item['download_serial_number'] != $serialItemObject['serial'])
					{
						//echo "\n-----------------FAIL\n\n";
						$this->updateSerial($csv_item,$serialItemObject,$item);
						//echo "-----------3-------------\n\n\n\n";
					}


					//echo "\n\n----------------END----------------\n\n";
					$productcount++;


				}
			//}

		}
	}

	function updateSerial($csv_serial,$magento_serial,$m_item)
	{

		$connection = Mage::getSingleton('core/resource') -> getConnection('core_read');
		$select = $connection -> select() -> from('ecodes_downloadable', array('*')) -> where('serial=?', $csv_serial['download_serial_number']);

		$rowsArray = $connection -> fetchAll($select);

		if(count($rowsArray)>0)
		{
			print_r($rowsArray);
			$resource = Mage::getSingleton('core/resource');
			$writeConnection = $resource->getConnection('core_write');
			$query = "
			UPDATE
			`ecodes_downloadable`
			SET
			`order_item_id` = ".$m_item->getItemId()."
			,`gp_sku` = '".$csv_serial['product_sku']."'
			,`document_id`='".$csv_serial['eCodes_ID']."'
			,`product_title` ='".mysql_real_escape_string($csv_serial['product_name'])."'
			WHERE
			`serial` = '".$csv_serial['download_serial_number']."'";
			//echo "-------------4-----\n";
			echo $query."\n";
			$writeConnection->query($query);
			if(isset($magento_serial))
			{
				$resource = Mage::getSingleton('core/resource');
				$writeConnection = $resource->getConnection('core_write');
					
					
					
					
				$query = "UPDATE ecodes_downloadable SET order_item_id = '' WHERE id = ".$magento_serial['id'];
					
				//echo "----------5-----\n";
				//print_r($magento_serial);
					
				//echo "------6------\n";
				echo $query."\n";
					
				$writeConnection->query($query);
			}

		}else{
			$data = array(
					'id' => NULL,
					'serial' => $csv_serial['download_serial_number'],
					'order_item_id' => $m_item->getItemId(),
					'enabled' => '1',
					'updated_at' => CURRENT_TIMESTAMP,
					'created_at' => CURRENT_TIMESTAMP,
					'gp_sku' => $csv_serial['product_sku'],
					'document_id' => $csv_serial['eCodes_ID'],
					'product_title' => mysql_real_escape_string($csv_serial['product_name'])
			);
			print_r($data);

			$write = Mage::getSingleton('core/resource') -> getConnection('core_write');
			$write -> insert('ecodes_downloadable', $data);




			if(isset($magento_serial))
			{
				$resource = Mage::getSingleton('core/resource');
				$writeConnection = $resource->getConnection('core_write');
					
					
					
					
				$query = "UPDATE ecodes_downloadable SET order_item_id = '' WHERE id = ".$magento_serial['id'];
					
				//echo "----------5-----\n";
				//print_r($magento_serial);
					
				//echo "------6------\n";
				echo $query."\n";
					
				$writeConnection->query($query);
			}


		}

	}



	function getSerialObjectByItemId($itemid)
	{
		echo $itemid."\n";
		$connection = Mage::getSingleton('core/resource') -> getConnection('core_read');
		$select = $connection -> select() -> from('ecodes_downloadable', array('*')) -> where('order_item_id=?', $itemid);

		$rowsArray = $connection -> fetchAll($select);
		//print_r($rowsArray);
		return $rowsArray[0];
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

		$order = Mage::getModel('sales/order')->load($order->getEntityId());

		return $order;


		//print_r($order->getData());
	}

	function loadFile() {
		//echo "Loading file ";
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

	function processLinesToOrderArray($data) {
		$notfound = array();
		$orderArray = array();
		$product = array();
		//Mage::getModel('catalog/product');
		foreach ($data as $line) {



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





			$orderArray[$old_order_id_a]['old_order_id_a'] = $old_order_id_a;
			$orderArray[$old_order_id_a]['old_order_id_b'] = $old_order_id_b;
			$orderArray[$old_order_id_a]['products'][] = $product;

		}
		return $orderArray;


		//print_r ($notfound);
	}
}