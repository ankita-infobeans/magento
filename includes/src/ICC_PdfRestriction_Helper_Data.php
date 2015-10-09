<?php
class ICC_PdfRestriction_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_downloadableProduct = NULL;

    public function __construct()
    {
        $this->_downloadableProduct = Mage::registry('downloadable_product');
    }

    public function setRestrictions($in_file)
    {
        if (!$this->_downloadableProduct){
            return false;
        }
        /* parameters for the output document */
        $out_filename = '';

        // set in the admin panel for all products
        $passMaster = Mage::helper('core')->decrypt($this->getMasterPassword());

        // password for opening file
        $passUser = '';

        $permissions = $this->getRestrictions();

        // if product has his own password we should use it as master password for unlocking the file
        if ($this->getUserPassword() != ''){
            $passUser = $this->getUserPassword();
        }

        if($permissions === '' && $this->getUserPassword() === ''){
        	return false;
        }
        
       // $key = 'L410702-010500-800228-T7U5A2-W6V592';
          $key = 'L500702-010525-800681-32JU22-C9QEH2';
      
        $license = sprintf("license={%s}", $key);

        try{
            if(!class_exists('PLOP')) {
                throw new Exception("PDFLib class PLOP not found.");
            }
            /**
             * create a new PLOP object
             * using pdf library PDFlib PLOP, more information http://www.pdflib.com/products/plop/
             */
            $plop = new PLOP();
            $plop->set_option($license);

            $optlist = "";

            /* open protected input file with the password */
            $optlist = sprintf("password {%s} ", $passMaster);
            if (!($doc = $plop->open_document($in_file, $optlist))) {
                Mage::log("Error: " . $plop->get_errmsg(), null, 'pdflib_exception.log');
            }
            
            Mage::log($optlist, null , 'mylog.log');

            /* create the output file */
            //$optlist = sprintf("masterpassword {%s} userpassword {%s} permissions {%s}", $passMaster, $passUser, $permissions);
            $optlist = sprintf("masterpassword {%s} userpassword {%s} permissions {%s} input=%d", $passMaster, $passUser, $permissions, $doc);
            Mage::log($optlist, null , 'mylog.log');
            if (!$plop->create_document($out_filename, $optlist)) {
                Mage::log("Error: " . $plop->get_errmsg(), null, 'pdflib_exception.log');
            }

            $buf = $plop->get_buffer();
            $len = strlen($buf);

            header("Pragma: public");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-type: application/pdf");
            header("Content-Length: $len");

            print $buf;

            /* close input and output files */
            $plop->close_document($doc, "");
        }
        catch (PLOPException $e) {
            Mage::log("PLOP exception occurred in encrypt noprint:\n" ."[" . $e->get_errnum() . "] " . $e->get_apiname() . ": " .$e->get_errmsg() . "\n", null, 'pdflib_exception.log');
            return false;
        }
        catch (Exception $e) {
            Mage::log($e, null, 'pdflib_exception.log');
            return false;
        }
        $plop = 0;
    }

    public function getMasterPassword()
    {
        return Mage::getStoreConfig('pdf_password_section/master_password_group/password_input_field');
    }

    public function getUserPassword(){
        $isAtive = $this->_downloadableProduct->getUsePdfPass();
        $userPass = $this->_downloadableProduct->getPdfPassword();
        return ($isAtive == 1 && !empty($userPass)) ? $userPass : '';
    }

    public function getRestrictions(){
        $restriction = '';

        $restrArray = array(
            'noprint'       => $this->_downloadableProduct->getNoprint(),
            'nomodify'      => $this->_downloadableProduct->getNomodify(),
            'nocopy'        => $this->_downloadableProduct->getNocopy(),
            'noannots'      => $this->_downloadableProduct->getNoannots(),
            'noforms'       => $this->_downloadableProduct->getNoforms(),
            'noaccessible'  => $this->_downloadableProduct->getNoaccessible(),
            'noassemble'    => $this->_downloadableProduct->getNoassemble(),
            'nohiresprint'  => $this->_downloadableProduct->getNohiresprint(),
            'plainmetadata' => $this->_downloadableProduct->getPlainmetadata()
        );

        foreach($restrArray as $key => $value){
            if ($value == '1'){
                $restriction .= $key.' ';
            }
        }
        return $restriction;
    }
}
