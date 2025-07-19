<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    //
    protected $fillable = [
        'quote_id',
        'employee_id',
        'name',
        'description',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    
}
