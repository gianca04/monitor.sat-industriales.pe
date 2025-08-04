<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactData extends Model
{
    use HasFactory;

    protected $table = 'contact_data';

    protected $fillable = [
        'sub_client_id',
        'email',
        'phone_number',
        'contact_name',
    ];

    protected $casts = [
        'sub_client_id' => 'integer',
        'email' => 'string',
        'phone_number' => 'string',
        'contact_name' => 'string',
    ];

    public function subClient()
    {
        return $this->belongsTo(SubClient::class);
    }
}
