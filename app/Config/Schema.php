<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Logical to Physical Schema Mapping.
 * Use this to map logical table and field names to physical ones.
 * This ensures that when the real database schema is provided, 
 * you only need to update this file.
 */
class Schema extends BaseConfig
{
    /**
     * Table Mappings
     */
    public array $tables = [
        'students'           => 'students',
        'tickets'            => 'tickets',
        'ticket_events'      => 'ticket_events',
        'files'              => 'files',
        'roles'              => 'roles',
        'users'              => 'users',
        'cat_stages'         => 'cat_stages',
        'cat_ticket_status'  => 'cat_ticket_status',
        'ci_sessions'        => 'ci_sessions',
    ];

    /**
     * Common Field Mappings (logical => physical)
     * Optional: use this if field names vary significantly across environments.
     */
    public array $fields = [
        'students' => [
            'id'                  => 'id',
            'control_number'      => 'control_number',
            'registration_number' => 'registration_number',
            'full_name'           => 'full_name',
            'major_name'          => 'major_name',
            'photo_file_id'       => 'photo_file_id',
            'signature_file_id'   => 'signature_file_id',
            'fingerprint_file_id' => 'fingerprint_file_id',
        ],
        'tickets' => [
            'id'         => 'id',
            'student_id' => 'student_id',
            'folio'      => 'folio',
            'stage_id'   => 'stage_id',
            'status_id'  => 'status_id',
            'is_active'  => 'is_active',
        ],
    ];
}
