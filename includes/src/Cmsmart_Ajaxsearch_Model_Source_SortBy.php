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
class Cmsmart_Ajaxsearch_Model_Source_SortBy
{
  public function toOptionArray()
  {
    return array(
      array('value' => 0, 'label' =>'ID'),
      array('value' => 1, 'label' =>'Name'),
      array('value' => 2, 'label' =>'Price'),
      array('value' => 3, 'label' =>'Create Date'),
      array('value' => 4, 'label' =>'Quantity'),
     
     // and so on...
    );
  }
}
