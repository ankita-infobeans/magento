<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Grid column widget for rendering grid cells that contains mapped values
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ICC_TEC_Block_Widget_Grid_Column_Renderer_Options extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    /**
     * Render a grid cell as options
     *
     * @param Varien_Object $row
     * @return string
     */

    public function render(Varien_Object $row)
    {
        $options = $this->getColumn()->getOptions();
        $showMissingOptionValues = (bool)$this->getColumn()->getShowMissingOptionValues();
        if (!empty($options) && is_array($options)) {
            $value = $row->getData($this->getColumn()->getIndex());
            if (is_array($value)) {
                $res = array();
                foreach ($value as $item) {
                    if (isset($options[$item])) {
                        $res[] = $options[$item];
                    }
                    elseif ($showMissingOptionValues) {
                        $res[] = $item;
                    }
                }
                return implode(', ', $res);
            }
            elseif (isset($options[$value])) {
                return $options[$value];
            } elseif (is_string($value)) { // <--- MY CHANGES HERE
                $values = explode(',', $value);
                $returnOptions = "";
                foreach($values as $k=>$v) {
                    $returnOptions .= isset($options[$v]) ? $options[$v] : '';
                    $returnOptions .= ", ";
                }
                return substr($returnOptions, 0, -2);
            }
            return '';
        }
    }
    
    
}
