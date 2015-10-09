<?php
/**
 * @category   ICC
 * @package    ICC_Volumelicense
 */
class ICC_Volumelicense_Model_Checkout_Type_Onepage extends ICC_Checkout_Model_Type_Onepage
{
    public function saveVolumelicense($data)
    {
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }

        $j = 1;
		$registerCustomer = $this->getQuote()->getBillingAddress();
		$duplicateEmail = Array();
        foreach ($data['product_id'] as $p_id) {
			if($registerCustomer->getEmail() != $data[$p_id][$j]['email']) {
				if (!in_array($data[$p_id][$j]['email'], $duplicateEmail)){
					$duplicateEmail[] = $data[$p_id][$j]['email'];
					$collection = Mage::getModel('customer/customer')->getCollection()->addFieldToFilter('email', $data[$p_id][$j]['email'])->getFirstItem();
					if (!$collection->getId()) {
						if (!isset($result['error'])) {
							$result['error'] = -1;
							$result['message'] = $data[$p_id][$j]['email']."<br />";
						} else {
							$result['message'] .= $data[$p_id][$j]['email']."<br />";					
						}
					}
				}	
			}	
            $newUsers[] = array("product_id" => $p_id, "name" => $data[$p_id][$j]['firstname'], "lastname" => $data[$p_id][$j]['lastname'], "email" => $data[$p_id][$j]['email'], "item_id" => $data[$p_id][$j]['item_id']);
            $j++;	
        }
        if (isset($result)) {
			$quote = $this->getQuote();
                        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
			$model = Mage::getModel('volumelicense/volumelicense_quote');
			$model->deteleByQuote($quote->getId(),'volume_data');
			$model->setQuoteId($quote->getId());
			$model->setKey('volume_data');
			$model->setValue(json_encode($newUsers));
                        $model->setCustomerEmail($email);
			$model->save();
            return $result;
        }
        $i = 1;
        foreach ($data['product_id'] as $p_id) {
            $json_data[] = array("product_id" => $p_id, "name" => $data[$p_id][$i]['firstname'], "lastname" => $data[$p_id][$i]['lastname'], "email" => $data[$p_id][$i]['email'], "item_id" => $data[$p_id][$i]['item_id']);
            $i++;
        }
        $this->getQuote()->setVolumeData(json_encode($json_data));	
        $this->getQuote()->collectTotals();
        $this->getQuote()->save();

        $this->getCheckout()
        ->setStepData('volumelicense', 'allow', true)
        ->setStepData('volumelicense', 'complete', true)
        ->setStepData('payment', 'allow', true);

        return array();
    }
}
