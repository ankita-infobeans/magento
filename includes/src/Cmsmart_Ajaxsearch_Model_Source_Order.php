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

class Cmsmart_Ajaxsearch_Model_Source_Order
{
  public function toOptionArray()
  {
    return array(
      array('value' => 0, 'label' =>'Ascending'),
      array('value' => 1, 'label' => 'Descending'),     
     // and so on...
    );
  }
}
