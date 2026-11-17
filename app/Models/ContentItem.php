<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentItem extends Model
{
    protected $guarded = [];

    protected $appends = ['public_url'];

    protected function casts(): array
    {
        return ['categories' => 'array', 'metadata' => 'array', 'published_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ProgramRegistration::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ContentAttachment::class);
    }

    public function getPublicUrlAttribute(): string
    {
        if ($this->kind === 'legal' && in_array($this->slug, ['tentang', 'syarat-ketentuan', 'kebijakan-privasi', 'kebijakan-refund', 'aturan-komunitas', 'aksesibilitas'])) {
            return '/'.$this->slug;
        }
        $prefix = ['article' => '/artikel', 'ebook' => '/belajar/ebook', 'podcast' => '/belajar/podcast', 'short' => '/belajar/video-pendek', 'discussion' => '/komunitas/diskusi', 'showcase' => '/komunitas/showcase', 'gallery' => '/komunitas/galeri', 'mentorship' => '/komunitas/mentorship', 'event' => '/program/event', 'bootcamp' => '/program/bootcamp', 'competition' => '/program/kompetisi', 'job' => '/program/karier', 'partner' => '/program/partner', 'help' => '/bantuan', 'legal' => '/kebijakan'];

        return ($prefix[$this->kind] ?? '/artikel').'/'.$this->slug;
    }
}
