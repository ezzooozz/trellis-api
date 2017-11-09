<?php
/**
 * Created by IntelliJ IDEA.
 * User: wi27
 * Date: 11/9/2017
 * Time: 10:10 AM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Export extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    protected $fillable = [
        'id',
        'type',
        'export_id',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $table = 'export';

}