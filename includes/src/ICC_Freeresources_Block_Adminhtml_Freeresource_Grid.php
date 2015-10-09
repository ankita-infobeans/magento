<?php
/**
 * Free Resource admin grid block
 *
 * @category    ICC
 * @package     ICC_Freeresources
  */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {
    /**
     * constructor
     * @access public

     */
    public function __construct(){
        parent::__construct();
        $this->setId('freeresourceGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }
    /**
     * prepare collection
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Grid

     */
    protected function _prepareCollection(){
        $collection = Mage::getModel('icc_freeresources/freeresource')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    /**
     * prepare grid collection
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Grid

     */
    protected function _prepareColumns(){
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('icc_freeresources')->__('Id'),
            'index'        => 'entity_id',
            'type'        => 'number'
        ));
        
        $this->addColumn('title', array(
            'header'    => Mage::helper('icc_freeresources')->__('Free Resource'),
            'align'     => 'left',
            'index'     => 'title',
        ));
        
        $this->addColumn('action',
            array(
                'header'=>  Mage::helper('icc_freeresources')->__('Action'),
                'width' => '100',
                'type'  => 'action',
                'getter'=> 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('icc_freeresources')->__('Edit'),
                        'url'   => array('base'=> '*/*/edit'),
                        'field' => 'id'
                    )
                ),
                'filter'=> false,
                'is_system'    => true,
                'sortable'  => false,
        ));
        $this->addExportType('*/*/exportCsv', Mage::helper('icc_freeresources')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('icc_freeresources')->__('Excel'));
        $this->addExportType('*/*/exportXml', Mage::helper('icc_freeresources')->__('XML'));
        return parent::_prepareColumns();
    }
    
    /**
     * prepare mass action
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Grid

     */
    protected function _prepareMassaction(){
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('freeresource');
        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('icc_freeresources')->__('Delete'),
            'url'  => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('icc_freeresources')->__('Are you sure?')
        ));
        return $this;
    }
    /**
     * get the row url
     * @access public
     * @param ICC_Freeresources_Model_Freeresource
     * @return string

     */
    public function getRowUrl($row){
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
    /**
     * get the grid url
     * @access public
     * @return string

     */
    public function getGridUrl(){
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
    /**
     * after collection load
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Grid

     */
    protected function _afterLoadCollection(){
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }
}
