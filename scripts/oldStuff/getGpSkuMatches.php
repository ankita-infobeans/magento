<?php

require_once '../app/Mage.php';

umask ( 0 );

Mage::app ( 'default' );

// $collection = Mage::getModel('catalog/product')
// ->getCollection();

// foreach ($collection as $product) {
// echo $product->getName() . "<br />";

// }

$product = Mage::getModel ( 'catalog/product' );

$file = "file.csv";
$fp = fopen($file, 'a');

$fields = array("Name","Sku","GP Sku");
fputcsv($fp, $fields);
$atsets = getAttributeSets ();
fclose($fp);
foreach ( $atsets as $at ) {
	
	$skus = getProductSkusByAttribute ( $at );
	
	foreach ( $skus as $sku ) {
		echo "loading $sku\n";
		//continue;
		
		$id = $product->getIdBySku($sku);
		
		$product->load ( $id );
		
		$fields = array($product->getName(), $product->getSku(), $product->getGpSku());
		
		echo $product->getName().",". $product->getSku().",". $product->getGpSku()."\n";
		$fp = fopen($file, 'a');
		fputcsv($fp, $fields);
		fclose($fp);
	}
}


fclose($fp);

































function getProductSkusByAttribute($att) {
	$products = Mage::getModel ( 'catalog/product' )->getCollection ()->addFieldToFilter ( 'attribute_set_id', $att ['attribute_set_id'] )->addFieldToFilter ( 'type_id', array (
			'in' => array (
					'simple',
					'virtual',
					'downloadable' 
			) 
	) )->addAttributeToSelect ( "gp_sku" );
	$skus = array ();
	
	foreach ( $products as $product ) {
		if ($product->getGpSku () != "") {
			$skus [] = $product->getSku ();
		}
	}
	return $skus;
}

function getAttributeSets() {
	$attributeSet = Mage::getModel ( "eav/entity_attribute_set" )->getCollection ();
	
	$list = array ();
	foreach ( $attributeSet as $at ) {
		$single = array ();
		$single ['attribute_set_id'] = $at->getAttributeSetId (); // $at['attribute_set_id'];
		$single ['attribute_set_name'] = $at->getAttributeSetName (); // ['attribute_set_name'];
		$list [] = $single;
	}
	
	return $list;
}




