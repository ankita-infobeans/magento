<?php
ini_set("memory_limit", "512M");
require_once '../../app/Mage.php';

umask(0);

Mage::app('default');




$f = new fixMe();
$f->run();


class fixMe
{

	private $currentProductList;
	function run()
	{

		$found = 0;
		$notfound = 0;
		$fp = fopen('fixlist.csv', 'w');


		$lines = $this->loadFile();

		$orders = $this->processLinesToOrderArray($lines);
		foreach($orders as $order)
		{

			//print_r($order);

			$this->currentProductList = $this->processProducts($order['products']);

			/*

			foreach($this->currentProductList as $products)
			{

			foreach($products as $product)
			{
			//print_r($product);
			$connection = Mage::getSingleton('core/resource') -> getConnection('core_read');
			$select = $connection -> select()
			-> from('ecodes_downloadable', array('*'))
			-> where('serial=?', $product['serial']);

			$rowsArray = $connection -> fetchAll($select);
			if(count($rowsArray)>0)
			{
			echo "FOUND ".$product['serial']."\n";
			$found++;
			continue;
			}
			else
			{
			$notfound++;
			echo "Cannot find ".$product['serial']."\n";
			}
			}
			}

			*/

			//continue;

			$order = $this->loadOrderByOldOrderId($order['old_order_id_a']);
			$items = $order->getAllItems();

			//print_r($items);


			foreach ($items as $itemId => $item)
			{
				$ordersku=$item->getSku();









				$connection = Mage::getSingleton('core/resource') -> getConnection('core_read');
				$select = $connection -> select() -> from('ecodes_downloadable', array('*')) -> where('order_item_id=?', $item->getItemId());

				$rowsArray = $connection -> fetchAll($select);


				// $this->currentProductList[$ordersku];
				if(count($rowsArray)>0)
				{

					$count = 0;
					$found = false;
					foreach($this->currentProductList[$ordersku] as $product)
					{
						if($product['serial'] == $rowsArray[0]['serial'])
						{
							$this->currentProductList[$ordersku][$count]['used'] = 1;
							$found = true;
						}
						$count++;
					}


					if(!$found)
					{
						$count = 0;

						foreach($this->currentProductList[$ordersku] as $product)
						{
							if($this->currentProductList[$ordersku][$count]['used'] == 0)
							{
								if($this->addSerial($product,$item))
								{
									$this->currentProductList[$ordersku][$count]['used'] = 1;
										
									$resource = Mage::getSingleton('core/resource');
									$writeConnection = $resource->getConnection('core_write');
									$query = "UPDATE ecodes_downloadable SET order_item_id = '' WHERE id = ".(int)$rowsArray[0]['id'];
									$writeConnection->query($query);
									
										
								}else{
									echo "error\n";
									die;
									
								}
							}
							$count++;
						}



					}
				}else{

					foreach($this->currentProductList[$ordersku] as $product)
					{
						if($this->currentProductList[$ordersku][$count]['used'] == 0)
						{
							if($this->addSerial($product,$item))
							{
								$this->currentProductList[$ordersku][$count]['used'] = 1;
							}else{
								echo "error 2\n";
								
								$connection = Mage::getSingleton('core/resource') -> getConnection('core_read');
								$select = $connection -> select() -> from('ecodes_downloadable', array('*')) -> where('serial=?', $this->currentProductList[$ordersku][$count]['serial']);
								
								$rowsArray = $connection -> fetchAll($select);
								
								print_r($rowsArray);
								die;
								
							}
						}
						$count++;
					}

				}


			}


		}


			
		return false;
	}
	function addSerial($product,$item)
	{
		$data = array(
				'id' => NULL,
				'serial' => $product['serial'],
				'order_item_id' => $item->getItemId(),
				'enabled' => '1',
				'updated_at' => CURRENT_TIMESTAMP,
				'created_at' => CURRENT_TIMESTAMP,
				'gp_sku' => $product['data']['product_sku'],
				'document_id' => $product['data']['eCodes_ID'],
				'product_title' => mysql_real_escape_string($item -> getName())
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
			//echo "\n-------------------222-----------------\n";
			//echo $e->getMessage();
			//print_r($data);
			return false;

		}
	}
	function serialIsInProductList($serial)
	{
		foreach($this->currentProductList as $line)
		{
			if($line['serial'] == $serial)
			{
				if($line['used'] ==0)
					return 2;

				return 1;
			}
		}

		return 0;
	}

	function processProducts($a)
	{
		$products = array();
		//print_r($a);
		foreach($a as $product)
		{

			$ser['serial'] = $product['download_serial_number'];
			$ser['used'] = "0";
			$ser['data'] = $product;
			$products[$product['eCodes_ID']][] = $ser;

		}
		return $products;
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
}