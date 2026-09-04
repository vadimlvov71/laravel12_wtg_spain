<?php
// app/Models/Supplier.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Import;
use App\Models\Offer;

class Supplier extends Model
{
    protected $fillable = ['code', 'name', 'api_key'];

    public function imports()
    {
        return $this->hasMany(Import::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}