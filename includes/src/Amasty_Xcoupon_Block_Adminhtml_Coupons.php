<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */
class Amasty_Xcoupon_Block_Adminhtml_Coupons extends Mage_Adminhtml_Block_Widget_Grid
{
     protected $_exportCouponTypes = array();
    
    public function __construct()
    {
        parent::__construct();
        $this->setId('amxcouponCoupons');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $id = $this->getRequest()->getParam('id');
        
        $coupons = Mage::getResourceModel('salesrule/coupon_collection')
            ->addFieldToFilter('rule_id', $id);
            
        $select = $coupons->getSelect();
        $select->where('is_primary IS NULL');
   
        $this->setCollection($coupons);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->setTemplate('exportcoupon/grid.phtml');
        $this->addColumn('code', array(
            'header'    => Mage::helper('salesrule')->__('Coupon'),
            'index'     => 'code',
        ));

        $this->addColumn('usage_limit', array(
            'header'    => Mage::helper('salesrule')->__('Uses per Coupon'),
            'index'     => 'usage_limit',
        ));

        $this->addColumn('usage_per_customer', array(
            'header'    => Mage::helper('salesrule')->__('Uses per Customer'),
            'index'     => 'usage_per_customer',
        ));

        $this->addColumn('times_used', array(
            'header'    => Mage::helper('salesrule')->__('Times Used'),
            'index'     => 'times_used',
        ));
        
        $this->addColumn('action', array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Delete'),
                        'url'     => array('base'=>'*/*/delete'),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true,
        )); 
        
        $this->addExportType('*/*/exportCsv', Mage::helper('salesrule')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('salesrule')->__('XML'));
        $this->addExportCouponType('*/*/exportCouponCsv', Mage::helper('salesrule')->__('CSV'));
        $this->addExportCouponType('*/*/exportCouponXml', Mage::helper('salesrule')->__('XML'));
                
        return parent::_prepareColumns();
    }
     
    public function getRowUrl($row)
    {
        return $this->getUrl('*/adminhtml_coupon/edit', array('id' => $row->getId())); 
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setChild('export_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Export'),
                    'onclick'   => $this->getJsObjectName().'.doExportCoupon(\'export\')',
                    'class'   => 'task'
                ))
        );
        $this->setChild('export_coupon_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Export'),
                    'onclick'   => $this->getJsObjectName().'.doExportCoupon(\'export_coupon\')',
                    'class'   => 'task'
                ))
        );
        $this->setChild('reset_filter_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Reset Filter'),
                    'onclick'   => $this->getJsObjectName().'.resetFilter()',
                ))
        );
        $this->setChild('search_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Search'),
                    'onclick'   => $this->getJsObjectName().'.doFilter()',
                    'class'   => 'task'
                ))
        );
        return $this;
    }
    
    public function getExportCouponButtonHtml()
    {
        return $this->getChildHtml('export_coupon_button');
    }
    
    /**
     * Retrieve grid export types
     *
     * @return array|false
     */
    public function getExportCouponTypes()
    {
        if (!empty($this->_exportCouponTypes)) {
            foreach ($this->_exportCouponTypes as $exportType) {
                $url = Mage::helper('core/url')->removeRequestParam($exportType->getUrl(), 'action');
                $exportType->setUrl(Mage::helper('core/url')->addRequestParam($url, array('action' => 'creditmemo')));
            }
            return $this->_exportCouponTypes;
        }
        return false;
    }
    
    public function addExportCouponType($url, $label)
    {
        $this->_exportCouponTypes[] = new Varien_Object(
            array(
                'url'   => $this->getUrl($url, array('_current'=>true)),
                'label' => $label
            )
        );
        return $this;
    }
    
    public function getCouponSystemDetail () {
        $id = $this->getRequest()->getParam('id');
        $coupons = Mage::getModel('salesrule/rule')->load($id);
        return $coupons->getUsedForCouponSystem();
    }
    
}