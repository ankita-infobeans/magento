<?php

class ICC_Volumelicense_Block_Adminhtml_Reports_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId("reportsGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
       // $customercollection = Mage::getResourceModel('customer/customer_collection')
         //       ->addNameToSelect();
        $collection = Mage::getModel("volumelicense/reports")->getCollection();
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
            "header" => Mage::helper("volumelicense")->__("ID"),
            "align" => "right",
            "width" => "50px",
            "type" => "number",
            "index" => "id",
        ));

        $this->addColumn("order_number", array(
            "header" => Mage::helper("volumelicense")->__("Order Number"),
            "index" => "order_number",
        ));
        $this->addColumn("parent_order_num", array(
            "header" => Mage::helper("volumelicense")->__("Parent Order"),
            "index" => "parent_order_num",
        ));
        $this->addColumn("email", array(
            "header" => Mage::helper("volumelicense")->__("Email"),
            'index' => 'email',
            "filter_index" => "main_table.email",
        ));
        $this->addColumn("customer_name", array(
            "header" => Mage::helper("volumelicense")->__("Customer Name"),
            "index" => "name",
        ));
        $this->addColumn("reassigned_to", array(
            "header" => Mage::helper("volumelicense")->__("Reassigned To"),
            "index" => "reassigned_to",
            'renderer' => 'volumelicense/adminhtml_volumelicense_renderer_reassign',
            'filter_condition_callback' => array($this, '_addReassignEmailsFilter')
        ));
        $this->addColumn("link_data", array(
            "header" => Mage::helper("volumelicense")->__("Link Info"),
            "index" => "link_data",
            'renderer' => 'volumelicense/adminhtml_volumelicense_renderer_linkinfo',
        ));
        $this->addColumn("from_date", array(
            "header" => Mage::helper("volumelicense")->__("Purchased Date"),
            "index" => "from_date",
            'type' => 'datetime',
        ));
        $this->addColumn("to_date", array(
            "header" => Mage::helper("volumelicense")->__("Reassigned Date"),
            "index" => "to_date",
            'type' => 'datetime',
            'renderer' => 'volumelicense/adminhtml_volumelicense_renderer_removeDate',
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
          $custom = Mage::getModel("volumelicense/reports")->getCollection()->addFieldToFilter('email', $condition)->addFieldToSelect('id');
          $ids = array_column($custom->getData(), 'id');
          $collection->addFieldToFilter('reassigned_to', array('in' => $ids));
          return $this;
      }

}
