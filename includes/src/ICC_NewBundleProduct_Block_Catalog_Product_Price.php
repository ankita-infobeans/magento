<?php
class ICC_NewBundleProduct_Block_Catalog_Product_Price extends Mage_Bundle_Block_Catalog_Product_Price {
	
	public function getProduct() {
		if (Mage::registry ( 'grouped-product-child-product' )) {
			return Mage::registry ( 'grouped-product-child-product' );
		}
		return parent::getProduct ();
	}
	//old
        /*
	protected function getVolumeDiscountPopupHtml($tierPrices, $price) {
		return $this->getLayout ()->createBlock ( 'bundle/catalog_product_price' )->setTemplate ( 'bundle/catalog/product/bundle-volumepopup.phtml' )->setTierPrices ( $tierPrices )->setPrice ( $price )->toHtml ();
	}
        * 
        */
        //infobeans
        protected function getVolumeDiscountPopupHtml($tierPrices, $price, $_isMember=0, $checkDiscountTypeInformation = false)
        {
            return $this->getLayout()->createBlock('bundle/catalog_product_price')
                    ->setTemplate('bundle/catalog/product/bundle-volumepopup.phtml')
                    ->setTierPrices($tierPrices)
                    ->setPrice($price)
                    ->setMember($_isMember)
                    ->setCheckDiscountTypeInformation($checkDiscountTypeInformation)
                    ->toHtml();
        }
	
	public function applyPercentage($val, $percentage) {
		$res = $val - ($val * ($percentage / 100));
                $diff = $res - floor($res);
                if($diff > 0 && $diff < 0.02) {
                    $res = floor($res);
                }
                
                return $res;
	}
	
        //old code as per percent
        /*
	public function getBundleTierPrice() {
		$_product = $this->getProduct();
		$_priceModel = $_product->getPriceModel();
                $resource = Mage::getSingleton('core/resource');
                
                list ( $_minimalPriceTax, $_maximalPriceTax ) = $_priceModel->getTotalPrices ( $_product, null, null, false, Gorilla_Catalog_Helper_Data::ALL_GROUPS );
		list ( $_minimalPriceTaxMember, $_maximalPriceTaxMember ) = $_priceModel->getTotalPrices ( $_product, null, null, false, Gorilla_Catalog_Helper_Data::MEMBER_GROUP );
          
		$_tierPrices = $_priceModel->getTierPrices($_product, Gorilla_Catalog_Helper_Data::ALL_GROUPS );
		$_tierPricesMember = $_priceModel->getTierPrices ( $_product, Gorilla_Catalog_Helper_Data::MEMBER_GROUP );
		
		if (sizeof ( $_tierPrices ) > 0 && $_tierPrices [0] ['price_qty'] == 1) {
			$_minimalPriceTax = $_minimalPriceTax - ($_minimalPriceTax * ($_tierPrices [0] ['price'] / 100));
		}
		
		if (sizeof ( $_tierPricesMember ) > 0 && $_tierPricesMember [0] ['price_qty'] == 1) {
			$_minimalPriceTaxMember = $_minimalPriceTaxMember - ($_minimalPriceTaxMember * ($_tierPricesMember [0] ['price'] / 100));
		}
		
		if ($_minimalPriceTaxMember) {
			return $this->trimPrice ( $_minimalPriceTaxMember );
		}
		return $this->trimPrice ( $_minimalPriceTax );
	}
        * 
        */
	
	public function getBundleSavingPrice() {
		$price = $this->getProduct ()->getPrice();
		$tierPrice = $this->getBundleTierPrice();
		
		return $price > $tierPrice ? $price - $tierPrice : '';
	}
	
	// bandaid fix for Ticket#2014102310000152. Round off 1 cent rounding errors
	public function TrimPrice($price) {
		$diff = $price - floor ( $price );
		if ($diff > 0 && $diff < 0.02) {
			$price = floor ( $price );
		}
		return $price;
	}
        //infobeans    
        public function getBundleTierPrice() {
            $_product = $this->getProduct();
            $_priceModel = $_product->getPriceModel();
            $resource = Mage::getSingleton('core/resource');

            $productPrice = $_product->getPrice();
            //echo $productPrice.'<br>';exit;
            list($_minimalPriceTax, $_maximalPriceTax) = $_priceModel->getTotalPrices($_product, null, null, false, Gorilla_Catalog_Helper_Data::ALL_GROUPS, 'all');

            list($_minimalPriceTaxMember, $_maximalPriceTaxMember) = $_priceModel->getTotalPrices($_product, null, null, false, Gorilla_Catalog_Helper_Data::MEMBER_GROUP, 'member');

            $_tierPrices = $_priceModel->getTierPrices($_product, Gorilla_Catalog_Helper_Data::ALL_GROUPS );
            $_tierPricesMember = $_priceModel->getTierPrices ( $_product, Gorilla_Catalog_Helper_Data::MEMBER_GROUP );

            $checkDiscountTypeInformation = $_priceModel->checkDiscountTypeInformation($_product);

            if($checkDiscountTypeInformation)
            {
                if (sizeof($_tierPrices) > 0 && $_tierPrices[0]['price_qty'] == 1) 
                {
                        $_minimalPriceTax =  $_tierPrices[0]['price'];//$_minimalPriceTax - ($_minimalPriceTax * ($_tierPrices[0]['price'] / 100));
                        $_maximalPriceTax = $_maximalPriceTax;//$_maximalPriceTax - ($_minimalPriceTax * ($_tierPrices[0]['price'] / 100));
                }
                if (sizeof($_tierPricesMember) > 0 && $_tierPricesMember[0]['price_qty'] == 1) 
                {
                        $_minimalPriceTaxMember = $_tierPricesMember[0]['price'];
                        $_maximalPriceTaxMember = $_maximalPriceTaxMember; //$_tierPricesMember[0]['price'] + ($_maximalPriceTax - $_minimalPriceTax);
                }
            }
            else
            {
                if (sizeof($_tierPrices) > 0 && $_tierPrices[0]['price_qty'] == 1) 
                {
                        $_minimalPriceTax = $productPrice - ($productPrice * ($_tierPrices[0]['price'] / 100));
                        $_maximalPriceTax = $_maximalPriceTax;
                }
                if (sizeof($_tierPricesMember) > 0 && $_tierPricesMember[0]['price_qty'] == 1) 
                {
                        $_minimalPriceTaxMember = $productPrice - ($productPrice * ($_tierPricesMember[0]['price'] / 100));
                        $_maximalPriceTaxMember = $_maximalPriceTaxMember;
                }   
            }
            if($_minimalPriceTax > $_maximalPriceTax)
            {
                $_minimalPriceTax = $_maximalPriceTax;
            }
            if($_minimalPriceTaxMember > $_maximalPriceTaxMember)
            {
                $_minimalPriceTaxMember = $_maximalPriceTaxMember;
            }


            if ($_minimalPriceTaxMember) {
                    return $this->trimPrice ( $_minimalPriceTaxMember );
            }
            return $this->trimPrice ( $_minimalPriceTax );
        }        
        
}