<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['profile_id', 'possible_duplicate_id', 'confidence', 'reasons', 'status', 'resolved_by_id', 'resolved_at'])]
class DuplicateFlag extends Model
{
    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'reasons' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function possibleDuplicate()
    {
        return $this->belongsTo(Profile::class, 'possible_duplicate_id');
    }
}
