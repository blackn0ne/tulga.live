<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the users that belong to the class.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }

    /**
     * Get the lessons for the class.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'class_id');
    }
}
