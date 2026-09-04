<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierImportLog extends Model
{
    protected $table = 'supplier_import_logs';

    protected $fillable = [
        'supplier_code',
        'status',
        'external_import_id',
        'error_message',
        'request_data',
        'ip_address',
    ];

    protected $casts = [
        'request_data' => 'json',
    ];
}
