<?php

return <<<PHP
        return Table::create(\$this->connection, \$this->table, function (Blueprint \$blueprint) {
            \$blueprint->integer('id', true, true);
            \$blueprint->varchar('name')->notNull();
            // ...
        });
PHP;