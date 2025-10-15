<?php

namespace Medpzl\ClubdataCart\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class SubstrViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('string', 'string', 'The string to substr', true);
        $this->registerArgument('start', 'int', 'Start position', true);
        $this->registerArgument('length', 'int', 'Length of substring', false, null);
    }

    /**
     * return string chunk
     *
     * @return string
     */
    public function render()
    {
        $string = $this->arguments['string'];
        $start = $this->arguments['start'];
        $length = $this->arguments['length'];

        if ($length !== null) {
            return substr($string, $start, $length);
        } else {
            return substr($string, $start);
        }
    }
}
