<?php
/*____________________________________________________________________

* Name Extension: Magento Ajaxsearch Autocomplete And Suggest

* Author: The Cmsmart Development Team 

* Date Created: 2013

* Websites: http://cmsmart.net

* Technical Support: Forum - http://cmsmart.net/support

* GNU General Public License v3 (http://opensource.org/licenses/GPL-3.0)

* Copyright © 2011-2013 Cmsmart.net. All Rights Reserved.

______________________________________________________________________*/
?>
<?php

class Cmsmart_Ajaxsearch_Model_System_Config_Backend_Color extends Mage_Core_Model_Config_Data
{
	public function save()
	{
		//Get the value from config
		$v = $this->getValue();
		if ($v == 'rgba(0, 0, 0, 0)')
		{
			$this->setValue('transparent');
			//Mage::getSingleton('adminhtml/session')->addNotice("value == rgba(0, 0, 0, 0)");
		}
		return parent::save();
	}
}
