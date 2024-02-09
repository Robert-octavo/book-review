<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'review',
        'rating',
    ];

    // Add this method to the Review model class to define the relationship between the Review and Book models
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    protected static function booted()
    {
        static::updated(fn (Review $review) => cache()->forget('book:' . $review->book_id));
        static::deleted(fn (Review $review) => cache()->forget('book:' . $review->book_id));
        static::created(fn (Review $review) => cache()->forget('book:' . $review->book_id));
    }
}
