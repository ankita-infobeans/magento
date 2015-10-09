<?php

class ICC_Avectra_Helper_Data extends Mage_Core_Helper_Abstract
{
    
    public function syncBillingAvectra($avectra_key, $billing)
    {
        $customer = $this->getUserByAvectraKey($avectra_key);
        
        $billmemberCxa = $customer->getBillmemberAddrCxa();
        if(empty($billmemberCxa)) {
            // we should never hit this in production
            $account = Mage::getModel('icc_avectra/account');
            $account->updateUser($customer->getAvectraKey());
            $billmemberCxa = $customer->getBillmemberAddrCxa();
        }
        $billmemberAddresses = Mage::getModel('customer/address')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('avectra_key', $billmemberCxa)
                ;
        if( ! $billmemberAddresses->count() ) return $billing; // no updates without an address
        $billmemberAddress = $billmemberAddresses->getFirstItem();
        // test for some content
        if( ! $billmemberAddress->getCity() ) return $billing; // don't update with empty content

//        if( $billmemberAddress->validate() ) {
//            $billing->setFirstname( $billmemberAddress->getFirstname() );
//            $billing->setLastname( $billmemberAddress->getLastname() );
            $billing->setCity( $billmemberAddress->getCity() );
            $billing->setRegion( $billmemberAddress->getRegion() );
            $billing->setRegionId( $billmemberAddress->getRegionId() );
            $billing->setPostcode( $billmemberAddress->getPostcode() );
            $billing->setCountryId( $billmemberAddress->getCountryId() );
            $billing->setCountry( $billmemberAddress->getCountry() );
            $billing->setStreet( $billmemberAddress->getStreet() );
            $billing->setCompany($billmemberAddress->getCompany());
            
            $billing->save();
//        }
        

        return $billing;
    }
    
    public function getUserByAvectraKey($avectra_key)
    {
        $customers = Mage::getModel('customer/customer')
                        ->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('avectra_key', $avectra_key );
        
        if($customers->count() === 1) {
            return  $customers->getFirstItem();
        } else {
            return false;
        }
    }

    public function getLoginUrl()
    {
        // https://av.iccsafe.org/eweb/startpage.aspx?site=icc-cart&URL_success={{URL}}
        $returnUrl = urlencode(Mage::getBaseUrl());
        $url = Mage::getStoreConfig('customer/avectra/login_url');
        return str_replace('{URL}', $returnUrl, $url);
        //return "https://av.iccsafe.org/eweb/DynamicPage.aspx?WebCode=LoginRequired&Site=icc&URL_success=".urlencode(Mage::getBaseUrl())."%3find_token%3d%7btoken%7d";
        //return "https://av.iccsafe.org/eweb/startpage.aspx?site=icc-cart&URL_success=".urlencode(Mage::getBaseUrl())."%3find_token%3d%7btoken%7d";
    }

    public function getCheckoutLoginUrl()
    {
        $returnUrl = urlencode(Mage::getUrl('checkout/onepage', array('_secure' => true, '_nosid' => true)));
        $url = Mage::getStoreConfig('customer/avectra/login_url');
        return str_replace('{URL}', $returnUrl, $url);
        //return "https://av.iccsafe.org/eweb/DynamicPage.aspx?WebCode=LoginRequired&Site=icc&URL_success=".urlencode(Mage::getBaseUrl())."checkout/onepage%3find_token%3d%7btoken%7d";
        //return "https://av.iccsafe.org/eweb/startpage.aspx?site=icc-cart&URL_success=".urlencode(Mage::getBaseUrl())."checkout/onepage%3find_token%3d%7btoken%7d";
    }

    public function getLogoutLink()
    {
       // $returnUrl = urlencode(Mage::getBaseUrl());
	$returnUrl = urlencode( Mage::getUrl('customer/account/logout/'));
        $url = Mage::getStoreConfig('customer/avectra/logout_url');
        return str_replace('{URL}', $returnUrl, $url);
        //return "https://av.iccsafe.org/eweb/Logout.aspx?&RedirectURL=".urlencode(Mage::getBaseUrl());
    }

}
