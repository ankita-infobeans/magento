<?php
class ICC_Volumelicense_Block_Adminhtml_Volumelicense_Renderer_Linkinfo extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        $return_string = '';
        if ($value) {
            $data = unserialize($value);
            $return_string = $data['product_name'] . " " . "\n";
            unset($data['product_name']);
            $return_string .= "Links:\n";
            foreach ($data as $d) {
                $return_string .= $d['link_title'] . " (" . $d['number_of_downloads_used'] . "/";
                $return_string .= ($d['number_of_downloads_bought'] == 0) ? "Unlimited" : $d['number_of_downloads_bought'];
                $return_string .= ") \n";
            }
        }
        if (strpos(Mage::helper('core/url')->getCurrentUrl(), 'exportCsv') !== false) {
            return $return_string;
        } else {

            return nl2br($return_string);
        }
    }

}