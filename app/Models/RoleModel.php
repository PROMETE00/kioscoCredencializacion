<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table      = 'roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    public function listAll(): array
    {
        return $this->orderBy('nombre', 'ASC')->findAll();
    }
}