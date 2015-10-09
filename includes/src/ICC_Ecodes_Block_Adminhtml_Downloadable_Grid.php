<?php

class ICC_Ecodes_Block_Adminhtml_Downloadable_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Initialize grid settings
     *
     */
    public function _construct()
    {
        parent::_construct();
        
        $this->setId('ecodesGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
    /**
     * Prepare downloadable ecodes serials collection
     * 
     * @return ICC_Ecodes_Block_Adminhtml_Downloadable_Grid 
     */
    protected function _prepareCollection()
    {
        if (!$this->getCollection())
        {
            $collection = Mage::getModel('ecodes/downloadable')->getCollection();
            $collection->attachAdminGridColumns();
            $this->setCollection($collection);
        }

        return parent::_prepareCollection();
    }

    /**
     * Configuration of grid
     * 
     * @return $this
     */
    protected function _prepareColumns() {
        $this->addColumn('id', array(
            'header'    => Mage::helper('ecodes')->__('ID'),
            'width'     => '50px',
            'sortable'  => true,
            'index'     => 'id',
            'type'      => 'number'
        ));
        $this->addColumn('serial', array(
            'header'    => Mage::helper('ecodes')->__('Serial Number'),
            'width'     => '180px',
            'index'     => 'serial',
            'type'      => 'string',
            'sortable'  => true
        ));
        
        $this->addColumn('enabled', array(
            'header'    => Mage::helper('ecodes')->__('Enabled'),
            'width'     => '50px',
            'index'     => 'enabled',
            'type'      => 'options',
            'options'   => array('1'=>'Enabled', '0'=>'Disabled'),
            'sortable'  => false
            
        ));
        
        $this->addColumn('in_use', array(
            'header'        => Mage::helper('ecodes')->__('Used'),
            'width'         => '50px',
            'index'         => 'attached_to_order_item',
            'filter_index'  => 'IF(`main_table`.`order_item_id`, \'Yes\', \'No\')',
            'type'          => 'options',
            'options'       => array('Yes'=>'Yes', 'No'=>'No'),
            'sortable'      => false
        ));
        
        $this->addColumn('gp_sku', array(
            'header'        => Mage::helper('ecodes')->__('GP SKU'),
            'width'         => '150px',
            'index'         => 'gp_sku',
//            'filter_index'  => '`catalog_product`.`sku`',
            'type'          => 'string',
            'sortable'      => true
        ));
        
        $this->addColumn('product_title', array(
            'header'    => Mage::helper('ecodes')->__('Product Title'),
            'width'     => '250px',
            'index'     => 'product_title',
            'type'      => 'string',
//            'filter_index'  => '`product_varchar`.`value`',
            'sortable'  => true
        ));
        
        $this->addColumn('document_id', array(
            'header'    => Mage::helper('ecodes')->__('Document ID'),
            'width'     => '250px',
            'index'     => 'document_id',
            'type'      => 'string',
//            'filter_index'  => '`product_varchar`.`value`',
            'sortable'  => true
        ));
        
        $this->addColumn('customer_id', array(
            'header'    => Mage::helper('ecodes')->__('Customer ID'),
            'width'     => '80px',
            'sortable'  => true,
            'index'     => 'customer_id',
            'type'      => 'number'
        ));
        /* */
        return parent::_prepareColumns();
    }
    
    public function _prepareMassaction() 
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('serial');

        $this->getMassactionBlock()->addItem('assign', array(
            'label'     => Mage::helper('ecodes')->__('Assign to Order Item'),
            'url'       => $this->getUrl($this->_getControllerUrl('massAssign')),
            'confirm'   => Mage::helper('ecodes')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('delete', array(
            'label'     => Mage::helper('ecodes')->__('Disable'),
            'url'       => $this->getUrl($this->_getControllerUrl('massDisable')),
            'confirm'   => Mage::helper('ecodes')->__('Are you sure?')
        ));

        return $this;
    }
    
    /**
     * Get Url to action
     *
     * @param  string $action action Url part
     * @return string
     */
    protected function _getControllerUrl($action = '')
    {
        return '*/*/' . $action;
    }

    /**
     * Retrieve row url
     *
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl($this->_getControllerUrl('edit'), array(
            'id' => $row->getId()
        ));
    }
}