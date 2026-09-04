<?php
// app/Models/Property.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = ['code', 'name', 'city', 'description'];

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}