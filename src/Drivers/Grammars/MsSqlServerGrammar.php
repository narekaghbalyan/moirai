<?php

namespace Moirai\Drivers\Grammars;

class MsSqlServerGrammar extends Grammar implements GrammarInterface
{
    /**
     * @var array
     */
    protected array $pitaForColumns = [
        'opening' => '[',
        'closing' => ']'
    ];

    /**
     * @var array
     */
    protected array $pitaForStrings = [
        'opening' => '\'',
        'closing' => '\''
    ];
}