<?php

class ICC_Ecodes_Block_Adminhtml_Customer_Edit_Tab_Downloadable_Available
    extends ICC_Ecodes_Block_Adminhtml_Downloadable_Grid
{
    public function _construct()
    {
        parent::_construct();
        $this->setId('customer_edit_tab_downloadable_available');
        $this->setUseAjax(true);
        $this->setHeaderText(Mage::helper('enterprise_checkout')->__('Available eCodes'));
    }

    /**
     * Returns additional javascript to init this grid
     *
     * @return Mage_Core_Model_Store
     */
    public function getAdditionalJavaScript ()
    {
        return "Event.observe(window, 'load',  function() {\n"
            . "setTimeout(function(){productConfigure.addListType('" . $this->getListType() . "', {urlFetch: '" . $this->getConfigureUrl() . "'})\n"
            . "});\n"
            . "checkoutObj.addSourceGrid({htmlId: '" . $this->getId() . "', listType: '" . $this->getListType() . "'});\n}, 10)";
    }

    /**
     * Return custom object name for js grid
     *
     * @return string
     */
    public function getJsObjectName()
    {
        return 'availableGrid';
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'ecodes/mysql4_downloadable_collection';
    }

    /**
     * Configuring and setting collection
     *
     * @return Enterprise_Rma_Block_Adminhtml_Customer_Edit_Tab_Rma
     */
    protected function _prepareCollection()
    {
        $customerId = null;

        if (Mage::registry('current_customer') && Mage::registry('current_customer')->getId()) {
            $customerId = Mage::registry('current_customer')->getId();
        } elseif ($this->getCustomerId())  {
            $customerId = $this->getCustomerId();
        }
        if ($customerId) {
            $collection = Mage::getModel('ecodes/downloadable')->getCollection();
            $collection
                ->filterOutUnavailable()
                ->attachAdminGridColumns()
            ;
            /* @var $collection ICC_Ecodes_Model_Mysql4_Downloadable_Collection */
            $this->setCollection($collection);
        }
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return Enterprise_Rma_Block_Adminhtml_Rma_Grid
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->removeColumn('customer_id');
    }

    /**
     * Get Url to action
     *
     * @param  string $action action Url part
     * @return string
     */
    protected function _getControllerUrl($action = '')
    {
        return '*/downloadable/' . $action;
    }

    /**
     * Get Url to action to reload grid
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/downloadable/availableCustomer', array('_current' => true));
    }

    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }


}