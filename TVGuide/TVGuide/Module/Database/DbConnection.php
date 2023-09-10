<?php
declare(strict_types=1);

namespace TVGuide\Module\Database;

use PDO;

final class DbConnection extends PDO
{
    public function __construct(string $dsn, string $username = null, string $password = null, array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
    }
}