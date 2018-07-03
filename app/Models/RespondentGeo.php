<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RespondentGeo extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'respondent_geo';

    protected $fillable = [
        'id',
        'geo_id',
        'respondent_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function geo () {
        return $this->hasOne('App/Models/Geo');
    }
}
