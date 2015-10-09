<?php

class Gorilla_GroupConfigurable_AddController extends Mage_Core_Controller_Front_Action
{
    protected $_storeId = null;

    protected function getStoreId()
    {
        if (is_null($this->_storeId)) {
            $this->_storeId = Mage::app()->getStore()->getId();
        }
        return $this->_storeId;
    }

    /**
     * Adds an array of products to the cart.
     * Expects item ids and quantities
     */
    public function addgroupAction()
    {
        $messages   = array();
        $addedItems = array();
        $notSaleable = array();
        $notQty = array();

        $cart       = Mage::getSingleton('checkout/cart');
        $params      = $this->getRequest()->getParam('gpchild', null);
        $bundle = $this->getRequest()->getParam('bundle_option');
        if (!empty($params)) {
            foreach ($params as $param) {
                if (is_numeric($param['qty'])) {
                    if ($param['qty'] > 0) {
                        try {
                            $product = Mage::getModel('catalog/product')
                                                    ->setStoreId($this->getStoreId())
                                                    ->load($param['product']);

                            if ($product->getTypeId() == 'bundle'){
                                
                                $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
                                $attributeSetModel->load($product->getAttributeSetId());
                                $attributeSetName  = $attributeSetModel->getAttributeSetName();
                                $premium_flag = FALSE;
                                if($attributeSetName == 'PremiumACCESS Bundle'){
                                    $premium_flag = TRUE;
                                }
                                
                                
                                $redirectParams = array();
                                $selectionsCollection = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);
                                foreach($selectionsCollection->getItems() as $selection){
                                    $redirectParams['bundle_option'][$selection->getOptionId()] = $selection->getSelectionId();
                                    $redirectParams['bundle_option_qty'][$selection->getOptionId()] = $selection->getSelectionQty();
                                }
                                $redirectParams['product'] = $param['product'];
                                $redirectParams['qty'] = $param['qty'];
                                $redirectParams['related_product'] = null;
                                $param = $redirectParams;
                                /* This is for PRemium Access Bundle */
                                if($premium_flag) {
                                    $param['bundle_option'] = $bundle[$param['product']];
                                }

                            }

                            $success = $this->addToCart($cart, $product, $param);
                            if ($success) {
                                $addedItems[]  = $product->getName();
                            } else {
                                $notSaleable[] = $product->getName();
                            }
                        } catch (Mage_Core_Exception $e) {
                        	$m = trim($e->getMessage(), '.');
				$n = $product->getName();
                            $messages[] = Mage::helper('wishlist')->__("$m for \"$n\".");
                        } catch (Exception $e) {
                            Mage::logException($e);
                            $messages[] = Mage::helper('wishlist')->__('Cannot add the item to shopping cart.');
                        }
                    }
                } else {
                	
            		if(isset($param['product'])){
                     $product = Mage::getModel('catalog/product')
                                   ->setStoreId($this->getStoreId())
                                   ->load($param['product']);
                    $notQty[] = $product->getName();
            		}
                }
            } 
        } else {
            
        }

        if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
            $redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
        } else if ($this->_getRefererUrl()) {
            $redirectUrl = $this->_getRefererUrl();
        } else {
            $redirectUrl = '';
        }

        if ($notSaleable) {
            $messages[] = Mage::helper('core')->__('Unable to add the following product(s) to shopping cart: %s.', join(', ', $notSaleable));
        }

        if ($notQty) {
            $messages[] = Mage::helper('core')->__('Please enter a valid number in field Qty. Unable to add the following product(s) to shopping cart: %s.', join(', ', $notQty));
        }
        
        if ($messages) {
            foreach ($messages as $message) {
                Mage::getSingleton('checkout/session')->addError($message);
            }
        }

        if ($addedItems) {
            Mage::getSingleton('checkout/session')->addSuccess(
                Mage::helper('core')->__('%d product(s) have been added to shopping cart: %s.', count($addedItems), join(', ', $addedItems))
            );
        }
        
        // save cart and collect totals
        $cart->save()->getQuote()->collectTotals();

        $this->_redirectUrl($redirectUrl);
    }

    /**
     * Add an item to shopping cart
     *
     * Return true if product was successful added or exception with code
     * Return false for disabled //or unvisible products
     *
     */
    protected function addToCart(Mage_Checkout_Model_Cart $cart, $product, $param)
    {
        if ($product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            return false;
        }

//        if (!$product->isSalable()) {
//            throw new Mage_Core_Exception(null, self::EXCEPTION_CODE_NOT_SALABLE);
//        }
//
//        if ($product->getTypeInstance(true)->hasRequiredOptions($product)) {
//            throw new Mage_Core_Exception(null, self::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS);
//        }

        $eventArgs = array(
            'product' => $product,
            'qty' => $param['qty'],
            'additional_ids' => array(),
            'request' => $this->getRequest(),
            'response' => $this->getResponse(),
        );
        Mage::dispatchEvent('checkout_cart_before_add', $eventArgs);
        $cart->addProduct($product, $param);
        Mage::dispatchEvent('checkout_cart_after_add', $eventArgs);
//        $cart->save();
//        Mage::dispatchEvent('checkout_cart_add_product', array('product'=>$product));


        if (!$product->isVisibleInSiteVisibility()) {
            $cart->getQuote()->getItemByProduct($product)->setStoreId($this->getStoreId());
        }

        return true;
    }

}