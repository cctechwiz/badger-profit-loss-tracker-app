<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LicenseKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($licenseKey) {
            $licenseKey->key = (string) Str::uuid();
            $licenseKey->usage_count = 0;
        });
    }

    public function license()
    {
        return $this->belongsTo(\App\Models\License::class);
    }
}
