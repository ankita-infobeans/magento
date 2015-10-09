<?php
class Gorilla_StampPDF_Model_Stamp_Message_Text extends Gorilla_StampPDF_Model_Stamp_Message_Abstract
{

    /**
     * Angle to rotate text stamp
     *
     * @var int
     */
    protected $_angle = 0;

    /**
     * Font Size
     *
     * @var int
     */
    protected $_size = 12;

    /**
     * @var string
     */
    protected $_font;

    /**
     * @var string
     */
    protected $_fontFile;

    /**
     * Text mode: solid (0), outline (1) or invisible (2)
     *
     * @var int
     */
    protected $_textMode = 0;

    /**
     * Actual text of stamp
     *
     * @var string
     */
    protected $_text;

    /**
     * Insert line breaks to fir text within margins
     *
     * @var boolean
     */
    protected $_wordWrap = false;

    /**
     * @var int
     */
    protected $_charSpace = 0;

    /**
     * @var int
     */
    protected $_wordSpace = 0;

    /**
     * @var int
     */
    protected $_lineSpace = 1;

    /**
     * @var boolean
     */
    protected $_multiLine = true;

    /**
     * @param string $text
     */
    public function __construct($text,array $options = null)
    {
        $this->_text = $text;
        parent::__construct($options);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'Text';
    }

    /**
     * @return array
     */
    protected function _getOptions()
    {
        $options = array(
            'Text'      => str_replace("\r",'\r', $this->_text),
            'WordWrap'  => $this->_wordWrap ? 'Yes' : 'No',
            'TextMode'  => $this->_textMode,
            'CharSpace' => $this->_charSpace,
            'WordSpace' => $this->_wordSpace,
            'LineSpace' => $this->_lineSpace,
            'Size'      => $this->_size,
            'Angle'     => $this->_angle,
            'MultiLine' => $this->_multiLine ? 'Yes' : 'No'
        );


        if($this->_font){
            $options['Font'] = $this->_font;
        }

        if($this->_fontFile){
            $options['FontFile'] = $this->_fontFile;
        }

        return $options;
    }

    /**
     * @param int $mode
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setTextMode($mode)
    {
        $valid = array(0,1,2);
        if(!in_array($mode,$valid)){
            throw new InvalidArgumentException('Invalid mode, must be 0, 1, or 2');
        }
        $this->_textMode = $mode;
        return $this;
    }

    /**
     * @param $flag
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setWordWrap($flag)
    {
        $this->_wordWrap = (boolean)$flag;
        return $this;
    }

    /**
     * @param string $font
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setFont($font)
    {
        $valid = array(
            'Courier',
            'Courier-Bold',
            'Courier-Oblique',
            'Courier-BoldOblique',
            'Zapf Dingbats',
            'Helvetica-Bold',
            'Helvetica',
            'Helvetica-BoldOblique',
            'Symbol',
            'Times Roman',
            'Times Bold',
            'Times Italic',
            'Times Bold Italic'
        );

        if(!in_array($font,$valid)){
            throw new InvalidArgumentException(sprintf('%s is not a valid font value. Valid values are %s',
                $font,implode(', ',$valid)));
        }
        $this->_font = $font;
        return $this;
    }

    /**
     * @param string $file
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     * @throws InvalidArgumentException
     */
    public function setFontFile($file)
    {
        if(!is_file($file)){
            throw new InvalidArgumentException(sprintf('The file %s does not exist',$file));
        }
        $this->_fontFile = $file;
        return $this;
    }

    /**
     * @param int $space
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setCharSpace($space)
    {
        $this->_charSpace = (int)$space;
        return $this;
    }

    /**
     * @param int $space
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setWordSpace($space)
    {
        $this->_wordSpace = (int)$space;
        return $this;
    }

    /**
     * @param int $space
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setLineSpace($space)
    {
        $this->_lineSpace = (int)$space;
        return $this;
    }


    /**
     * @param int $angle
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setAngle($angle)
    {
        $this->_angle = $angle;
        return $this;
    }

    /**
     * @param int $size
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setSize($size)
    {
        $this->_size = $size;
        return $this;
    }

    /**
     * @param boolean $flag
     * @return Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    public function setMultiLine($flag)
    {
        $this->_multiLine = (boolean)$flag;
        return $this;
    }

}