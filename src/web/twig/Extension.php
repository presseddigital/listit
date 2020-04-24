<?php
namespace presseddigital\listit\web\twig;

use presseddigital\listit\Listit;
use presseddigital\listit\web\twig\ListitVariable;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extension extends AbstractExtension implements GlobalsInterface
{
    // Twig Globals {{ global }}
    // =========================================================================

    public function getGlobals(): array
    {
        if (!Listit::$variable)
        {
            Listit::$variable = new ListitVariable();
        }

        return [
            'listit' => Listit::$variable,
        ];
    }

    // Twig Operators
    // =========================================================================

    // public function getOperators(): array
    // {
    //     return [
    //         // Unary operators
    //         [],
    //         // Binary operators
    //         [],
    //     ];
    // }

    // Twig Filters {{ var|filter }}
    // =========================================================================

    // public function getFilters(): array
    // {
    //     return [
    //         new TwigFilter('filter', [Helper::class, 'filter']),
    //     ];
    // }

    // Twig Functions {{ function(var) }}
    // =========================================================================

    // public function getFunctions()
    // {
    //     return [
    //         new TwigFunction('function', [Helper::class, 'function']),
    //     ];
    // }

}
