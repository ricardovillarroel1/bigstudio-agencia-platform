<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAdInsight extends Model
{
    protected $table = 'meta_ad_insights';

    protected $fillable = [
        'meta_ad_account_id',
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
        return $this->belongsTo(MetaAdAccount::class, 'meta_ad_account_id');
    }

    // ROAS calculado
    public function getRoasAttribute(): float
    {
        return $this->inversion > 0 ? round($this->ventas / $this->inversion, 2) : 0;
    }
}
