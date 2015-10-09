<?php 
class Gorilla_GroupConfigurable_Block_Product_View_Type_Grouped_Configurable extends OrganicInternet_SimpleConfigurableProducts_Catalog_Block_Product_View_Type_Configurable
{
    //protected $_product = null;
    //protected $MAIN_IMAGE_H  =   170;
    //protected $MAIN_IMAGE_W  =   170;
    //protected $INCLUDE_GALLERY  =   false;

    public function getProduct()
    {
        return Mage::registry('grouped-product-child-product');
    }

    public function getJsonConfig() {
        // let's reset some variables first
        $this->setAllowProducts(null);

        // and now call geJsonConfig
        return parent::getJsonConfig();
    }

	/**
     * Get JSON encripted configuration array which can be used for JS dynamic
     * price calculation depending on product options
     * <<<Stolen from Mage_Catalog_Block_Product_View>>>
     *
     * @return string
     */
    public function getJsonConfigPrice()
    {
        $config = array();
        if (!$this->hasOptions()) {
            return Mage::helper('core')->jsonEncode($config);
        }

        $_request = Mage::getSingleton('tax/calculation')->getRateRequest(false, false, false);
        $_request->setProductClassId($this->getProduct()->getTaxClassId());
        $defaultTax = Mage::getSingleton('tax/calculation')->getRate($_request);

        $_request = Mage::getSingleton('tax/calculation')->getRateRequest();
        $_request->setProductClassId($this->getProduct()->getTaxClassId());
        $currentTax = Mage::getSingleton('tax/calculation')->getRate($_request);

        $_regularPrice = $this->getProduct()->getPrice();
        $_finalPrice = $this->getProduct()->getFinalPrice();
        $_priceInclTax = Mage::helper('tax')->getPrice($this->getProduct(), $_finalPrice, true);
        $_priceExclTax = Mage::helper('tax')->getPrice($this->getProduct(), $_finalPrice);

        $config = array(
            'productId'           => $this->getProduct()->getId(),
            'priceFormat'         => Mage::app()->getLocale()->getJsPriceFormat(),
            'includeTax'          => Mage::helper('tax')->priceIncludesTax() ? 'true' : 'false',
            'showIncludeTax'      => Mage::helper('tax')->displayPriceIncludingTax(),
            'showBothPrices'      => Mage::helper('tax')->displayBothPrices(),
            'productPrice'        => Mage::helper('core')->currency($_finalPrice, false, false),
            'productOldPrice'     => Mage::helper('core')->currency($_regularPrice, false, false),
            'skipCalculate'       => ($_priceExclTax != $_priceInclTax ? 0 : 1),
            'defaultTax'          => $defaultTax,
            'currentTax'          => $currentTax,
            'idSuffix'            => '_clone',
            'oldPlusDisposition'  => 0,
            'plusDisposition'     => 0,
            'oldMinusDisposition' => 0,
            'minusDisposition'    => 0,
        );

        $responseObject = new Varien_Object();
        Mage::dispatchEvent('catalog_product_view_config', array('response_object'=>$responseObject));
        if (is_array($responseObject->getAdditionalOptions())) {
            foreach ($responseObject->getAdditionalOptions() as $option=>$value) {
                $config[$option] = $value;
            }
        }

        return Mage::helper('core')->jsonEncode($config);
    }


//    public function getAssociatedProducts()
//    {
//        return $this->getProduct()->getTypeInstance(true)
//            ->getAssociatedProducts($this->getProduct());
//    }
}
