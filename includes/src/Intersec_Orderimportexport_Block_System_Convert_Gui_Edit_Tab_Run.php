<?php

class Intersec_Orderimportexport_Block_System_Convert_Gui_Edit_Tab_Run
    extends Mage_Adminhtml_Block_System_Convert_Profile_Edit_Tab_Run
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('orderimportexport/system/convert/profile/run.phtml');
    }
    
    public function showFileList()
    {
        $model = Mage::registry('current_convert_profile');
        
        return ($model->getDirection() == 'import')
            && $model->getDataTransfer() == 'interactive';
    }
    
    public function getImportedFiles()
    {
        $files = array();
        $path = Mage::app()->getConfig()->getTempVarDir().'/import';
        if (!is_readable($path)) {
            return $files;
        }
        $dir = dir($path);
        while (false !== ($entry = $dir->read())) {
            if($entry != '.'
               && $entry != '..')
            {
                $files[] = $entry;
            }
        }
        sort($files);
        $dir->close();
        return $files;
    }
}
