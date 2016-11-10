<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model {

    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'form';

    protected $fillable = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'form_master_id',
        'name_translation_id',
        'version'
    ];

    public function nameTranslation() {
        return $this
            ->belongsTo('App\Models\Translation', 'name_translation_id')
            ->with('translationText');
    }

    public function sections() {
        return $this
            ->belongsToMany('App\Models\Section', 'form_section')
            ->whereNull('form_section.deleted_at')
            ->withPivot('sort_order', 'is_repeatable', 'max_repetitions', 'repeat_prompt_translation_id')
            ->withTimestamps()
            ->with('questionGroups', 'nameTranslation');
    }

}