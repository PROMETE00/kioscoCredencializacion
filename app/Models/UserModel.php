<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'usuarios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'usuario','nombre','email','password_hash','rol_id','activo','created_at','updated_at'
    ];

    public function findActiveByUsuario(string $usuario): ?array
    {
        return $this->where('usuario', $usuario)
                    ->where('activo', 1)
                    ->first();
    }
}