<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleAdInsight extends Model
{
    protected $table = 'google_ad_insights';

    protected $fillable = [
        'google_ad_account_id',
        'periodo',
        'nivel',
        'objeto_id',
        'objeto_nombre',
        'inversion',
        'ventas',
        'compras',
        'alcance',
        'impresiones',
        'clicks',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public function cuenta()
    {
        return $this->belongsTo(GoogleAdAccount::class, 'google_ad_account_id');
    }
}
