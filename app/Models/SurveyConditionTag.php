<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyConditionTag extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'survey_condition_tag';

    protected $fillable = [
        'id',
        'survey_id',
        'condition_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
