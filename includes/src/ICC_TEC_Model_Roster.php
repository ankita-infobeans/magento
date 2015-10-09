<?php

// custom options. the registrants will be customized. the custom option field will be a text area
class ICC_TEC_Model_Roster extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        // this is the entity table - in the config xml the table is defined there
        $this->_init('icc_tec/roster');
    }

    /**
     * This function will return the custom option label which shows up
     * in the $item_prod_options array during the checkout process, so that we
     * may extract the relevant values and insert them into the database roster
     * table in the correct columns.
     *
     * @param string $option_name - string of the custom option we want
     * @return string 
     */
    public function get_custom_option_label($option_name)
    {
        $custom_options = array(
            "Code Cycle" => "CodeCycle",
            "Date" => "Date",
            "Location" => "Location",
        );

        return isset($custom_options[$option_name])
                ? $custom_options[$option_name]
                : false;
    }
}