<?php
// app/Models/Offer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'import_id',
        'property_id',
        'supplier_id',
        'external_id',
        'check_in',
        'check_out',
        'max_guests',
        'price',
        'currency',
        'available_units',
        'expires_at'
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'expires_at' => 'datetime',
        'payload' => 'array',
        'source_sent_at' => 'datetime',
    ];
    public function getRouteKeyName(): string
    {
        return 'external_id';
    }
    public function import()
    {
        return $this->belongsTo(Import::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
}
}