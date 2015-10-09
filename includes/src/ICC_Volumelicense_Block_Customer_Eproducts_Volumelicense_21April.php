<?php
class ICC_Volumelicense_Block_Customer_Eproducts_Volumelicense extends Mage_Core_Block_Template {

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}
        public function __construct()
        {
            // we have to load only unique orders by current customer
            $products = Mage::getModel('volumelicense/volumelicense')->getCollection();
            $products->addFieldToFilter('max_register',array('gt' => 0));
            $products->setOrder ( 'created_at', 'desc' );
            $products->getByUserId($this->getCustomer()->getId());
            $this->setVolumeProducts($products);
        }

        protected function _prepareLayout()
        {
            $pager = $this->getLayout()->createBlock('page/html_pager', 'volumelicense.customer.products.pager')
                ->setCollection($this->getVolumeProducts());
            $this->setChild('volumelicense_pager', $pager);
            $this->getVolumeProducts()->load();
            //collect current order's ids
            return $this;
        }

        public function getPagerHtml()
        {
            return $this->getChildHtml('volumelicense_pager');
        }
        
         public function getVolumeselfUsers() 
        {
                return  Mage::getModel('volumelicense/volumelicense')->getCollection()->getByUserId($this->getCustomer()->getId());
        }

        
}
