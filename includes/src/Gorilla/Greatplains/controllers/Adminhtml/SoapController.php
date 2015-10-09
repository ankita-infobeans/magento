<?php
class Gorilla_Greatplains_Adminhtml_SoapController extends Mage_Adminhtml_Controller_Action {
	public function indexAction() {
		// $this->loadLayout();
		$type = $this->getRequest ()->getParam ( 'type', false );
		// echo $type;
		if ($type == "getsku") {
			
			$sku = $this->getRequest ()->getParam ( 'sku', false );
			// echo $type." ".$sku;
			$model = Mage::getModel ( "greatplains/soap" );
			$product = $model->getProductBySku ( $sku );
			
			$json = json_encode ( $product );
			echo $json;
		
		}
		
		// $this->renderLayout();
	}
	public function updateproductAction() {
		$model = Mage::getModel ( "greatplains/soap" );
		;
	}
	
	public function getskuAction() {
		// $this->loadLayout();
		// $sku =
		// echo $sku;
		// $this->renderLayout();
	
	}

}