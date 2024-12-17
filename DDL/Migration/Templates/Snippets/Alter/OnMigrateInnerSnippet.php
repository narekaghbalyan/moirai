<?php

return <<<PHP
        return Table::alter(\$this->connection, \$this->table, function (Blueprint \$blueprint) {
            \$blueprint->varchar('first_name');
            \$blueprint->varchar('name')->rename('last_name');
            \$blueprint->dropColumn('surname');
            // ...
        });
PHP;