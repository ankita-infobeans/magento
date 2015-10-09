<?php
class ICC_Premiumaccess_Block_Customer_Eproducts_Premiumaceess extends Mage_Core_Block_Template {

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}
        public function __construct()
        {
            // we have to load only unique orders by current customer
            
            Mage::getSingleton('customer/session')->isLoggedIn();
            $loginCustomerId = Mage::getSingleton('customer/session')->getCustomerId();
            
            
            $products = Mage::getModel('sales/order_item')->getCollection();
            $sales_order_table= Mage::getSingleton('core/resource')->getTableName('sales_flat_order'); 
            $products->getSelect()->joinLeft(array('sales_order'=>$sales_order_table),'`main_table`.`order_id` = `sales_order`.`entity_id`');
            $products->addFieldToFilter('sales_order.customer_id',array('eq'=>$loginCustomerId));
            $products->addFieldToFilter('sales_order.parent_order_id',array('null'=>true));
	    $products->addFieldToFilter('sales_order.premium_access',array('eq'=>1));
	    $products->addFieldToFilter('main_table.premium_access',array('eq'=>1));
	    $products->addFieldToFilter('main_table.qty_invoiced',array('gt'=>0));
	    
	    
	    
	    $products->setOrder ( 'sales_order.increment_id', 'desc' );
	    //echo $products->getSelect();
            $this->setPremiumProducts($products);
            
            $parentOrder = Mage::getModel('sales/order')->getCollection();
            $parentOrder->addFieldToFilter('customer_id',array('eq'=>$loginCustomerId));
            $parentOrder->addFieldToFilter('parent_order_id',array('null'=>true));
            $parentOrder->addFieldToFilter('premium_access',array('eq'=>1));
            //echo $parentOrder->getSelect();
                    
            //print_r($parentOrder->getAllIds());
            
//die;            
            $childproducts = Mage::getModel('sales/order_item')->getCollection();
            $sales_order_tables= Mage::getSingleton('core/resource')->getTableName('sales_flat_order'); 
            $childproducts->getSelect()->joinLeft(array('sales_order'=>$sales_order_tables),'`main_table`.`order_id` = `sales_order`.`entity_id`');
            $childproducts->addFieldToFilter('sales_order.customer_id',array('eq'=>$loginCustomerId));
            $childproducts->addFieldToFilter('sales_order.parent_order_id',array('notnull'=>true));
	    $childproducts->addFieldToFilter('sales_order.premium_access',array('eq'=>1));
	    $childproducts->addFieldToFilter('main_table.premium_access',array('eq'=>1));
            $childproducts->addFieldToFilter('sales_order.status',array('neq'=>'canceled'));
            if(!empty($parentOrder->getAllIds())){
                $childproducts->addFieldToFilter('sales_order.parent_order_id',array('nin'=>$parentOrder->getAllIds()));
            }
            $childproducts->setOrder ( 'sales_order.increment_id', 'desc' );
            $this->setGiftPremiumProducts($childproducts);            
        }

        protected function _prepareLayout()
        {
            $pager = $this->getLayout()->createBlock('page/html_pager', 'premiumaccess.customer.products.pager')
                ->setCollection($this->getPremiumProducts());
            $this->setChild('premiumaccess_pager', $pager);
            $this->getPremiumProducts()->load();
            
            $giftpager = $this->getLayout()->createBlock('page/html_pager', 'gif_premiumaccess.customer.products.pager')
                ->setCollection($this->getGiftPremiumProducts());
            $this->setChild('gift_premiumaccess_pager', $giftpager);
            $this->getGiftPremiumProducts()->load();
            
            
            return $this;
        }

        public function getPagerHtml()
        {
            return $this->getChildHtml('premiumaccess_pager');
        }
        
         public function getGiftPagerHtml()
        {
            return $this->getChildHtml('gift_premiumaccess_pager');
        }
        
                
        public function getChildOrderCount($order_id, $order_item_id)
        {
	      //$order_id=232652;
        
	      $products = Mage::getModel('sales/order')->getCollection();
	      $products->addFieldToFilter('parent_order_id',array('eq'=>$order_id));
	      $products->addFieldToFilter('premium_access',array('eq'=>1));
	      $products->addFieldToFilter('parent_order_item_id',array('eq'=>$order_item_id));
              $products->addFieldToFilter('status',array('neq'=>'canceled'));
              //echo $products->getSelect();
	      return count($products);
        }
        
        public function getSelfAssingCount($order_id, $order_item_id)
        {
            $products = Mage::getModel('sales/order')->getCollection();
            $products->addFieldToFilter('parent_order_id',array('eq'=>$order_id));
            $products->addFieldToFilter('parent_order_item_id',array('eq'=>$order_item_id));
            $products->addFieldToFilter('premium_access',array('eq'=>1));
            $products->addFieldToFilter('future_email',array('null'=>true));
            $products->addFieldToFilter('customer_id',array('eq'=>$this->getCustomer()->getId()));
            $products->addFieldToFilter('status',array('neq'=>'canceled'));
            
            //echo $products->getSelect(); die;
            return count($products);
        }
        
        public function formatDate1($sqlDate) {
		return date('M j, Y', strtotime($sqlDate));
	}
        public function isExpired($expirationDate,$sub=null) {
		$expTime = strtotime($expirationDate);// + 86400;
		return (time() > $expTime );
	}
        
        public function isExpiring($expriationDate) {
        
		$expTime = strtotime($expriationDate);
		return ($expTime < time());
	}
        
        public function getExpriationFromDurationValue($item){
                
                $obj = Mage::getModel('catalog/product');
                $_product = $obj->load($item->getProductId());
                $v=$_product->getAttributeText("subscription_duration");
                return strtotime(date("Y-m-d",strtotime($item->getCreatedAt()) ) . " +" . $v);
        }
}