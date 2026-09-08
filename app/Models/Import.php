<?php
// app/Models/Import.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'external_import_id',
        'sent_at',
        'status',
        'total_offers',
        'processed_offers',
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