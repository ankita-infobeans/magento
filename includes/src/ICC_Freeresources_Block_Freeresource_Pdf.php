<?php
/**
 * Free Resource list block
 *
 * @category    ICC
 * @package     ICC_Freeresources
  */
class ICC_Freeresources_Block_Freeresource_Pdf
    extends Mage_Catalog_Block_Product_List {
    /**
     * initialize
     * @access public

     */
    public function __construct()
    {
        $file = 'media/Document/032014-LA.pdf';
        header('Pragma: public');
        header('Expires: 0');
        header('Content-Type: application/pdf');
        header("Content-Disposition: inline; filename='032014-LA.pdf'");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Length' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
    }
}
