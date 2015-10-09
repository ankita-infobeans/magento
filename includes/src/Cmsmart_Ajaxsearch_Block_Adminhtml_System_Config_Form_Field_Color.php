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

class Cmsmart_Ajaxsearch_Block_Adminhtml_System_Config_Form_Field_Color extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Add color picker
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return String
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
		$html = $element->getElementHtml(); //Default HTML
		$jsPath = $this->getJsUrl('cmsmart/ajaxsearch/jquery-1.7.2.min.js');
        $jsPath1 = $this->getJsUrl('cmsmart/ajaxsearch/jquery-noconflict17.js');
		$mcPath = $this->getJsUrl('cmsmart/ajaxsearch/mcolorpicker/');
		
		if (Mage::registry('jqueryLoaded') == false)
		{
			$html .= '
			<script type="text/javascript" src="'. $jsPath .'"></script>
            <script type="text/javascript" src="'. $jsPath1 .'"></script>
			';
			Mage::register('jqueryLoaded', 1);
        }
		if (Mage::registry('colorPickerLoaded') == false)
		{
			$html .= '
			<script type="text/javascript" src="'. $mcPath .'mcolorpicker.min.js"></script>
			<script type="text/javascript">
				Cmsmart17.fn.mColorPicker.init.replace = false;
				Cmsmart17.fn.mColorPicker.defaults.imageFolder = "'. $mcPath .'images/";
				Cmsmart17.fn.mColorPicker.init.allowTransparency = true;
				Cmsmart17.fn.mColorPicker.init.showLogo = false;
			</script>
            <link type="text/css" rel="stylesheet" href="'. $this->getJsUrl('cmsmart/ajaxsearch/mcolorpicker/jquery.mcolorpicker.css') .'" />
            ';
			Mage::register('colorPickerLoaded', 1);
        }
		
		$html .= '
			<script type="text/javascript">
				Cmsmart17(function($){
					$("#'. $element->getHtmlId() .'").attr("data-hex", true).width("250px").mColorPicker();
				    
                });
                
			</script>
        ';
		
        return $html;
    }
}
