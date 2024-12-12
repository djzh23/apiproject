<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    use HasFactory;

    // Define constants for status values
    const STATUS_STANDING = 'standing';
    const STATUS_COMPLETE = 'complete';

    // Define an array of valid statuses
    const VALID_STATUSES = [
        self::STATUS_STANDING,
        self::STATUS_COMPLETE,
    ];

    protected $fillable = [
        'creator_id',
        'start_work',
        'status',
        'date',
        'team',
        'ort',
        'vorort',
        'list_of_helpers',
        'plan',
        'end_work',
        'reflection',
        'defect',
        'parent_contact',
        'wellbeing_of_children',
        'notes',
        'wishes',
        'pdf_file',
        'updated_at',
        'pdf_file',
    ];

    protected $casts = [
        'date' => 'date',
        'vorort' => 'boolean',
        'list_of_helpers' => 'array',
        'start_work' => 'datetime',
        'end_work' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function ageGroups()
    {
        return $this->belongsToMany(AgeGroup::class, 'work_age_group')
            ->withPivot('boys', 'girls')
            ->withTimestamps();
    }

}
