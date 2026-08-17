<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Auditable;
    
    protected $table = 'companies'; // filiais

    protected $fillable = [
        'is_active',
        'code', // codigo interno
        'name',
        'trade_name', // nome fantasia
        'cnpj',
        'ie',
        'zip',
        'street',
        'number',
        'district',
        'city',
        'state',
        'complement',
        'geolocation',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
