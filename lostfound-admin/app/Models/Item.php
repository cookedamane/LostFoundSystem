<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public const STATUSES = ['pending', 'listed', 'claimed', 'returned', 'rejected'];

    protected $fillable = ['type', 'name', 'description', 'category_id', 'location', 'date_event', 'status', 'reported_by', 'photo_path', 'admin_note'];
    protected $casts = ['date_event' => 'date'];

    public function category() { return $this->belongsTo(Category::class); }
    public function reporter() { return $this->belongsTo(User::class, 'reported_by'); }
    public function claims() { return $this->hasMany(Claim::class); }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? route('media', $this->photo_path) : null;
    }
}
