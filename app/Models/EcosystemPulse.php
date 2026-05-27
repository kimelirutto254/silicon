<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['week_starts_at', 'headline', 'summary', 'metrics'])]
class EcosystemPulse extends Model
{
    protected function casts(): array
    {
        return [
            'week_starts_at' => 'date',
            'metrics' => 'array',
        ];
    }
}
