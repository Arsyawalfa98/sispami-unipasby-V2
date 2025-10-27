<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $guarded = []; // Karena kita mengisi semua field

    protected $fillable = [
        'user_id',
        'action',
        'roleactive',
        'module',
        'description',
        'subject_type',
        'subject_id',
        'old_data',
        'new_data'
    ];
    protected $casts = [
        'old_data' => 'json',
        'new_data' => 'json'
    ];
    
     // Mutator untuk memastikan data JSON bersih
     public function setOldDataAttribute($value)
     {
         $this->attributes['old_data'] = is_array($value) ? 
             json_encode($value, JSON_UNESCAPED_SLASHES) : $value;
     }
 
     public function setNewDataAttribute($value)
     {
         $this->attributes['new_data'] = is_array($value) ? 
             json_encode($value, JSON_UNESCAPED_SLASHES) : $value;
     }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }
}