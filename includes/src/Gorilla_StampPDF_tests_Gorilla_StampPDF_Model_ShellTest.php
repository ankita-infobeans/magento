<?php
class Gorilla_StampPDF_Model_ShellTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Gorilla_StampPDF_Model_Shell
     */
    protected $shell;

    function setUp()
    {
        $this->shell = Mage::getModel('gorilla_stamppdf/shell');
        $this->assertInstanceOf('Gorilla_StampPDF_Model_Shell',$this->shell);
    }

    public function testCreatesOutputFile()
    {
        $outfile = tempnam(realpath(sys_get_temp_dir()),'gorilla_stamppdf') . 'pdf';
        $infile  = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_assets' . DIRECTORY_SEPARATOR . 'input.pdf';
        $stamp   = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_assets' . DIRECTORY_SEPARATOR . 'stamp.txt';
        $this->shell->setOutputFile($outfile)
                    ->setInputFile($infile)
                    ->setStampFile($stamp);

        $this->shell->run();
        $this->assertFileExists($outfile);
        @unlink($outfile);
    }

    public function testInvalidApplicationPathReturnsException()
    {
        $this->setExpectedException('RuntimeException');
        $outfile = tempnam(realpath(sys_get_temp_dir()),'gorilla_stamppdf') . 'pdf';
        $infile  = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_assets' . DIRECTORY_SEPARATOR . 'input.pdf';
        $stamp   = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_assets' . DIRECTORY_SEPARATOR . 'stamp.txt';
        $this->shell->setOutputFile($outfile)
            ->setInputFile($infile)
            ->setStampFile($stamp);

        $this->shell->setApplicationPath('/usr/bin/idontexist');
        $this->shell->run();
    }
}