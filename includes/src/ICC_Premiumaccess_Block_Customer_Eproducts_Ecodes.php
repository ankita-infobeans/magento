<?php

class ICC_Premiumaccess_Block_Customer_Eproducts_Ecodes extends Mage_Core_Block_Template {

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}
        
        /**
         * This method prepare collection of premium accecss for purchased as well as share premium access.
         */
        public function __construct()
        {
            $model = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
            $model->addFieldToFilter('customer_id',array('eq'=>$this->getCustomerId()));
            $model->addFieldToFilter('status',array('eq'=>'1'));
            $premiumaccess = array();
            foreach ($model as $m):
                array_push($premiumaccess,$m->getId());
            endforeach;
            $model = Mage::getModel('icc_premiumaccess/registry')->getCollection();
            $model->addFieldToFilter('assign_customer_id',array('eq'=>$this->getCustomerId()));
            $model->addFieldToFilter('status',array(array('eq'=>  ICC_Premiumaccess_Helper_Data::PENDING),array('eq'=>ICC_Premiumaccess_Helper_Data::ACTIVE)));
           $premiumregistry = array();
           foreach ($model as $m):
                array_push($premiumregistry,$m->getSubscriptionId());
            endforeach;
           $merge = array_merge($premiumaccess,$premiumregistry);
           $array = array_unique (array_merge ($premiumaccess, $premiumregistry));
           $collection = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
           $collection->addFieldToFilter('id',array('in'=>$array));
           $collection->addFieldToFilter('seats_total',array('gt'=> 0));
           //$collection->setOrder ( 'order_number', 'desc' );
           $collection->getSelect()->order('order_number desc');
           //echo $collection->getSelect();
           $this->setPremiumAccess($collection);
           
        }

        /**
         * This method prepare layout for premium access record for current customer.
         * @return \ICC_Premiumaccess_Block_Customer_Eproducts_Ecodes
         */
        protected function _prepareLayout()
        {
            $pager = $this->getLayout()->createBlock('page/html_pager', 'premiumaccess.customer.products.pager')
                ->setCollection($this->getPremiumAccess());
            $this->setChild('premiumaccess_pager', $pager);
            $this->getPremiumAccess()->load();
            return $this;
        }

        /**
         * This method define pager for premium access grid.
         * @return type
         */
        public function getPagerHtml()
        {
            return $this->getChildHtml('premiumaccess_pager');
        }
        
        /**
         * This method return premium access collection by customer id
         * @return type
         */
        public function getPremiumSubscriptions() {
		return Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection()->getRegisteredByCustomerId($this->getCustomer()->getId());               
	}
        
        /**
         * This method returns premium access registry collection by user id 
         * @return type
         */
        public function getPremiumSubscriptionsRegisrty() {
                return Mage::getModel('icc_premiumaccess/registry')->getCollection()->getByUserId($this->getCustomer()->getId());
        }

        /**
         * This method used to return M j,Y format for given date
         * @param type $sqlDate
         * @return type
         */
	public function formatDate1($sqlDate) {
		return date('M j, Y', strtotime($sqlDate));
	}

        /**
         * This function we will used in next version,
         * 
	public function isExpiring($expriationDate) {
        $num_days = Mage::getStoreConfig('catalog/renew_expire_date/email_before_days');
		$expTime = strtotime($expriationDate);
		return ($expTime < (time() + (60*60*24* $num_days )));
	}

	public function isExpired($expirationDate,$sub=null) {
        $num_days = Mage::getStoreConfig('catalog/renew_expire_date/renewal_grace_days');
		$expTime = strtotime($expirationDate) + 86400;
		if($sub) {
			if(!$this->canRenew($sub)) {
				return (time() > $expTime);
			}
		}
		return (time() > ($expTime + (60*60*24* $num_days )));
	}
         * 
         */
        
        /**
         * This method used to validate expiry date for current premium access.
         * @param type $expriationDate
         * @return type
         */
        public function isExpiring($expriationDate) {
        
		$expTime = strtotime($expriationDate);
		return ($expTime < time());
	}

        /**
         * This method is used to validate expiry status of current premium access from given date 
         * 
         * 
         * @param type $expirationDate
         * @param type $sub
         * @return type
         */
	public function isExpired($expirationDate,$sub=null) {
		$expTime = strtotime($expirationDate);// + 86400;
		return (time() > $expTime );
	}
        /*
         * These function we will used in next version.
        public function getRenewalProductUrl($sub)
        {
            $product_id = $sub->getProductId();
            $product = Mage::getModel('catalog/product')->load($product_id);
            $renewal_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $product->getRenewSku() );
            return ( ! empty($renewal_product))?($renewal_product->getProductUrl()) : ('#');
        }

        public function canRenew($sub) {
            $product_id = $sub->getProductId();
            $product = Mage::getModel('catalog/product')->load($product_id);
            if($product->getSubscriptionDuration() != "150") {
                    return true;
            }
            return false;
        }
         * 
         */
        /**
         * This method return current customer id .
         * @return type
         */
        public function getCustomerId()
        {
            if(!$this->getData('customer_id'))
            {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                if($customerId = $customer->getId())
                {
                    $this->setData('customer_id', $customerId);
                }
            }

            return $this->getData('customer_id');
        }
}