<?php

/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

/**
 * Class ErrorAttribute
 *
 * Organizes contents of error messages
 *
 * @package QCubed
 */
class ErrorAttribute
{
    public $Label;
    public $Contents;
    public $MultiLine;

    public function __construct($strLabel, $strContents, $blnMultiLine)
    {
        $this->Label = $strLabel;
        $this->Contents = $strContents;
        $this->MultiLine = $blnMultiLine;
    }
}
