<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'document_type',
        'document_number',
        'person_type',
        'business_name',
        'description',
        'address',
        'contact_phone',
        'contact_email',
        'logo',
    ];

    protected $casts = [
        'document_type'   => 'string',         'document_number' => 'string',
        'person_type'     => 'string',
        'description'     => 'string',
        'address'         => 'string',
        'contact_email'   => 'string',
        'contact_phone'   => 'string',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'client_project')->withTimestamps();
    }

        public function subClients()
    {
        return $this->hasMany(SubClient::class);
    }
}
