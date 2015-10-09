<?php
class Gorilla_StampPDF_Helper_Data extends Mage_Core_Helper_Data
{
    const POSITION_TOP             = 'Top';
    const POSITION_MIDDLE          = 'VCenter';
    const POSITION_BOTTOM          = 'Bottom';
    const POSITION_ANGLE           = 'Angle';
    const POSITION_DIAG_TOPLEFT    = 'Diag-TopLeft';
    const POSITION_DIAG_BOTTOMLEFT = 'Diag-BottomLeft';

    const JUSTIFICATION_LEFT   = 'Left';
    const JUSTIFICATION_CENTER = 'Center';
    const JUSTIFICATION_RIGHT  = 'Right';

    protected $_positions = array(
        self::POSITION_TOP,
        self::POSITION_MIDDLE,
        self::POSITION_BOTTOM,
        self::POSITION_ANGLE,
        self::POSITION_DIAG_TOPLEFT,
        self::POSITION_DIAG_BOTTOMLEFT
    );

    protected $_justifications = array(
        self::JUSTIFICATION_LEFT,
        self::JUSTIFICATION_CENTER,
        self::JUSTIFICATION_RIGHT
    );

    /**
     * Write out stamp messages
     *
     * @param Gorilla_StampPDF_Model_Stamp $stamp
     * @param $infile
     * @param string $outfile (optional)
     * @return string|false
     */
    public function stampPdf(Gorilla_StampPDF_Model_Stamp $stamp, $infile, $outfile = null)
    {
        if(!$stamp->hasMessages() && !$stamp->hasStampFileContent()) {
            return false;
        }

        if($outfile === null){
            $outfile = tempnam(realpath(sys_get_temp_dir()),'gorilla_stamppdf_out') . 'pdf';

        } elseif(strpos($outfile,' ') !== false) {
            //Files cannot contain spaces. StampPDF chokes on these.
            $outfile = str_replace(' ','_',$outfile);
        }

        //If infile contains spaces, we need to create a copy without spaces in
        //The system's temp directory without spaces
        if(strpos($infile,' ') !== false){
            $infileCopy = realpath(sys_get_temp_dir()) . DS . str_replace(' ','_',basename($infile));
            if(!is_file($infileCopy)){
                if(!copy($infile, $infileCopy)){
                    throw new RuntimeException(sprintf("Could not copy infile %s to system temp %s",$infile,$infileCopy));
                }
            }
            $infile = $infileCopy;
        }

        $stampFile = $stamp->write();

        /* @var $shell Gorilla_StampPDF_Model_Shell */
        $shell = Mage::getModel('gorilla_stamppdf/shell');

        $shell->setOutputFile($outfile)
            ->setInputFile($infile)
            ->setStampFile($stampFile)
            ->setLogFile(Mage::getBaseDir('var').DS.'log'.DS.'stamppdf.log')
            ->run();

        //Clean up stamp file
        @unlink($stampFile);
        return $outfile;
    }


    /**
     * Stamp text on a PDF document. The location parameter sets the position of the stamp relative to the
     * document's margins. Below are some of the possible options:
     *
     * -top: Top margin from bottom of page. A value of 100 will but the top margin 100 pts from the bottom.
     * -right
     * -left
     * -bottom
     * -font:  Set the font, the default font is Helvetica-Bold
     * -size:  Set the font size. The default is 12
     *
     * To write more than one line at once, include a carriage return (\r).
     *
     * @param Gorilla_StampPDF_Model_Stamp $stamp Stamp model instance
     * @param string $text
     * @param string $position (top, middle, bottom, angle)
     * @param string $justification (left, center, right)
     * @param array $options (optional)
     * @return string
     */
    public function stampText(Gorilla_StampPDF_Model_Stamp $stamp, $text, $position, $justification, array $options = array())
    {
        if(!in_array($position, $this->_positions)){
            throw new InvalidArgumentException(sprintf('%s is not a valid position. Valid positions are %s',$position,implode(', ',$this->_positions)));
        }

        if(!in_array($justification, $this->_justifications)){
            throw new InvalidArgumentException(sprintf('%s is not a valid justification. Valid justifications are %s',$justification,implode(', ',$this->_justifications)));
        }

        $options['Position']      = $position;
        $options['Justification'] = $justification;

        //Carriage returns must be escaped
        $text = str_replace("\r",'\r',$text);
        $stamp->addTextMessage($text,$options);

    }
}
