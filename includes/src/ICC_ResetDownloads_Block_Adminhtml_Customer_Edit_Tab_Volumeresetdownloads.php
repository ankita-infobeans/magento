<?php

class ICC_ResetDownloads_Block_Adminhtml_Customer_Edit_Tab_Volumeresetdownloads
    extends		Mage_Adminhtml_Block_Widget_Grid
	implements	Mage_Adminhtml_Block_Widget_Tab_Interface
{

    public function __construct()
    {
		
        parent::__construct();
		$this->setId('customer_edit_tab_volumeresetdownloads');
        $this->setDefaultSort('created_at', 'desc');
        $this->setUseAjax(true);
		
    }

    protected function _prepareCollection()
    {
		$collection = Mage::getModel('volumelicense/links')->getCollection();
		
		$collection->getSelect()
				->join  (
					array('vlr'=>Mage::getSingleton('core/resource')->getTableName('icc_volumelicense_registry')),
					//'main_table.order_item_id = dlp.order_item_id', // changed because the main field for the relationship between tables downloadable_link_purchased_item and downloadable_link_purchased is field purchased_id NOT order_item_id
                                        'main_table.registry_id = vlr.id',array('assign_customer_email')
					
				)
                                ->join  (
					array('vl'=>Mage::getSingleton('core/resource')->getTableName('icc_volumelicense')),
					//'main_table.order_item_id = dlp.order_item_id', // changed because the main field for the relationship between tables downloadable_link_purchased_item and downloadable_link_purchased is field purchased_id NOT order_item_id
                                        'vlr.volumelicense_id = vl.id',array('created_at','order_number','product_id','sku','product_name')
					
				)
//                                ->join (
//					array('sfo'=>Mage::getSingleton('core/resource')->getTableName('sales_flat_order')),
//					'vl.order_number = sfo.increment_id',array('customer_email')
//				)
                        
                                ->join (
					array('lpi'=>Mage::getSingleton('core/resource')->getTableName('downloadable/link_purchased_item')),
					'main_table.link_id = lpi.link_id',array('link_title')
				);
		
		$collection    -> addFieldToFilter('vlr.assign_customer_id',array('eq'=> Mage::registry('current_customer')->getId() ))   // ex.31305
				->addFieldToFilter('vlr.assign_status',array('eq'=> 1 ))
				-> addFieldToSelect('link_download_limit')
                                -> addFieldToSelect('number_of_downloads')
				-> addFieldToSelect(array('item_id'=>'id')); 
		$collection->getSelect()->group('main_table.id');
          // echo $collection->getSelect();             
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
        return Mage::helper('resetdownloads')->__('Reset Volume License Downloads');
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('resetdownloads')->__('Reset Volume License Downloads');
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
		
	
        $this->addColumn('order_number', array(
            'header'    => Mage::helper('resetdownloads')->__('Order #'),
            'width'     => '100px',
            'index'     => 'order_number',
        ));
		
        $this->addColumn('product_id', array(
            'header'    => Mage::helper('resetdownloads')->__('Product ID'),
            'width'     => '100px',
            'index'     => 'product_id',
        ));
		
        $this->addColumn('sku', array(
            'header'    => Mage::helper('resetdownloads')->__('Product SKU'),
            'width'     => '250px',
            'index'     => 'sku',
        ));

        $this->addColumn('product_name', array(
            'header'    => Mage::helper('resetdownloads')->__('Product Name'),
            'width'     => '250px',
            'index'     => 'product_name',
        ));
       $this->addColumn('link_title', array(
            'header'    => Mage::helper('resetdownloads')->__('Link Title'),
            'width'     => '250px',
            'index'     => 'link_title',
        ));
       
       $this->addColumn('assign_customer_email', array(
            'header'    => Mage::helper('resetdownloads')->__('Customer Email ID'),
            'width'     => '250px',
            'index'     => 'assign_customer_email',
        ));
       
//	$this->addColumn('customer_email', array(
//            'header'    => Mage::helper('resetdownloads')->__('Purchase Customer Email ID'),
//            'width'     => '250px',
//            'index'     => 'customer_email',
//        ));	
		
        $this->addColumn('link_download_limit', array(
            'header'    => Mage::helper('resetdownloads')->__('Number of downloads bought'),
            'width'     => '50px',
            'index'     => 'link_download_limit',
        ));

        $this->addColumn('number_of_downloads', array(
            'header'    => Mage::helper('resetdownloads')->__('Number of downloads used'),
            'width'     => '50px',
            'index'     => 'number_of_downloads',
        ));
		
        $this->addColumn('reset_downloads',
            array(
                'header'    => Mage::helper('resetdownloads')->__('Reset Volume License Downloads Remaining'),
				//'getter'    => 'getId',
				'actions'   => array (
                    'caption'	=> Mage::helper('resetdownloads')->__('Reset Volume License Downloads'),
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
    public function getGridUrl()
    {
		return $this->getUrl('resetdownloads/volumeGrid/volumeresetdownloads', array('_current' => true));
    }

	
	
}


