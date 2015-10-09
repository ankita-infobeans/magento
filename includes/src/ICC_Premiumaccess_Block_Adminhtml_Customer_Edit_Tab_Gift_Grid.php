<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ICC_Premiumaccess_Block_Adminhtml_Customer_Edit_Tab_Gift_Grid  extends Mage_Adminhtml_Block_Widget_Grid
{
    public function _construct()
    {   //Mage::log('grid block called', null, 'premium-grid-block.log');
        parent::_construct();
        $this->setId('giftid');
        $this->setUseAjax(true);
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setDefaultLimit(100);
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
       $customer_id = (int)$this->getRequest()->getParam('id'); 
       //$collection = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
       //$collection->addFieldToFilter('main_table.customer_id', array('eq' => $customer_id)); 
       $collection = Mage::getModel('icc_premiumaccess/registry')->getCollection(); 
       $collection->addFieldToSelect('id');
       $collection->addFieldToSelect('parent_customer_id');
       $collection->addFieldToSelect('subscription_id');
       $_resource_table = Mage::getSingleton('core/resource')->getTableName('ecodes_premium_access'); 
       $collection->getSelect()->join(array('rg_access'=>$_resource_table),'`main_table`.`subscription_id` = `rg_access`.`id`',
       array('rg_access.product_name', 'rg_access.order_number','rg_access.sku', 'rg_access.expiration', 'rg_access.seats_total',
           'rg_access.registered_count' , 'rg_access.notes', 'rg_access.status')); 
       $collection->addFieldToFilter('main_table.assign_customer_id',array('eq'=>$customer_id));
       $collection->addFieldToFilter('main_table.parent_customer_id',array('gt'=>0));
       $collection->addFieldToFilter('main_table.status',array('in'=> array(0,1)));
       $this->setCollection($collection); 
       //echo $collection->getSelect();die;
       return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('product_name', array(
            'header'    => $this->__('Product Name'),            
            'sortable' => true,
            'index'     => 'product_name',
            'type'  => 'string',
        ));
        $this->addColumn('parent_customer_id', array(
            'header'    => $this->__('Gifted By'),            
            'sortable' => true,
            'index'     => 'parent_customer_id',
            'type'  => 'string',
            'renderer'  => 'ICC_Premiumaccess_Block_Adminhtml_Customer_Edit_Renderer'
        ));
        $this->addColumn('order_number', array(
            'header'    => $this->__('Order #'),
            'width'     => '100px',
            'sortable' => true,
            'index'     => 'order_number',
            'type'  => 'string',
            'renderer'  => 'ICC_Premiumaccess_Block_Adminhtml_Customer_Edit_Rendererurl'
        ));
        $this->addColumn('sku', array(
            'header'    => $this->__('Sku'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'sku',
            'type'  => 'string',
        ));
        /*$this->addColumn('seats_total', array(
            'header'    => $this->__('Total Qty'),
            'width'     => '30px',
            'sortable' => true,
            'index'     => 'seats_total',
            'type'  => 'string',
        ));*/
     /*   $this->addColumn('registered_count', array(
            'header'    => $this->__('Gift Qty'),
            'width'     => '30px',
            'sortable' => true,
            'index'     => 'registered_count',
            'type'  => 'string',
        ));*/
        $this->addColumn('expiration', array(
            'header'    => $this->__('Expiration Date'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'expiration',
            'type'  => 'date',
        ));       
        
        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => array(0 => 'Disabled', 1=>'Enabled', 2 =>'Expiry' ),
        ));
        
        
       $link= Mage::helper('adminhtml')->getUrl('adminhtml/catalog_product/edit/') .'id/$entity_id';
        $this->addColumn('action',
            array(
                'header'    =>  $this->__('Action'),
                'width'     => '100px',
                'type'      => 'action',
                'getter'    => 'getSubscriptionId',
                'actions'   => array(                     
                    array(
                        'caption'   => $this->__('View Details'),
                        'url'       =>  array('base' => '*/premiumcustomers/index'),
                        'field'     => 'id'                         
                       // 'confirm'   => 'Are you sure you would like to manage the users this order item?',
                    ),                    
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
    }
    // /*
    public function getGridUrl()
    {
    	//die;
        return $this->getUrl('*/premiumuser/gift', array('_current'=> true));
    }
    /* */
    // /*
    public function getRowUrl($row)
    {
        //return $this->getUrl('*/premium/edit', array('id'=>$row->getId()));
    }
    /* */
}