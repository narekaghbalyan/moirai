<?php

namespace Moarai\Drivers;

class PostgreSqlDriver extends Driver
{
    public function initializeDriver(): void
    {
        $this->setPitaForColumns('"');

        $this->setPitaForStrings('\'');
    }
}