<?php

namespace Moarai\Drivers;

class MySqlDriver extends Driver
{
    public function initializeDriver(): void
    {
        $this->setPitaForColumns('`');

        $this->setPitaForStrings('\'');
    }
}