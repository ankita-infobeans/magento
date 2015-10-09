<?php
class ICC_Volumelicense_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Titlename"));
	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("titlename", array(
                "label" => $this->__("Titlename"),
                "title" => $this->__("Titlename")
		   ));

      $this->renderLayout(); 
	  
    }
    public function testAction(){

        $collection = Mage::getModel('volumelicense/registry')->getCollection();
                $msa_eventType = Mage::getSingleton('core/resource')->getTableName('icc_volumelicense');
                $collection->getSelect()->joinRight(array('rg_access'=>$msa_eventType),'`main_table`.`volumelicense_id` = `rg_access`.`id`',
                array('rg_access.product_name', 'rg_access.sku', 'rg_access.max_register', 'rg_access.notes', 'rg_access.status')); 
                $customercollection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email'); 
                $collection->getSelect()->join(
                array('epa' => $customercollection->getSelect()), 'main_table.assign_customer_id=epa.entity_id', array('entity_id','name','email') 
				);
                $collection->addFieldToFilter('main_table.volumelicense_id',array('eq'=>8));
                $collection->addFieldToFilter('main_table.assign_status',array('neq'=>3));
			echo $collection->getSelect();
    }
    
     public function test1Action(){
     
       // $orderitemid = Mage::getModel('volumelicense/volumelicense')->load(1);
      //    echo $orderitemid->getOrderItemId();
         $Collection = Mage::getModel('volumelicense/links')->getCollection();
         $Collection->addFieldToSelect('id');
         
         
         
         $Collection ->getSelect() 
                // Serials (eCodes)
                ->joinInner(
                    array('srg' => 'icc_volumelicense_registry'),
                    'main_table.registry_id = srg.id',
                    array('assign_customer_id')
                )           
                ->joinInner(
                    array('dlpi' => 'downloadable_link_purchased_item'),
                    'main_table.pur_item_id = dlpi.item_id',
                    array('number_of_downloads_bought', 'number_of_downloads_used', 'link_hash', 'link_id', 'status', 'link_title', 'product_id')
                )
                 ->joinLeft(
                    array('ecodes' => 'ecodes_downloadable'),
                    'dlpi.order_item_id = ecodes.order_item_id',
                    array('serial')
                )
                  
                // Sales Information
                ->joinLeft(
                        array('order_item' => 'sales_flat_order_item'),
                        '`dlpi`.`order_item_id` = `order_item`.`item_id`',
                        array('created_at', 'name')
                        )
                ->joinLeft(
                        array('order' => 'sales_flat_order'),
                        '`order_item`.`order_id` = `order`.`entity_id`',
                        array('increment_id')
                        )
                // can't use orWhere() 
                // since we need:
                //  "... AND (enabled=1 OR enabled is null) AND ..."
                // but orWhere results in:
                //  "... AND (enabled=1) OR (enabled is null) AND ..."
                ->where('ecodes.enabled = 1 OR ecodes.enabled is null');

            $Collection->addFieldToFilter('dlpi.status',
                    array(
                        'nin' => array(
                            Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING_PAYMENT,
                            Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PAYMENT_REVIEW,
                            ICC_Ecodes_Helper_Downloadable::LINK_STATUS_REFUNDED
                        )
                    )
                );
            
        $customer_id = 111;
         $Collection->addFieldToFilter('srg.assign_customer_id',array('eq' => $customer_id));
            
            
         echo $Collection ->getSelect();
         exit;
         
        $collection = Mage::getModel('downloadable/link_purchased_item')->getCollection();
        $collection->getSelect()->limit(20);
        print_r($collection->getData());exit;
    
    
               
     }
     
     public function palceOrderAction(){
	    // get logged in cusomer id
	$customerAccountNo = 162864;//Mage::getModel('customer/session')->getCustomer()->getId();
	// load customer object
	$customerObj = Mage::getModel('customer/customer')->load($customerAccountNo);
	// assign this customer to quote object, before any type of magento order, first create quote.
	$quoteObj = Mage::getModel('sales/quote')->assignCustomer($customerObj);
	$quoteObj = $quoteObj->setStoreId(Mage::app()->getStore()->getId());
	
	// product id
	$productId = 42277;
	$productModel = Mage::getModel('catalog/product');
	$productObj = $productModel->load($productId);
	
	$quotePaymentObj = $quoteObj->getPayment();
	    $quotePaymentObj->setMethod('volume_license');
	    $quoteObj->setPayment($quotePaymentObj);
	    
	
	
	
	// for simple product
	if ($productObj->getTypeId() == 'simple') {
	    $quoteObj->addProduct($productObj , 1);
	    // for downloadable product
	} else if ($productObj->getTypeId() == 'downloadable') {
	    $params = array();
	    $links = Mage::getModel('downloadable/product_type')->getLinks( $productObj );
	    $linkId = 0;
	    foreach ($links as $link) {
		$linkId = $link->getId();
	    }
	    $params['product'] = $productId;
	    $params['qty'] = $qty;
	    $params['links'] = array($linkId);
	    $request = new Varien_Object();
	    $request->setData($params);
	    $quoteObj->addProduct($productObj , $request);
	}
	// sample billing address
	$billingAddress = array
	(
	    'email' => NULL,
	    'firstname' => "FirstName",
	    'lastname' => "LastName",
	    'telephone' => "102920",
	    'street' => "517 4th Avenue",
	    'country_id' => "US",
	    'city' => 'San Diego',
	    'postcode' => "90001",
	    'region_id' => "12",
	    'region' => 'California',
	    'company' => "Company",
	    'fax' => "123445",
	    'customer_address_id' => NULL,
	);
	
	$quoteBillingAddress = Mage::getModel('sales/quote_address');
	$quoteBillingAddress->setData($billingAddress);
	$quoteObj->setBillingAddress($quoteBillingAddress);
	
	//if product is not virtual
	if (!$quoteObj->getIsVirtual()) {
	    $shippingAddress = $billingAddress;
	    $quoteShippingAddress = Mage::getModel('sales/quote_address');
	    $quoteShippingAddress->setData($shippingAddress);
	    $quoteObj->setShippingAddress($quoteShippingAddress);
	    // fixed shipping method
	    $quoteObj->getShippingAddress()->setShippingMethod('flatrate_flatrate');
	    $quoteObj->getShippingAddress()->setCollectShippingRates(true);
	    $quoteObj->getShippingAddress()->collectShippingRates();
	}
	
	$quoteObj->collectTotals();
	$quoteObj->save();
	
	
	$transaction = Mage::getModel('core/resource_transaction');
	if ($quoteObj->getCustomerId()) {
	    $transaction->addObject($quoteObj->getCustomer());
	}
	$transaction->addObject($quoteObj);
	$quoteObj->reserveOrderId();
	
	
	
	$convertQuoteObj = Mage::getSingleton('sales/convert_quote');
	if ($quoteObj->getIsVirtual()) {
	    $orderObj = $convertQuoteObj->addressToOrder($quoteObj->getBillingAddress());
	} else {
	    $orderObj = $convertQuoteObj->addressToOrder($quoteObj->getShippingAddress());
	}
	
	$orderPaymentObj = $convertQuoteObj->paymentToOrderPayment($quotePaymentObj);
	
	$orderObj->setBillingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getBillingAddress()));
	$orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quoteObj->getPayment()));
	if (!$quoteObj->getIsVirtual()) {
	    $orderObj->setShippingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getShippingAddress()));
	}
	
	// set payment options
	
	    
	    $convertQuoteObj = Mage::getSingleton('sales/convert_quote');
	    if ($quoteObj->getIsVirtual()) {
		$orderObj = $convertQuoteObj->addressToOrder($quoteObj->getBillingAddress());
	    } else {
		$orderObj = $convertQuoteObj->addressToOrder($quoteObj->getShippingAddress());
	    }
	    
	   // $orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quoteObj->getPayment()));
	
	$items=$quoteObj->getAllItems();
	
	foreach ($items as $item) {
	//@var $item Mage_Sales_Model_Quote_Item
	    $orderItem = $convertQuoteObj->itemToOrderItem($item);
	    if ($item->getParentItem()) {
		$orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
	    }
	    $orderObj->addItem($orderItem);
	}
	
	$orderObj->setCanShipPartiallyItem(false);
	
	$totalDue = $orderObj->getTotalDue();
	
	$transaction->addObject($orderObj);
	$transaction->addCommitCallback(array($orderObj, 'place'));
	$transaction->addCommitCallback(array($orderObj, 'save'));
	
	try {
	    $transaction->save();
	} catch (Exception $e){
	    Mage::throwException('Order Cancelled Bad Response from Credit Authorization.');
	}
	
	$orderObj->sendNewOrderEmail();
	
	Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order'=>$orderObj, 'quote'=>$quoteObj));
	
	$quoteObj->setIsActive(0);
	$quoteObj->save();
	echo "nikhil"; die;
      }
      
}