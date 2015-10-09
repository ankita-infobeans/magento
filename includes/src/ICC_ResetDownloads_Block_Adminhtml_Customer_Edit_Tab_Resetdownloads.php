<?php

class ICC_ResetDownloads_Block_Adminhtml_Customer_Edit_Tab_Resetdownloads
    extends		Mage_Adminhtml_Block_Widget_Grid
	implements	Mage_Adminhtml_Block_Widget_Tab_Interface
{

    public function __construct()
    {
		
        parent::__construct();
		$this->setId('customer_edit_tab_resetdownloads');
        $this->setDefaultSort('created_at', 'desc');
        $this->setUseAjax(true);
		
    }

    protected function _prepareCollection()
    {
               
            /*
             * This code is changed for resolving reset download is not showing for VL orders by Abhilash on 21st June 2015
             */
                $filter_order = Mage::getModel('sales/order')->getCollection()
                        ->addFieldToSelect('entity_id')
                        ->addFieldToFilter('customer_id',array('eq'=> Mage::registry('current_customer')->getId() ))
                        ->addFieldToFilter('volume_license', '1')
                        ->addFieldToFilter('parent_order_id', array('null' => true));
                //get volume license parent order ids
                $ids = $filter_order->getAllIds(); 
               
                //get all downloadable order links collection
                 
                $collection = Mage::getModel('downloadable/link_purchased_item')->getCollection();
		$collection->getSelect()
				->join  (
					array('dlp'=>Mage::getSingleton('core/resource')->getTableName('downloadable_link_purchased')),
                    'main_table.purchased_id = dlp.purchased_id',
					array('pname'=>'product_name','psku'=>'product_sku')
				)->join (
					array('sfog'=>Mage::getSingleton('core/resource')->getTableName('sales_flat_order_grid')),
					'dlp.order_increment_id = sfog.increment_id'
				)
                        
                        ->join (
					array('soi'=>Mage::getSingleton('core/resource')->getTableName('sales_flat_order_item')),
					'main_table.order_item_id = soi.item_id',
                                array('sales_order_item_id'=>'item_id','volume_license','volume_license')
				);
		
           	  //$collection     -> addFieldToFilter('soi.order_id',array('nin'=> array($ids)))   // exclude volume license parent orders

                  if(count($ids) > 0)
		  {
		    $collection->addFieldToFilter('soi.order_id',array('nin'=> array($ids)));
		  }

                  $collection   -> addFieldToFilter('sfog.customer_id',array('eq'=> Mage::registry('current_customer')->getId() ))   
                                -> addFieldToFilter('sfog.status',array('neq'=> 'canceled' ))
				-> addFieldToSelect('item_id')
				-> addFieldToSelect('order_item_id')
				-> addFieldToSelect('product_id')
				-> addFieldToSelect('link_title')
				-> addFieldToSelect('number_of_downloads_bought')
				-> addFieldToSelect('number_of_downloads_used');
				
            $this->setCollection($collection);
            return parent::_prepareCollection();
    }

    /**
     * ######################## TAB settings #################################
     */
    /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('resetdownloads')->__('Reset Downloads');
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('resetdownloads')->__('Reset Downloads');
    }

	
    /**
     * Check if can show tab
     *
     * @return boolean
     */
    public function canShowTab()
    {
        $customer = Mage::registry('current_customer');
        return (bool)$customer->getId();
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }	
    /**
     * ######################## 			 #################################
     */


    protected function _prepareColumns()
    { 

        $this->addColumn('item_id', array(
            'header'    => Mage::helper('resetdownloads')->__('ID'),
            'width'     => '50px',
            'index'     => 'item_id',
        ));
		
        $this->addColumn('created_at', array(
            'header'    => Mage::helper('resetdownloads')->__('Purchase On'),
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));
		
	
        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('resetdownloads')->__('Order #'),
            'width'     => '100px',
            'index'     => 'increment_id',
        ));
		
        $this->addColumn('product_id', array(
            'header'    => Mage::helper('resetdownloads')->__('Product ID'),
            'width'     => '100px',
            'index'     => 'product_id',
        ));
		
        $this->addColumn('product_sku', array(
            'header'    => Mage::helper('resetdownloads')->__('Product SKU'),
            'width'     => '250px',
            'index'     => 'psku',
        ));

        $this->addColumn('product_name', array(
            'header'    => Mage::helper('resetdownloads')->__('Product Name'),
            'width'     => '250px',
            'index'     => 'pname',
        ));
        $this->addColumn('link_title', array(
            'header'    => Mage::helper('resetdownloads')->__('Link Title'),
            'width'     => '250px',
            'index'     => 'link_title',
        ));	
				
        $this->addColumn('number_of_downloads_bought', array(
            'header'    => Mage::helper('resetdownloads')->__('Number of downloads bought'),
            'width'     => '50px',
            'index'     => 'number_of_downloads_bought',
            'renderer'  => 'ICC_ResetDownloads_Block_Adminhtml_Customer_Edit_Renderer'
        ));

        $this->addColumn('number_of_downloads_used', array(
            'header'    => Mage::helper('resetdownloads')->__('Number of downloads used'),
            'width'     => '50px',
            'index'     => 'number_of_downloads_used',
        ));
		
        $this->addColumn('reset_downloads',
            array(
                'header'    => Mage::helper('resetdownloads')->__('Reset Downloads Remaining'),
				//'getter'    => 'getId',
				'actions'   => array (
                    'caption'	=> Mage::helper('resetdownloads')->__('Reset Downloads'),
					//'url'		=> $this->getResetLinkParams(),
					'gridId'	=> $this->getId(),
					'index'		=> 'item_id',
					'params' => array(
					
					)
				),
				'renderer'	=> 'ICC_ResetDownloads_Block_Adminhtml_Grid_Column_Renderer_Action',
                'filter'	=> false,
                'sortable'	=> false
				
                )
		);

		
		
	    return parent::_prepareColumns(); 
		
    }
/*
    public function getResetLinkParams()
    {
        return array(
            'base'      =>  'resetdownloads/grid/resetDownloads/id/'.Mage::registry('current_customer')->getId(),
        );
    }
*/
	
//    public function getRowUrl1($row)
 //   {
	/*
		$this->getUrl('resetdownloads/adminhtml_grid/ordersDownloads',
            array(
                Mage_Adminhtml_Model_Url::SECRET_KEY_PARAM_NAME
                =>
                Mage::getModel('adminhtml/url')->getSecretKey('adminhtml_mycontroller', 'index')
            )
        );
	*/
		//return $this->getUrl('*/sales_order/view', array('order_id' => $row->getId()));
		//return $this->getUrl('resetdownloads/grid/ordersDownloads', array('item_id' => $row->getId()));
		//return null;//$row->getId(); //;null;
		
    //}

    public function getGridUrl()
    {
		//$customer_id = Mage::registry('current_customer')->getId();
		//return $this->getUrl('resetdownloads/grid/ordersDownloads', array( 'id' => $customer_id ));

		return $this->getUrl('resetdownloads/grid/ordersDownloads', array('_current' => true));
    }

	
	
}


