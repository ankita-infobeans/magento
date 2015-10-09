<?php
class Gorilla_JsMinificationFix_Model_Core_Design_Package extends Mage_Core_Model_Design_Package
{
    /**
     * Merge specified javascript files and return URL to the merged file on success
     *
     * @param $files
     * @return string
     */
    public function getMergedJsUrl($files)
    {
         $targetFilename = md5(implode(',', $files)) . '.js'; # Mage Code
         
        /* Gorilla Code - Create file name based upon file name, date, and file size rather than just name*/
        $fileName = false;
        foreach ($files as $file)
        {
            if (file_exists($file))
            {
                $fileName .= filesize($file) . filemtime($file) . $file;
            }
        }
        if ($fileName)
        {
            $targetFilename = md5($fileName) . '.js';
        }
        
        
        /* End Gorilla Code */
        
        $targetDir = $this->_initMergerDir('js');
        if (!$targetDir) {
            return '';
        }
        if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, null, 'js')) {
            return Mage::getBaseUrl('media', Mage::app()->getRequest()->isSecure()) . 'js/' . $targetFilename;
        }
        return '';
    }
}
