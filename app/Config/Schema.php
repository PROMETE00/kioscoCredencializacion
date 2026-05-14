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
            'major_code'          => 'major_code',
            'major_name'          => 'major_name',
            'photo_file_id'       => 'photo_file_id',
            'signature_file_id'   => 'signature_file_id',
            'fingerprint_file_id' => 'fingerprint_file_id',
            'created_at'          => 'created_at',
            'updated_at'          => 'updated_at',
        ],
        'tickets' => [
            'id'            => 'id',
            'student_id'    => 'student_id',
            'folio'         => 'folio',
            'qr_token_hash' => 'qr_token_hash',
            'stage_id'      => 'stage_id',
            'status_id'     => 'status_id',
            'is_active'     => 'is_active',
            'called_at'     => 'called_at',
            'expires_at'    => 'expires_at',
            'created_at'    => 'created_at',
            'updated_at'    => 'updated_at',
        ],
        'files' => [
            'id'         => 'id',
            'type'       => 'type',
            'path'       => 'path',
            'sha256'     => 'sha256',
            'mime'       => 'mime',
            'size_bytes' => 'size_bytes',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ],
        'ticket_events' => [
            'id'                 => 'id',
            'ticket_id'          => 'ticket_id',
            'event_type'         => 'event_type',
            'previous_stage_id'  => 'previous_stage_id',
            'new_stage_id'       => 'new_stage_id',
            'previous_status_id' => 'previous_status_id',
            'new_status_id'      => 'new_status_id',
            'user_id'            => 'user_id',
            'details_json'       => 'details_json',
            'created_at'         => 'created_at',
        ],
    ];
}
