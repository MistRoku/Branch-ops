<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'company', 'business_name', 'business_type', 'location', 'country',
        'phone', 'email', 'website', 'register_number', 'vat_number',
        'logo_path', 'use_logo_in_invoice', 'currency', 'base_currency',
        'fiscal_year', 'timezone', 'language', 'date_format',
    ];

    protected $casts = [
        'use_logo_in_invoice' => 'boolean',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(
            [],
            [
                'business_name' => config('app.name', 'BranchOps'),
                'country' => 'South Africa',
                'currency' => 'ZAR',
                'base_currency' => 'ZAR',
                'timezone' => 'Africa/Johannesburg',
                'language' => 'en',
                'date_format' => 'd M Y',
            ]
        );
    }
}
