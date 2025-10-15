<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';
    protected $fillable = ['company_name','email','phone_number','is_duplicate','duplicate_of_id'];

    public function primary() { return $this->belongsTo(Client::class,'duplicate_of_id'); }
    public function duplicates() { return $this->hasMany(Client::class,'duplicate_of_id'); }
}
