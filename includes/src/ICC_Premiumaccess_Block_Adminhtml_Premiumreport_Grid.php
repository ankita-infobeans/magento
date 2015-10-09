<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ICC_Premiumaccess_Block_Adminhtml_Premiumreport_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
     public function __construct() {
        parent::__construct();
        $this->setId("reportsGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel("icc_premiumaccess/reports")->getCollection();
        $collection->getSelect()->joinLeft(
              array('vlr' => Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()->getSelect()), 'main_table.customer_id=vlr.entity_id', array('name') // added 'e.status' in stead of 'status'
         ); 
        //$collection->addExpressionFieldToSelect('parent_customer_id', 'IF( main_table.parent_customer_id> 0 ,"No", "Yes")');
        $this->setCollection($collection);
        
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn("id", array(
            "header" => Mage::helper("icc_premiumaccess")->__("ID"),
            "align" => "right",
            "width" => "50px",
            "type" => "number",
            "index" => "id",
        ));

        $this->addColumn("order_number", array(
            "header" => Mage::helper("icc_premiumaccess")->__("Order Number"),
            "index" => "order_number",
        ));
        $this->addColumn("parent_order_num", array(
            "header" => Mage::helper("icc_premiumaccess")->__("Parent Order"),
            "index" => "parent_order_num",
        ));
        $this->addColumn("email", array(
            "header" => Mage::helper("icc_premiumaccess")->__("Email"),
            'index' => 'email',
            "filter_index" => "main_table.email",
        ));
        $this->addColumn("customer_name", array(
            "header" => Mage::helper("icc_premiumaccess")->__("Customer Name"),
            "index" => "name",
        ));
        $this->addColumn("reassigned_to", array(
            "header" => Mage::helper("icc_premiumaccess")->__("Reassigned To"),
            "index" => "reassigned_to",
            'renderer' => 'icc_premiumaccess/adminhtml_premiumaccess_renderer_reassign',
            'filter_condition_callback' => array($this, '_addReassignEmailsFilter')
        ));
        $this->addColumn("link_data", array(
            "header" => Mage::helper("icc_premiumaccess")->__("Link Info"),
            "index" => "link_data",
            'renderer' => 'icc_premiumaccess/adminhtml_premiumaccess_renderer_linkinfo',
        ));
        $this->addColumn("from_date", array(
            "header" => Mage::helper("icc_premiumaccess")->__("Purchased Date"),
            "index" => "from_date",
            'type' => 'datetime',
        ));
        $this->addColumn("to_date", array(
            "header" => Mage::helper("icc_premiumaccess")->__("Reassigned Date"),
            "index" => "to_date",
            'type' => 'datetime',
            'renderer' => 'icc_premiumaccess/adminhtml_premiumaccess_renderer_removeDate',
        ));
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportXML', Mage::helper('sales')->__('XML'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return '#';
    }
    
    protected function _addReassignEmailsFilter($collection, $column){
          $condition = $column->getFilter()->getCondition();
          $custom = Mage::getModel("icc_premiumaccess/reports")->getCollection()->addFieldToFilter('email', $condition)->addFieldToSelect('id');
          $ids = array_column($custom->getData(), 'id');
          $collection->addFieldToFilter('reassigned_to', array('in' => $ids));
          return $this;
      }

}