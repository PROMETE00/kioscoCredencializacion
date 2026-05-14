<?php

namespace App\Repositories;

use Config\Database;
use Config\Schema;
use CodeIgniter\Database\BaseConnection;

abstract class BaseRepository
{
    protected BaseConnection $db;
    protected Schema $schema;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->schema = config('Schema');
    }

    /**
     * Get a physical table name from its logical name.
     */
    protected function t(string $logicalName): string
    {
        return $this->schema->tables[$logicalName] ?? $logicalName;
    }

    /**
     * Get a physical field name for a logical table and field.
     */
    protected function f(string $logicalTable, string $logicalField): string
    {
        return $this->schema->fields[$logicalTable][$logicalField] ?? $logicalField;
    }
}
