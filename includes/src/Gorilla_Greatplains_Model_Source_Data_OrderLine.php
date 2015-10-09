<?php

class Gorilla_Greatplains_Model_Source_Data_OrderLine {
    /*
     * public $CustomerNumber ; public $OrderDate ; public $ItemNumber ; public
     * $Quantity ; public $ItemPrice ; public $TotalPrice ; public
     * $PromotionAmount ; public $TaxLineAmount ; public $TaxLines ; public
     * $ShipToAddress ; public $ShippingMethod ;
     */

    const XMLPATH_APPLY_TAX_AFTER = 'tax/calculation/apply_after_discount';
    const APPLY_TAX_AFTER_DISCOUNT = 1;
    const APPLY_TAX_BEFORE_DISCOUNT = 0;

    public $TaxLineAmount;
    public $ItemNumber;
    public $ItemPrice;
    public $OrderDate;
    public $PromotionAmount;
    public $Quantity;
    public $ShipToAddress;
    public $ShippingMethod;
    public $ExtendedPrice;
    public $TaxLines = null;

    function __construct($order, $item, $taxdata) {

        // get product
        $id = $item->getItemId();
        $sku = $item->getSku();
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->getSku());
        if (!$product) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            if (!$product) {
                Mage::helper("greatplains")->Log("no product");
                return $this;
            }
        }
        
        // ItemNumber
        $gpsku = $product->getGpSku();
        if (!empty($gpsku)) {
            $this->ItemNumber = $product->getGpSku();
        } else {
            Mage::helper("greatplains")->Log("product does not have GP sku.");
            return $this;
        }

        // Shipping address
        $shippingmethod = "";
        $this->ShipToAddress = "";
        if ($order->getShippingAddress() != null) {
            $shipping = new Gorilla_Greatplains_Model_Source_Data_ShippingAddress($order->getShippingAddress());
            $this->ShipToAddress = $shipping;
        }
        $this->ShippingMethod = Mage::helper('greatplains')->getShippingMethod($order->getShippingMethod());;

	/* jinal khakharia changed in 5 august 2015 for IMS-67*/
        $freightAmount = number_format($order->getShippingAmount(),2,'.','');

        if($freightAmount == 0)
        {
            $this->ShippingMethod = 'FEDEX NOCHG';
        }
        else
        {
            $this->ShippingMethod = Mage::helper('greatplains')->getShippingMethod($order->getShippingMethod());
        }
        /*end*/

        // Date
        $this->OrderDate = date("Y-m-d", Mage::getModel('core/date')->timestamp(strtotime($item->getCreatedAt()))) . "T00:00:00";

        // Product price calculation
        $this->Quantity     = $item->getQtyOrdered();
        //Level 4 fix: 2014060210000931
        $this->ItemPrice    = number_format($item->getBasePrice(),2,'.','');
        $promoAmount        = $item->getDiscountAmount() / $item->getQtyOrdered();
        $this->PromotionAmount  = $this->roundDown($promoAmount, 2);
        $this->ExtendedPrice    = ($this->ItemPrice - $this->PromotionAmount) * $this->Quantity;
        //Level 4 fix: 2014060210000931
        $this->ExtendedPrice = number_format($this->ExtendedPrice,2,'.','');

        // Line Item taxes
        $this->TaxLineAmount = number_format($item->getTaxAmount(),2,'.','');
        if (empty($taxdata)) {
            $this->TaxLines[] = new Gorilla_Greatplains_Model_Source_Data_OrderLineTaxes();
        } else {

	   /* This code is changed (added the floatval) for resolving order not sync with GP(#100988767) by Jinal on 21st June 2015 */
            $itemTax = floatval($item->getTaxAmount());
            $baseprice = floatval($item->getRowTotal());
            $taxableprice = floatval($this->getTaxablePrice($baseprice, $item->getDiscountAmount()));
	    /*end*/

            $totalTax = 0;
            foreach ($taxdata as $line) {
                $t = new Gorilla_Greatplains_Model_Source_Data_OrderLineTaxes($line, $taxableprice,$itemTax);
                $this->TaxLines[] = $t;
                $totalTax += $t->TaxAmount;
            }

            //$itemtax = round($item, 2);

            if ($totalTax < $itemTax) {
                $difference = $itemTax - $totalTax;
                $this->TaxLines[0]->TaxAmount += $difference;
		/* This code is added for resolving order not sync with GP(#100988767) by Jinal on 21st June 2015 */
		$this->TaxLines[0]->TaxAmount = round($this->TaxLines[0]->TaxAmount, 3);
		/*end*/
            }
            if ($totalTax > $itemTax) {
                $difference = $totalTax - $itemTax;
                $this->TaxLines[0]->TaxAmount -= $difference;
		/* This code is added for resolving order not sync with GP(#100988767) by Jinal on 21st June 2015 */
		$this->TaxLines[0]->TaxAmount = round($this->TaxLines[0]->TaxAmount, 3);
		/*end*/
            }

        }

        return $this;
    }
    
    /**
     * Rounds a number down, retaining the specified number of decimal digits.
     * Might be a better (native) way to do this, but no time now :)
     *
     * @param float $number - the number to round down.
     * @param int $decimals - the decimal places to retain.
     * @return int
     */
    public function roundDown($number, $decimals=2) 
    {
        $multiplier = pow(10, $decimals);
        $number = $number*$multiplier;
        $number = floor($number);
        $number = $number/$multiplier;
        return $number;
    }
    
    protected function getTaxablePrice($basePrice, $discountAmount)
    {
        $taxableprice = $basePrice - $discountAmount;
        switch (Mage::getStoreConfig(self::XMLPATH_APPLY_TAX_AFTER)) {
            case self::APPLY_TAX_BEFORE_DISCOUNT :
                $taxableprice = $basePrice;
                break;
            case self::APPLY_TAX_AFTER_DISCOUNT :
                $taxableprice = $basePrice - $discountAmount;
                break;
        }
        return $taxableprice;
    }

}

?>
