<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RespondentConditionTag extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'respondent_condition_tag';

    protected $fillable = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'respondent_id',
        'condition_id'
    ];
}
