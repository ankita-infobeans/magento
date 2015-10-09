<?php

class Gorilla_Greatplains_Block_Adminhtml_Modelname_Grid extends Mage_Adminhtml_Block_Widget_Grid {
	
	public function __construct() {
		parent::__construct ();
		$this->setId ( "modelnameGrid" );
		$this->setDefaultSort ( "tablename_id" );
		$this->setDefaultDir ( "ASC" );
		$this->setSaveParametersInSession ( true );
	}
	
	protected function _prepareCollection() {
		$collection = Mage::getModel ( "greatplains/modelname" )->getCollection ();
		$this->setCollection ( $collection );
		return parent::_prepareCollection ();
	}
	protected function _prepareColumns() {
		$this->addColumn ( "tablename_id", array ("header" => Mage::helper ( "greatplains" )->__ ( "ID" ), "align" => "right", "width" => "50px", "index" => "tablename_id" ) );
		$this->addColumn ( "name", array ("header" => Mage::helper ( "greatplains" )->__ ( "Modelname Name" ), "align" => "left", "index" => "name" ) );
		
		return parent::_prepareColumns ();
	}
	
	public function getRowUrl($row) {
		return $this->getUrl ( "*/*/edit", array ("id" => $row->getId () ) );
	}

}