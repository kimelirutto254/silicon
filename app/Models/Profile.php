<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'slug',
    'name',
    'bio',
    'platform_link',
    'geographies',
    'topics',
    'formats',
    'status',
    'provenance',
    'submitted_by_id',
    'approved_by_id',
    'approved_at',
    'company',
    'search_vector',
    'trust_score',
    'confidence_level',
    'credibility_summary',
    'summary_generated_at',
    'data_quality_score',
    'data_quality_notes',
])]
class Profile extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'geographies' => 'array',
            'topics' => 'array',
            'formats' => 'array',
            'search_vector' => 'array',
            'trust_score' => 'float',
            'approved_at' => 'datetime',
            'summary_generated_at' => 'datetime',
            'data_quality_notes' => 'array',
        ];
    }

    public function recommendations()
    {
        return $this->hasMany(Recommendation::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function duplicateFlags()
    {
        return $this->hasMany(DuplicateFlag::class);
    }
}
