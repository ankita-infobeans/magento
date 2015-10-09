<?php
class Gorilla_StampPDF_Model_Stamp_Message_ImageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    protected $message;
    protected $path;

    function setUp()
    {
        $this->path = dirname(__FILE__) .  DIRECTORY_SEPARATOR . '_assets' . DIRECTORY_SEPARATOR . 'image2.jpg';

        $this->message = new Gorilla_StampPDF_Model_Stamp_Message_Image($this->path, array(
            'Position'     => 'Bottom',
            'Justification' => 'Left',

        ));
    }

    public function testRender()
    {
        $expected = <<< TEXT
Begin_Message

Type (Image)
Underlay (No)
Position (Bottom)
Justification (Left)
Path ({$this->path})

End_Message

TEXT;

        $this->assertEquals($expected,$this->message->render());

    }

}