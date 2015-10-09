<?php
class Gorilla_StampPDF_Model_StampTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Gorilla_StampPDF_Model_Stamp
     */
    protected $stamp;

    function setUp()
    {
        $this->stamp = Mage::getModel('gorilla_stamppdf/stamp');
        $this->assertInstanceOf('Gorilla_StampPDF_Model_Stamp',$this->stamp);
    }

    public function testRender()
    {
        $expected = <<< TEXT
Begin_Options

Version (1)
TopMargin (12)
RightMargin (8)
LeftMargin (8)
BottomMargin (12)

End_Options

TEXT;

        $this->assertEquals($expected, $this->stamp->render());
    }

    public function testCanSetOptions()
    {
        $expected = <<< TEXT
Begin_Options

Version (2)
TopMargin (10)
RightMargin (10)
LeftMargin (10)
BottomMargin (10)

End_Options

TEXT;

        $this->stamp
            ->setTopMargin(10)
            ->setLeftMargin(10)
            ->setBottomMargin(10)
            ->setRightMargin(10)
            ->setVersion(2);

        $this->assertEquals($expected, $this->stamp->render());
    }

    public function testCanAddUTF8Message()
    {
        $expected = <<< TEXT
Begin_Options

Version (1)
TopMargin (12)
RightMargin (8)
LeftMargin (8)
BottomMargin (12)

End_Options

Begin_Message

Type (UTF8)
Underlay (Yes)
OffsetX (10)
OffsetY (10)
Justification (Left)
Text (Hello World)
WordWrap (Yes)
TextMode (1)
CharSpace (1)
WordSpace (1)
LineSpace (2)
Size (12)
Angle (0)
MultiLine (Yes)

End_Message

TEXT;
        $this->stamp->addUFT8Message('Hello World',array(
            'name'          => 'Hello Stamp',
            'offset_x'      => 10,
            'offset_y'      => 10,
            'underlay'      => true,
            'justification' => 'Left',
            'word_wrap'     => true,
            'text_mode'     => 1,
            'char_space'    => 1,
            'word_space'    => 1,
            'line_space'    => 2
        ));

        $this->assertEquals($expected,$this->stamp->render());
    }


    public function testWriteImageAndTextMessages()
    {
        $path = dirname(__FILE__) .  DIRECTORY_SEPARATOR . '_assets' . DIRECTORY_SEPARATOR . 'image2.jpg';

        $expected = <<< TEXT
Begin_Options

Version (1)
TopMargin (12)
RightMargin (8)
LeftMargin (8)
BottomMargin (12)

End_Options

Begin_Message

Type (Image)
Underlay (No)
Position (Bottom)
Justification (Left)
Path ({$path})
Scale (0.5)

End_Message

Begin_Message

Type (UTF8)
Underlay (Yes)
OffsetX (10)
OffsetY (10)
Justification (Left)
Text (Hello World)
WordWrap (Yes)
TextMode (1)
CharSpace (1)
WordSpace (1)
LineSpace (2)
Size (12)
Angle (0)
MultiLine (Yes)

End_Message

TEXT;
        $this->stamp->addImageMessage($path, array(
            'Position' => 'Bottom',
            'Justification' => 'Left',
            'Scale' => '0.5'
        ));
        $this->stamp->addUFT8Message('Hello World',array(
            'name'          => 'Hello Stamp',
            'offset_x'      => 10,
            'offset_y'      => 10,
            'underlay'      => true,
            'justification' => 'Left',
            'word_wrap'     => true,
            'text_mode'     => 1,
            'char_space'    => 1,
            'word_space'    => 1,
            'line_space'    => 2
        ));

        $this->assertEquals($expected,$this->stamp->render());
    }

    public function testWriteToFile()
    {
        $text = <<< TEXT
Begin_Options

Version (1)
TopMargin (12)
RightMargin (8)
LeftMargin (8)
BottomMargin (12)

End_Options

TEXT;

        $file = tempnam(realpath(sys_get_temp_dir()),'gorilla_stamppdf_stampfile') . 'txt';
        $this->assertEquals($file,$this->stamp->write($file));
        $this->assertFileExists($file);
        $this->assertEquals($text,file_get_contents($file));

        //Test anonymous file
        $file = $this->stamp->write();
        $this->assertFileExists($file);
        $this->assertEquals($text,file_get_contents($file));
    }
}