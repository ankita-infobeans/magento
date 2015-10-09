<?php
class Gorilla_StampPDF_Model_Stamp_Message_TextTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Gorilla_StampPDF_Model_Stamp_Message_Text
     */
    protected $message;

    function setUp()
    {
        $this->message = new Gorilla_StampPDF_Model_Stamp_Message_Text('Hello World',array(
            'name'     => 'Hello Stamp',
            'offset_x' => 10,
            'offset_y' => 10
        ));
    }

    public function testRender()
    {
        $expected = <<< TEXT
Begin_Message

Type (Text)
Underlay (No)
OffsetX (10)
OffsetY (10)
Text (Hello World)
WordWrap (No)
TextMode (0)
CharSpace (0)
WordSpace (0)
LineSpace (1)
Size (12)
Angle (0)
MultiLine (Yes)

End_Message

TEXT;

        $this->assertEquals($expected,$this->message->render());

    }

    public function testCanChangeOptions()
    {
        $expected = <<< TEXT
Begin_Message

Type (Text)
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

        $this->message
            ->setCharSpace(1)
            ->setLineSpace(2)
            ->setTextMode(1)
            ->setWordSpace(1)
            ->setWordWrap(true)
            ->setUnderlay(true)
            ->setJustification('Left');

        $this->assertEquals($expected,$this->message->render());
    }

}