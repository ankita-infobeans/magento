<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */
class Amasty_Xcoupon_Block_Adminhtml_CouponsSystem extends Mage_Adminhtml_Block_Widget_Grid
{
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
        $this->addColumn('sku:name', array(
            'header'    => Mage::helper('salesrule')->__('Book SKU'),
            'index'     => 'sku:name',
            'renderer'  => 'Amasty_Coupons_Block_Adminhtml_Renderer_Sku',// THIS IS WHAT THIS POST IS ALL ABOUT
        ));
        
        $this->addColumn('name', array(
            'header'    => Mage::helper('salesrule')->__('Book Title'),
            'index'     => 'name',
            'renderer'  => 'Amasty_Coupons_Block_Adminhtml_Renderer_Name',// THIS IS WHAT THIS POST IS ALL ABOUT
        ));
        
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

        return parent::_prepareColumns();
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setChild('export_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Export'),
                    'onclick'   => $this->getJsObjectName().'.doExport()',
                    'class'   => 'task'
                ))
        );
        $this->setChild('export_coupon_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Export Coupon'),
                    'onclick'   => $this->getJsObjectName().'.doExport()',
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
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/adminhtml_coupon/edit', array('id' => $row->getId())); 
    }
      
}