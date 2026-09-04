<?php
// app/Models/Import.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'supplier_id',
        'external_import_id',
        'sent_at',
        'status',
        'offers_count',
        'error_message'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    // Проверяем, был ли уже обработан этот импорт
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}