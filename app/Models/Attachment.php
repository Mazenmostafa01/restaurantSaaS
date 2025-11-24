<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'path', 'filename', 'disk', 'mime_type', 'filesize',
        'position', 'is_primary',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        return \Storage::disk($this->disk)->url($this->path);
    }
}
