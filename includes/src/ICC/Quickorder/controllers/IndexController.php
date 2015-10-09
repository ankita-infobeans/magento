<?php

class ICC_Quickorder_IndexController extends Mage_Core_Controller_Front_Action
{

    protected $_error;

    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Quick Order"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
            "label" => $this->__("Home Page"),
            "title" => $this->__("Home Page"),
            "link" => Mage::getBaseUrl()
        ));

        $breadcrumbs->addCrumb("quick order", array(
            "label" => $this->__("Quick Order"),
            "title" => $this->__("Quick Order")
        ));

        $this->renderLayout();
    }

    public function addtocartAction()
    {
        $p = Mage::getModel('catalog/product');
        $cart = Mage::getModel('checkout/cart');
        $products = Mage::helper('quickorder')->getQuickorderItems();
        $qant = 0;

        foreach ($products as $product) {
            $config = array(); //new Varien_Object();
            $config['qty'] = $product['qty'];
            $productId = $p->getIdBySku($product['sku']);

            if ($this->isProductAvailable($product['sku'], $product['qty'])) {
                $cart->addProduct($productId, $config);
                $qant++;
            } else {
                Mage::getSingleton('core/session')->addError($this->_error);
            }
        }

        $cart->save();
        Mage::helper('quickorder')->resetQuickorder();
        Mage::getSingleton('core/session')->addSuccess($this->__($qant . ' product(s) have been successfully added to cart'));
        $this->_redirect('*/*/');
    }

    public function addtowishlistAction()
    {
        if (!Mage::helper('customer')->isLoggedIn()) {
            $referer = Mage::getUrl('*/*/*', array('_current' => true, '_use_rewrite' => true));
            $referer = Mage::helper('core')->urlEncode($referer);
            Mage::app()->getResponse()->setRedirect(Mage::getUrl("customer/account/login", array('_secure' => true, 'referer' => $referer)));
            return;
        }

        $wishlist = Mage::helper('wishlist')->getWishlist();
        $p = Mage::getModel('catalog/product');
        $products = Mage::helper('quickorder')->getQuickorderItems();
        $quant = 0;

        foreach ($products as $product) {
            $productId = $p->getIdBySku($product['sku']);
            $config['qty'] = $product['qty'];
            if ($productId) {
                if ($this->isProductAvailable($product['sku'], $product['qty'])) {
                    $p->load($productId);
                    $wishlist->addNewItem($p, $config, false);
                    $quant++;
                } else {
                    Mage::getSingleton('core/session')->addError($this->_error);
                }
            }
        }

        Mage::helper('quickorder')->resetQuickorder();
        Mage::getSingleton('core/session')->addSuccess($this->__($quant . ' product(s) have been successfully added to your wishlist'));
        $this->_redirect('*/*/');
    }

    public function addAction()
    {
        $item = Mage::app()->getRequest()->getParams();

        if (is_array($item ['sku'])) {
            $count = 0;
            for ($i = 0; $i < count($item ['sku']); $i++) {
                if ($item ['sku'] [$i] != "") {
                    $sku = $item ['sku'] [$i];
                    $qty = $item ['qty'] [$i];
                    $this->addItem($sku, $qty);
                }
            }
        } else {
            $this->addItem($item ['sku'], $item ['qty']);

        }

        $this->_redirect('*/*/');
    }

    protected function addItem($sku, $qty)
    {
        if (!is_numeric($qty) || $qty == "") {
            Mage::getSingleton('core/session')->addError("Please enter quantity for " . $sku);
            $this->_redirect('*/*/');
            return;
        }

        if ($this->isProductAvailable($sku, $qty)) {
            Mage::helper('quickorder')->addItemToQuickorder($sku, $qty);
        } else {
            Mage::getSingleton('core/session')->addError($this->_error);
        }
    }

    public function removeAction()
    {
        $params = Mage::app()->getRequest()->getParams();
        $ndx = (int)$params['id'];
        Mage::helper('quickorder')->removeQuickorderItem($ndx);
        Mage::getSingleton('core/session')->addSuccess("This item has been removed from your quick order list");
        $this->_redirect('*/*/');
    }

    public function RemoveallAction()
    {
        Mage::helper('quickorder')->resetQuickorder();
        Mage::getSingleton('core/session')->addSuccess("All products removed from quick order list");
        $this->_redirect('*/*/');
    }

	protected function isProductAvailable($sku, $qty) {
		
		$product = Mage::getModel ( 'catalog/product' );
		
		$productId = $product->getIdBySku ( $sku );
		
		if ($productId) {
			$product->load ( $productId );
            Mage::log($product->getStatus(), null, 'yakoff-quick.log');
            if($product->getStatus() != 1) {
                $this->_error = "Sorry. The item ".$sku." cannot be added by Quick Order, either because it requires configuration or is not a valid item. ";
                return false;
            }
			
			//Mage::Log ( print_r ( $product->debug (), true ) );
			
			$type = $product->getTypeId ();
			
			if ($type == "simple" || $type == "downloadable") {
                    $productInventory = Mage::getModel ( 'cataloginventory/stock_item' )->loadByProduct ( $product );
                    if((int) $productInventory->getManageStock()){
                        $_qty = ( int ) $productInventory->getQty ();
                        Mage::log($_qty, null, 'yakoff-quick.log');
                        if ((int)$_qty < $qty) {
                            $this->_error = "We are sorry. We were unable to add this product to your cart because the available quantity is less than amount you would like to buy.";
                            return false;
                        } elseif (!(int)$_qty) {
                            $this->_error = "We are sorry. We were unable to add this product to your cart because it is temporarily out of stock.";
                            return false;
                        }
                    }
					return true;
			}else{
				$this->_error = "Sorry. The item ".$sku." cannot be added by Quick Order, either because it requires configuration or is not a valid item. ";
				return false;
			}
		}
		$this->_error = "Sorry no product with sku '$sku' found";
		
		return false;
	}
}
