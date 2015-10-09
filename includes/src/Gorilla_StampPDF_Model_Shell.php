<?php
/**
 * Gorilla authored wrapper to StampPDF shell
 * @link http://docs.appligent.com/docs-stampbatch-usage#runningstamp
 */
class Gorilla_StampPDF_Model_Shell
{
    /**
     * Path to StampPDF
     *
     * @var string
     */
    protected $_appPath = '/usr/bin/stamppdfbatch/stamppdf';

    /**
     * Path to stamp file. This is a txt file containing stamping directives.
     * @link http://docs.appligent.com/docs-stampbatch-stampfiles
     * 
     * @var string
     */
    protected $_stampFile;

    /**
     * Path to the input PDF file
     * 
     * @var string
     */
    protected $_inputFile;

    /**
     * stamppdf shell options
     * @link http://docs.appligent.com/docs-stampbatch-usage#cmloptions
     */
    protected $_licenseCode;             // -r <reg number>
    protected $_outputFile;              // -o <outFile.pdf>
    protected $_logFile;                 // -l
    protected $_ownerPass;               // -d <pass>
    protected $_incrementalSave = false;
    protected $_linearize       = false; // -w
    protected $_optimize        = false; // -optimize
    protected $_nocomp          = false; // -nocomp
    protected $_comp            = false; // -comp
    protected $_iso32000        = false; // -iso3200
    protected $_encrypt         = false;
    protected $_keylength       = '';
    protected $_newOwnerPass    = '';
    protected $_newUserPass     = '';
    protected $_noPrint         = false;
    protected $_noModify        = false;
    protected $_noCopy          = false;
    protected $_noNotes         = false;
    protected $_noFill          = false;
    protected $_noAccess        = false;
    protected $_noAssembly      = false;
    protected $_noHighRes       = false;
    protected $_removeOldStamps = false;
    protected $_undoLabel       = false; // -u
    protected $_batesCharCount  = 0;

    /**
     * Executes stamppdf shell using provided options
     */
    public function run()
    {
        $cli = array(escapeshellcmd($this->_appPath));

        if($this->_licenseCode){
            $cli[] = '-r';
            $cli[] = escapeshellarg($this->_licenseCode);
        }

        if($this->_outputFile){
            $cli[] = '-o';
            $cli[] = escapeshellarg($this->_outputFile);
        }

        if($this->_logFile){
            $cli[] = '-l';
            $cli[] = escapeshellarg($this->_logFile);
        }

        $cli[] = escapeshellcmd($this->_stampFile);
        $cli[] = escapeshellcmd($this->_inputFile);

        $cliCommand = implode(' ',$cli);
        $output     = null;
        $code       = null;

        exec($cliCommand,$output,$code);

        if($code === 0){
            return true;
        } else {
            throw new RuntimeException(sprintf('Attempt to execute shell command %s resulted in code %s and message "%s"',
                $cliCommand,$code,is_array($output) ? implode(PHP_EOL,$output): $output));
        }
    }

    /**
     * Path to stamppdf shell script
     *
     * @param string $appPath
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setApplicationPath($appPath)
    {
        $this->_appPath = $appPath;
        return $this;
    }

    /**
     * StampPDF registration number (-l)
     *
     * @param $licenseCode
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setLicenseCode($licenseCode)
    {
        $this->_licenseCode = $licenseCode;
        return $this;
    }

    /**
     * @param string $ownerPass
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setOwnerPass($ownerPass)
    {
        $this->_ownerPass = $ownerPass;
        return $this;
    }

    /**
     * @param string $inputFile
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setInputFile($inputFile)
    {
        if(!is_file($inputFile)){
            throw new RuntimeException(sprintf('Input file %s does not exist.',$inputFile));
        }
        $this->_inputFile = $inputFile;
        return $this;
    }
    
    /*
     * Convert file encoding
     */
    public function changeFileEncoding($stampFile)
    {
        $outFile = $stampFile.'_2txt';
        exec('iconv -f UTF-8 -t ISO-8859-1 '.$stampFile.' > '.$outFile);
        
        return $outFile;
    }

    /**
     * @param string $stampFile
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setStampFile($stampFile)
    {
        $stampFileNew = $this->changeFileEncoding($stampFile);

        if(!is_file($stampFileNew)){
            throw new RuntimeException(sprintf('Stamp file %s does not exist.',$stampFileNew));
        }
        $this->_stampFile = $stampFileNew;
        return $this;
    }

    /**
     * Set output file (-o)
     * @param string $outputFile
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setOutputFile($outputFile)
    {
        $this->_outputFile = $outputFile;
        return $this;
    }

    /**
     * Path to custom log file location
     *
     * @param string $logFile
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setLogFile($logFile)
    {
        $this->_logFile = $logFile;
        return $this;
    }

    /**
     * @param bool $incrementalSave
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setIncrementalSave($incrementalSave)
    {
        $this->_incrementalSave = (bool)$incrementalSave;
        return $this;
    }

    /**
     * @param bool $linearize
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setLinearize($linearize)
    {
        $this->_linearize = (bool)$linearize;
        return $this;
    }

    /**
     * @param bool $optimize
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setOptimize($optimize)
    {
        $this->_optimize = $optimize;
        return $this;
    }

    /**
     * @param bool $nocomp
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNocomp($nocomp)
    {
        $this->_nocomp = (bool)$nocomp;
        return $this;
    }

    /**
     * @param bool $comp
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setComp($comp)
    {
        $this->_comp = (bool)$comp;
        return $this;
    }

    /**
     * @param bool $iso32000
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setIso32000($iso32000)
    {
        $this->_iso32000 = (bool)$iso32000;
        return $this;
    }

    /**
     * @param bool $encrypt
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setEncrypt($encrypt)
    {
        $this->_encrypt = (bool)$encrypt;
        return $this;
    }

    /**
     * @param int $keylength
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setKeylength($keylength)
    {
        $this->_keylength = $keylength;
        return $this;
    }

    /**
     * @param string $newOwnerPass
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNewOwnerPass($newOwnerPass)
    {
        $this->_newOwnerPass = $newOwnerPass;
        return $this;
    }

    /**
     * @param string $newUserPass
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNewUserPass($newUserPass)
    {
        $this->_newUserPass = $newUserPass;
        return $this;
    }

    /**
     * @param bool $noPrint
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNoPrint($noPrint)
    {
        $this->_noPrint = $noPrint;
        return $this;
    }

    /**
     * @param bool $noModify
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNoModify($noModify)
    {
        $this->_noModify = $noModify;
        return $this;
    }

    /**
     * @param bool $noCopy
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNoCopy($noCopy)
    {
        $this->_noCopy = $noCopy;
        return $this;
    }

    /**
     * @param bool $noNotes
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNoNotes($noNotes)
    {
        $this->_noNotes = $noNotes;
        return $this;
    }

    /**
     * @param bool $noFill
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNoFill($noFill)
    {
        $this->_noFill = $noFill;
        return $this;
    }

    /**
     * @param bool $noAccess
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNoAccess($noAccess)
    {
        $this->_noAccess = $noAccess;
        return $this;
    }

    /**
     * @param bool $noAssembly
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNoAssembly($noAssembly)
    {
        $this->_noAssembly = $noAssembly;
        return $this;
    }

    /**
     * @param bool $noHighRes
     * @return Gorilla_StampPDF_Model_Shell
     */
    public function setNoHighRes($noHighRes)
    {
        $this->_noHighRes = $noHighRes;
        return $this;
    }

}
