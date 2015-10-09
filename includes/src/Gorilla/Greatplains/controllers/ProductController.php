<?php

class Gorilla_Greatplains_ProductController extends Mage_Adminhtml_Controller_Action {
	public function indexAction() {
		$this->loadLayout ();
		$this->_title ( $this->__ ( "Great Plains" ) );
		$this->renderLayout ();
	}
	
	public function getProductInformation() {
	
	}
	
	public function updateproductAction() {
	
	}
}