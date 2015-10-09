<?php

class ICC_Pricemodification_Model_Observer extends Enterprise_PageCache_Model_Container_Abstract {
    const LIMIT = 40;

    const GUEST = 0;
    const GENERAL = 1;
    const MEMBER = 2;
    const RESELLER = 3;
    const MICHIGAN_ID = 4;
    const FLORIDA_ID = 5;
    const NORTH_CAROLINA_ID = 6;
    const HIDDEN_RESELLER = 7;
    const NSE_ID = 8;

    const MICHIGAN = "MI";
    const FLORIDA = "FL";
    const NORTH_CAROLINA = "NC";
    const NSE = "NSE";
    const MG = "MEM";


    /**
     * Get cache identifier
     */

    protected function _getIdentifier() {
       // Mage::Log("Get identifier");
        return $this -> _getCookieValue(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER, '');
    }

    protected function _getCacheId() {
         //Mage::Log("_getCacheId");

        /**
         * Recreating placeholder definition to create definition hash.
         * This must match the following format:
         *
         * BUSINESSMODEL_COVERAGE container="Mmm_BusinessModel_Model_Container_Coverage" block="Mmm_BusinessModel_Block_Coverage" cache_id="b7df6c78d78b44329430edccc88af9caab4134fc" template="businessmodel/coverage.phtml"
         */
        $cacheId = $this -> _placeholder -> getAttribute('cache_id');
        $container = $this -> _placeholder -> getAttribute('container');
        $block = $this -> _placeholder -> getAttribute('block');
        $template = $this -> _placeholder -> getAttribute('template');

        if ($cacheId) {

            $id = 'ICC_PRICEMODIFICATION_' . md5('ICC_PRICEMODIFICATION_ container="' . $container . '" ' . 'block="' . $block . '" cache_id="' . $cacheId . '"' . 'template="' . $template);
            return $id;
            //Mage::LoG($id);
        }
        return false;
    }

    /**
     * Save data to cache storage
     *
     * @param string $data
     * @param string $id
     * @param array $tags
     */
    protected function _renderBlock() {
       // Mage:Log("rendering block");
        $blockClass = $this -> _placeholder -> getAttribute('block');
        $template = $this -> _placeholder -> getAttribute('template');

        $block = new $blockClass;
        $block -> setTemplate($template);
        return $block -> toHtml();
    }

    protected function _saveCache($data, $id, $tags = array(), $lifetime = null) {
       // Mage::Log("Saving cache");
        return false;
    }

    private function getLimit() {
        return self::LIMIT;
    }

    public function runEveryTime() {

        //Mage::Log("Running every time");
        $response = false;

        $response = $this -> checkForReferrer();

        $response = $this -> checkForReseller();

        if ($response) {
            $this -> setCartProductsCustomerGroup();
        }

    }

    public function checkForReferrer() {

        $session = Mage::getSingleton('customer/session');
        $customer = $session -> getCustomer();
        $data = $this -> getCustomerGroup();
        //$session -> getData("customer_temp_group");
        $ref = $ref = Mage::app()->getFrontController()->getRequest()->getParam('ref', false);

        if ($data != "") {

            if ($ref){
                $refsArr = array(
                    self::FLORIDA       => self::FLORIDA_ID,
                    self::NORTH_CAROLINA=> self::NORTH_CAROLINA_ID,
                    self::MICHIGAN      => self::MICHIGAN_ID,
                    self::NSE           => self::NSE_ID,
                    self::MG            => self::MEMBER
                );

                if ($data == $refsArr[$ref]){
                    return true;
                }else{
                    // we need to update customer_temp_group if a user has come up with a new ref, request in ticket #2013101010000268
                    $this->resetCustomerGroup();
                }
            //return true;
            }else{
                return true;
            }
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referrer = $_SERVER['HTTP_REFERER'];
        } else {
            $referrer = '';
        }
        //Mage::Log("Referrer is : " . $referrer);

        $refArray = explode('/', $referrer);

        if (count($refArray) < 3) {
            return;
        }

        $refurl = $refArray[2];

        $checkurls = Mage::getStoreConfig('catalog/pricemodification/sites');

        $urlarray = explode(",", $checkurls);

        $found = false;
        foreach ($urlarray as $url) {
            $re = stripos($refurl, $url);
            if ($re === false) {
            } else {
                $found = true;
            }
        }
        if (!$found) {
            return false;
        }

        $session = Mage::getSingleton('customer/session');
        Mage::getSingleton('core/session', array('name' => 'frontend'));

        $session = Mage::getSingleton('customer/session');
        $customer = $session -> getCustomer();

        if (!$session -> isLoggedIn() || $customer -> getGroupId() == self::GENERAL || $customer -> getGroupId() == self::GUEST) {

            //$ref = Mage::app() -> getFrontController() -> getRequest() -> getParam('ref', false);

           // Mage::Log("ref is $ref");

            switch ($ref) {
                case self::FLORIDA :
                    $this -> changeCustomerGroup(self::FLORIDA_ID);
                    break;
                case self::NORTH_CAROLINA :
                    $this -> changeCustomerGroup(self::NORTH_CAROLINA_ID);
                    break;
                case self::MICHIGAN :
                    $this -> changeCustomerGroup(self::MICHIGAN_ID);
                    break;
                case self::NSE :
                    $this -> changeCustomerGroup(self::NSE_ID);
                    break;
                case self::MG :
                    $this -> changeCustomerGroup(self::MEMBER);
                    break;
            }
            return true;
        }

        if ($this -> getCustomerGroup() != "") {
            return true;
        }
        return false;
    }

    public function checkForReseller() {

        Mage::getSingleton('core/session', array('name' => 'frontend'));

        $session = Mage::getSingleton('customer/session');
        $groupid = $session -> getCustomerGroupId();

        if ($groupid == self::RESELLER) {
            $cart = Mage::helper('checkout/cart') -> getCart();
            $totalQty = $cart -> getItemsQty();

            if ($totalQty >= $this -> getLimit()) {
                $this -> changeCustomerGroup(self::HIDDEN_RESELLER);
                return true;
            }
            $this -> changeCustomerGroup(self::RESELLER);
        }

        if ($groupid == self::HIDDEN_RESELLER) {
            $cart = Mage::helper('checkout/cart') -> getCart();
            $totalQty = $cart -> getItemsQty();
            if ($totalQty <= $this -> getLimit()) {
                $this -> changeCustomerGroup(self::RESELLER);
                return true;
            }
            $this -> changeCustomerGroup(self::HIDDEN_RESELLER);
            return true;
        }
        return false;
    }

    public function setCartProductsCustomerGroup() {

        $tmpgroup = $this -> getCustomerGroup();

        $quote = Mage::getSingleton('checkout/session') -> getQuote();
        $quote -> setCustomerGroupId($tmpgroup);
        $items = $quote -> getAllItems();

        foreach ($items as $item) {

            $item -> setCustomerGroupId($tmpgroup);
        }
    }

    private function getCustomerGroup() {
        $session = Mage::getSingleton('customer/session');
        return $session -> getCustomerTempGroup();
        // return $session -> getData("customer_temp_group");

    }

    private function changeCustomerGroup($id) {
       // Mage::Log("Changing customer group to " . $id);
        Mage::getSingleton('customer/session') -> setData("customer_temp_group", $id);
    }

    private function resetCustomerGroup() {

        $session = Mage::getSingleton("customer/session");

        $session -> setData("customer_temp_group", "");

    }

}
