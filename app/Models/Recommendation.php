<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'profile_id',
    'user_id',
    'rationale',
    'role_badge',
    'company',
    'verified_identity',
    'weight',
    'decay_factor',
    'conflict_flagged',
    'conflict_confirmed',
    'conflict_overridden',
    'conflict_reasons',
])]
class Recommendation extends Model
{
    protected function casts(): array
    {
        return [
            'verified_identity' => 'boolean',
            'conflict_flagged' => 'boolean',
            'conflict_confirmed' => 'boolean',
            'conflict_overridden' => 'boolean',
            'conflict_reasons' => 'array',
            'weight' => 'float',
            'decay_factor' => 'float',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
