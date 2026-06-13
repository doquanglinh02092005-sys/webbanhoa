<?php
declare(strict_types=1);

namespace App\Core;

use mysqli;

abstract class Model
{
    protected mysqli $db;

    public function __construct(?mysqli $connection = null)
    {
        $this->db = $connection ?? db();
    }
}
