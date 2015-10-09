<?php
class Gorilla_StampPDF_Helper_DataTest extends PHPUnit_Framework_TestCase
{
    /** @var  Gorilla_StampPDF_Helper_Data */
    protected $helper;
    protected $stamp;

    public function setUp()
    {
        $this->helper = Mage::helper('gorilla_stamppdf');
        $this->stamp = Mage::getModel('gorilla_stamppdf/stamp');
        $this->assertInstanceOf('Gorilla_StampPDF_Helper_Data',$this->helper);
        $this->assertInstanceOf('Gorilla_StampPDF_Model_Stamp', $this->stamp);
    }

    public function testStampTextSingleLine()
    {
        $outfile = tempnam(realpath(sys_get_temp_dir()),'gorilla_stamppdf') . 'pdf';
        $infile  = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_assets' . DIRECTORY_SEPARATOR . 'input.pdf';

        $this->helper->stampText($this->stamp, "Hello World",Gorilla_StampPDF_Helper_Data::POSITION_BOTTOM, Gorilla_StampPDF_Helper_Data::JUSTIFICATION_LEFT);
        $file = $this->helper->stampPdf($this->stamp, $infile,$outfile);
        $this->assertEquals($outfile, $file);
        $this->assertFileExists($outfile);
        @unlink($outfile);
    }

    public function testStampTextMultiLine()
    {
        $outfile = tempnam(realpath(sys_get_temp_dir()),'gorilla_stamppdf') . 'pdf';
        $infile  = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_assets' . DIRECTORY_SEPARATOR . 'input.pdf';

        $options = array(
            'Top'  => 50,
            'Font' => 'Times Roman',
            'Size' => 10
        );
        //Also makes sure that carriage returns are escaped
        $this->helper->stampText($this->stamp, "Hello World from\rDavid Joly",Gorilla_StampPDF_Helper_Data::POSITION_TOP, Gorilla_StampPDF_Helper_Data::JUSTIFICATION_LEFT, $options);
        $file = $this->helper->stampPdf($this->stamp, $infile,$outfile);
        $this->assertEquals($outfile, $file);
        $this->assertFileExists($outfile);
        @unlink($outfile);
    }
}