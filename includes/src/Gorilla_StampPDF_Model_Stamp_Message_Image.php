<?php
class Gorilla_StampPDF_Model_Stamp_Message_Image extends Gorilla_StampPDF_Model_Stamp_Message_Abstract
{

    /**
     * @var string
     */
    protected $_path;

    /**
     * @var float
     */
    protected $_scale;



    /**
     * @param string $text
     */
    public function __construct($path,array $options = null)
    {
        $this->setPath($path);

        parent::__construct($options);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'Image';
    }

    /**
     * @return array
     */
    protected function _getOptions()
    {
        $options = array(
            'Path' => $this->_path
        );

        if($this->_scale && is_numeric($this->_scale)) {
            $options['Scale'] = $this->_scale;
        }

        return $options;
    }


    /**
     * @param string $file
     * @return Gorilla_StampPDF_Model_Stamp_Message_Image
     * @throws InvalidArgumentException
     */
    public function setPath($file)
    {
        if(!is_file($file)){
            throw new InvalidArgumentException(sprintf('The image file %s does not exist',$file));
        }

        if(!substr($file, -4) == '.jpg') {
            throw new InvalidArgumentException("Invalid image format for $file. Only JPEG images are permitted.");
        }

        $this->_path = $file;
        return $this;
    }

    /**
     * @param float $scale
     * @return Gorilla_StampPDF_Model_Stamp_Message_Image
     */
    public function setScale($scale)
    {
        if($scale && !is_numeric((string)$scale)) {
            throw new InvalidArgumentException(sprintf('Scale must be numeric. Scale given: %s',$scale));
        }
        $this->_scale = $scale;
        return $this;
    }

}