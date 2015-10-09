<?php
/**
 * This class renders a stampfile, using the provided options.
 */
class Gorilla_StampPDF_Model_Stamp
{
    /**
     * @var int
     */
    protected $_version      = 1;

    /**
     * @var int
     */
    protected $_topMargin    = 12;

    /**
     * @var int
     */
    protected $_bottomMargin = 12;

    /**
     * @var int
     */
    protected $_rightMargin  = 8;

    /**
     * @var int
     */
    protected $_leftMargin   = 8;

    /**
     * @var string
     */
    protected $_viewMode;

    /**
     * @var string
     */
    protected $_openMode;

    /**
     * Message blocks to render
     *
     * @var Gorilla_StampPDF_Model_Stamp_Message[]
     */
    protected $_messages = array();


    /**
     * Optional stamp file content. Use for custom stamp files content. If set, this will override all other stamp configurations set on the object.
     *
     * @var string
     */
    protected $_stampFileContent;

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        if($options){
            foreach($options as $option => $value){
                $method = $this->methodize($option);
                if(method_exists($this,$method)){
                    $this->$method($value);
                }
            }
        }
    }

    /**
     * Set custom stamp file. This file will be used rather than compiling the messages down into a stamp file.
     *
     * @param $content
     * @return $this
     */
    public function setStampFileContent($content)
    {
        $this->_stampFileContent = $content;
        return $this;
    }

    public function hasStampFileContent()
    {
        return $this->_stampFileContent != null && $this->_stampFileContent;
    }

    public function getStampFileContent()
    {
        return $this->_stampFileContent;
    }

    /**
     * @return bool
     */
    public function hasMessages() {
        return count($this->_messages) > 0;
    }

    /**
     * @param Gorilla_StampPDF_Model_Stamp_Message $message
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function addMessage(Gorilla_StampPDF_Model_Stamp_Message $message)
    {
        $this->_messages[] = $message;
        return $this;
    }

    /**
     * @param string $text
     * @param array $options
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function addTextMessage($text,array $options = null)
    {
        return $this->addMessage(new Gorilla_StampPDF_Model_Stamp_Message_Text($text,$options));
    }

    /**
     * @param string $path
     * @param array $options
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function addImageMessage($path,array $options = null)
    {
        return $this->addMessage(new Gorilla_StampPDF_Model_Stamp_Message_Image($path,$options));
    }

    /**
     * @param string $text
     * @param array $options
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function addUFT8Message($text,array $options = null)
    {
        return $this->addMessage(new Gorilla_StampPDF_Model_Stamp_Message_UTF8($text,$options));
    }


    /**
     * Renders Stamp File Content
     *
     * @return string;
     */
    public function render()
    {
        // Custom stamp file content override
        if($this->hasStampFileContent()) {
            return $this->getStampFileContent();
        }

        $options = array(
            'Version'      => $this->_version,
            'TopMargin'    => $this->_topMargin,
            'RightMargin'  => $this->_rightMargin,
            'LeftMargin'   => $this->_leftMargin,
            'BottomMargin' => $this->_bottomMargin,
        );

        if($this->_openMode){
            $options['OpenMode'] = $this->_openMode;
        }

        if($this->_viewMode){
            $options['ViewMode'] = $this->_viewMode;
        }

        $stamp = 'Begin_Options' . PHP_EOL . PHP_EOL;
        foreach($options as $option => $value)
        {
            $stamp .= sprintf('%s (%s)',$option, $value) . PHP_EOL;
        }
        $stamp .= PHP_EOL . 'End_Options' . PHP_EOL;

        foreach($this->_messages as $message){
            /* @var $message Gorilla_StampPDF_Model_Stamp_Message */
            $stamp .= PHP_EOL . $message->render();
        }

        return $stamp;

    }

    public function __toString()
    {
        return $this->render();
    }

    /**
     * @param string|null $file
     * @return bool|null|string
     */
    public function write($file = null)
    {
        if($file === null){
            $file = tempnam(realpath(sys_get_temp_dir()),'gorilla_stamppdf_stampfile') . 'txt';
        }

        $res = @file_put_contents($file,$this->render());
        return $res === false ? false : $file;
    }

    /**
     * @param int $version
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function setVersion($version)
    {
        $this->_version = (int)$version;
        return $this;
    }

    /**
     * @param int $margin
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function setRightMargin($margin)
    {
        $this->_rightMargin = (int)$margin;
        return $this;
    }

    /**
     * @param int $margin
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function setLeftMargin($margin)
    {
        $this->_leftMargin = (int)$margin;
        return $this;
    }

    /**
     * @param int $margin
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function setBottomMargin($margin)
    {
        $this->_bottomMargin = (int)$margin;
        return $this;
    }
    /**
     * @param int $margin
     * @return Gorilla_StampPDF_Model_Stamp
     */
    public function setTopMargin($margin)
    {
        $this->_topMargin = (int)$margin;
        return $this;
    }

    /**
     * @param string $mode
     * @return Gorilla_StampPDF_Model_Stamp
     * @throw InvalidArgumentException
     */
    public function setOpenMode($mode)
    {
        $valid = array('ShowBookMarks','ShowThumbNails','ShowNone');
        if(!in_array($mode,$valid)){
            throw new InvalidArgumentException(sprintf('%s is not a valid OpenMode value. Valid values are %s',
                $mode,implode(', ',$valid)));
        }
        $this->_openMode = $mode;
        return $this;
    }

    /**
     * @param string $mode
     * @return Gorilla_StampPDF_Model_Stamp
     * @throw InvalidArgumentException
     */
    public function setViewMode($mode)
    {
        $valid = array('ActualSize','FitHeight','FitPage','FitVisible','FitWidth');
        if(!in_array($mode,$valid)){
            throw new InvalidArgumentException(sprintf('%s is not a valid ViewMode value. Valid values are %s',
                $mode,implode(', ',$valid)));
        }
        $this->_viewMode = $mode;
        return $this;
    }

    public function methodize($value)
    {
        return 'set'.str_replace(' ','',ucwords(str_replace('_',' ',$value)));
    }
}