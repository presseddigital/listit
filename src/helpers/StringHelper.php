<?php
namespace presseddigital\listit\helpers;

class StringHelper extends \craft\helpers\StringHelper
{
	// Public Methods
    // =========================================================================

    public static function labelize(string $string, bool $onlyUcFirst = false): string
    {
        $string = str_replace('_', ' ', $string);
        $string = trim(preg_replace('/(?<=\\w)(?=[A-Z])/', " $1", $string));
        return ucfirst($onlyUcFirst ? strtolower($string) : $string);
    }
}


