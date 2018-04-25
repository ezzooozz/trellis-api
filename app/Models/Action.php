<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Action extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'action';

    protected $fillable = [
        'id',
        'action_type',
        'question_datum_id',
        'survey_id',
        'payload',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function type(){
        return $this->hasOne('action_type');
    }

}
