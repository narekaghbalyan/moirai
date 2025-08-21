<?php

namespace Moirai\Drivers\Grammars;

class OracleGrammar extends Grammar implements GrammarInterface
{
    /**
     * @var array
     */
    protected array $pitaForColumns = [
        'opening' => '"',
        'closing' => '"'
    ];

    /**
     * @var array
     */
    protected array $pitaForStrings = [
        'opening' => '\'',
        'closing' => '\''
    ];
}