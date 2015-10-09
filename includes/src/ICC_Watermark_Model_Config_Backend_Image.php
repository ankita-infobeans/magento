<?php

/**
 * Watermark config image field backend model
 *
 * @category   ICC
 * @package    ICC_Watermark
 * @author     Gorilla
 */
class ICC_Watermark_Model_Config_Backend_Image extends Mage_Adminhtml_Model_System_Config_Backend_File
{
    /**
     * Getter for allowed extensions of uploaded files
     *
     * @return array
     */
    protected function _getAllowedExtensions()
    {
        return array('jpg', 'jpeg');
    }
}
