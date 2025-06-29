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
    public string $Label;
    public string $Contents;
    public bool $MultiLine;

    /**
     * Constructor for initializing the object with label, contents, and multi-line preference.
     *
     * @param string $strLabel The label, for instance.
     * @param string $strContents The contents associated with the instance.
     * @param bool $blnMultiLine Indicates whether the contents are multi-line.
     * @return void
     */
    public function __construct(string $strLabel, string $strContents, bool $blnMultiLine)
    {
        $this->Label = $strLabel;
        $this->Contents = $strContents;
        $this->MultiLine = $blnMultiLine;
    }
}