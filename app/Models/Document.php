<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'disk',
        'path',
        'type',
        'description',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    const TYPE_IMAGE = 'image';
    const TYPE_SPEC_SHEET = 'spec_sheet';
    const TYPE_INVOICE = 'invoice';
    const TYPE_DELIVERY_NOTE = 'delivery_note';
    const TYPE_STOCK_TAKE_REPORT = 'stock_take_report';
    const TYPE_OTHER = 'other';

    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
    ];

    const MAX_FILE_SIZES = [
        'image' => 5 * 1024 * 1024, // 5MB
        'spec_sheet' => 10 * 1024 * 1024, // 10MB
        'invoice' => 10 * 1024 * 1024,
        'delivery_note' => 10 * 1024 * 1024,
        'stock_take_report' => 10 * 1024 * 1024,
        'other' => 10 * 1024 * 1024,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getUrlAttribute(): string
    {
        return \Storage::disk($this->disk)->url($this->path);
    }
}
