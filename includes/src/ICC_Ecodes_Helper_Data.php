<?php

class ICC_Ecodes_Helper_Data extends Mage_Core_Helper_Abstract
{
	const ECODES_ITEM_TYPE = 532;

    public function encryptPassword($password) {
        return Mage::helper('core')->encrypt($password);
    }

    public function decryptPassword($password) {
        return Mage::helper('core')->decrypt($password);
    }

    public function validateLogin($username) {
        if (strlen($username) < 6) {
            return 'The minimum username length is 6';
        }
		if (strlen($username) > 20) {
            return 'The maximum username length is 20';
        }
		if (preg_match('/^[a-zA-Z0-9_]*$/', $username) == 0) {
            return 'The username has invalid characters';
		}
	}

    public function validatePassword($password, $username, $firstName, $lastName) {
        //confirm user exists in ICC Connect
        $icc_connect = Mage::getModel('ecodes/api');
        if(!$icc_connect->hasConnection())
        {
            // queue but let them move on
/*
            $q = Mage::getModel('gorilla_queue/queue');
            $q->addToQueue(
                        'ecodes/apiQueue', 
                        'processNotifiyUserCreateAccountQueueItem',
                        array( 'customer_id' => $this->getId() ), 
                        'icc_emailuser_create_account'
                    )
                    ->setShortDescription( 'Icc Connect server could not be contacted.' )->save();
            
*/
            return array('success' => true, 'message' => 'We were unable to connect to the ICC Connect Web Service. Please continue with your purchase and we will email you at a later time with further instructions.');  
        }
        $result = Mage::getModel('ecodes/api')->doesUserExist($username);
        if (!$result['success']) {

            if (strlen($password) < 8) {
                return 'The minimum password length is 8';
            }
    		if (strlen($password) > 64) {
                return 'The maximum password length is 64';
            }

    		if (strpos(strtolower($password), strtolower($username)) !== false) {
                return 'The password cannot contain your username';
    		}
    		if (strlen($firstName) && strpos(strtolower($password), strtolower($firstName)) !== false) {
                return 'The password cannot contain your first name';
    		}
    		if (strlen($lastName) && strpos(strtolower($password), strtolower($lastName)) !== false) {
                return 'The password cannot contain your last name';
    		}

    		$characterTypeCount = 0;
    		if (preg_match('/[0-9]/', $password) != 0) {
    			$characterTypeCount++;
    		}
    		if (preg_match('/[a-z]/', $password) != 0) {
    			$characterTypeCount++;
    		}
    		if (preg_match('/[A-Z]/', $password) != 0) {
    			$characterTypeCount++;
    		}
    		if (preg_match('/[!@#$%\^&+=]/', $password) != 0) {
    			$characterTypeCount++;
    		}

    		if ($characterTypeCount < 3) {
    			return "The password must contain three of the four following character types: a digit, an uppercase letter, a lowercase letter, a symbol.";
    		}
        }
	}

	public function fixXml($xml) {
		if (strpos($xml, 'Code"') !== false) $xml = str_replace('Code"', 'Code="', $xml);
		if (strpos($xml, 'Id=""r') !== false) $xml = str_replace('Id=""r', 'Id=""', $xml);

		return $xml;
	}
        
    public function getDurationFromSku($sku)
    {
        // sku has form of: IC-P-2009-000003-Corporateu3yR 
        // where the last part is number of seats (units) and time with the R at the end signifying renewal
        
        // first try to get duration from 
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        $duration = $product->getSubscriptionDuration();
        if( ! empty($duration))
        {
            switch($duration) {
                case '148':
                    $dur = array(
                        'period' => 'year',
                        'number' => 3
                    );
                    break;
                case '149':
                    $dur = array(
                        'period' => 'year',
                        'number' => 1
                    );
                    break;
                case '150':
                    $dur = array(
                        'period' => 'month',
                        'number' => 6
                    );
                    break;
            }
            return $dur;
        }
        $sku_parts = explode('-', $sku);
        $period = strtolower( array_pop($sku_parts) );
        $period = rtrim($period, 'r');
        $period = substr($period, -2);
        $duration_code = substr($period, -1);
        $number = (int)$period;
        switch($duration_code)
        {
            case 'y':
                $duration = 'year';
                break;
            case 'm':
                $duration = 'month';
                break;
        }
        $dur = array(
            'period' => $duration,
            'number' => $number
        );
        return $dur;
    }
    
    public function addDateFromSku($orig_date, $sku)
    {
        
        $duration_parts = $this->getDurationFromSku($sku);
        //$orig_date = $ps->getExpiration();
        $new_time = strtotime('+' . $duration_parts['number'] . ' ' . $duration_parts['period'], strtotime($orig_date));
        $new_date = date('Y-m-d H:i:s', $new_time);
        return $new_date;
    }
    
    public function addDateFromDurationId($orig_date, $duration_id)
    {
        switch($duration_id)
        {
            case '148':
                $duration = '+3 y';
                break;
            case '149':
                $duration = '+1 y';
                break;
            case '150':
                $duration = '+6 m';
                break;
            case '':
                // duration not set
                return false;
                break;
            default:
                // set to something new or db's changed
                return false;
        }
        if(!isset($duration))
        {
            // we could also try and get it from the sku should we want a back up? or just add to Queue?
            return false; // if we somehow got through the switch without a duration
        }
        $new_time = strtotime($duration, strtotime($orig_date));
        $new_date = date('Y-m-d', $new_time);
        return $new_date;        
    }
}
