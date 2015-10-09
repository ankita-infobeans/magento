<?php
/**
 * This class renders a stampfile, using the provided options.
 */
interface Gorilla_StampPDF_Model_Stamp_Message
{
    /**
     * Render the Message Block
     *
     * @return string
     */
    public function render();
    public function __toString();
}