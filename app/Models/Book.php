<?php

namespace App\Models;

use GuzzleHttp\Psr7\Query;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Book extends Model
{
    use HasFactory;

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function goodReviews()
    {
        return $this->reviews()->where('rating', '>=', 4);
    }

    public function scopeTitle(Builder $query, string $title): Builder|QueryBuilder
    {
        return $query->where('title', 'like', '%' . $title . '%');
    }

    public function scopeAuthor(Builder $query, string $author): Builder|QueryBuilder
    {
        return $query->where('author', 'like', '%' . $author . '%');
    }

    public function scopePopular(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withReviewsCount()
            // ->having('reviews_count', '>=', 10)
            ->orderBy('reviews_count', 'desc');
    }

    public function scopeUnpopular(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withReviewsCount()
            ->having('reviews_count', '<', 5)
            ->orderBy('reviews_count', 'asc');
    }

    public function scopeReviewed(Builder $query): Builder|QueryBuilder
    {
        return $query->has('reviews');
    }

    public function scopeNotReviewed(Builder $query): Builder|QueryBuilder
    {
        return $query->doesntHave('reviews');
    }

    public function scopeWithReviewsCount(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withCount([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ]);
    }

    public function scopeWithAvgRating(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withAvg([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ], 'rating');
    }

    public function scopeHighestRated(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withAvgRating()
            ->orderBy('reviews_avg_rating', 'desc');
    }

    public function scopeMinReviews(Builder $query, int $minReviews): Builder|QueryBuilder
    {
        return $query->having('reviews_count', '>=', $minReviews);
    }

    private function dateRangeFilter(Builder $query, $from = null, $to = null): void
    {
        if ($from && !$to) {
            $query->where('created_at', '>=', $from);
        } elseif (!$from && $to) {
            $query->where('created_at', '<=', $to);
        } elseif ($from && $to)
            $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopePopularLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonth(), now())
            ->highestRated(now()->subMonth(), now())
            ->minReviews(2);
    }

    public function scopePopularLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonth(6), now())
            ->highestRated(now()->subMonth(6), now())
            ->minReviews(7);
    }

    public function scopeHighestRatedLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonth(), now())
            ->popular(now()->subMonth(), now())
            ->minReviews(2);
    }

    public function scopeHighestRatedLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonth(6), now())
            ->popular(now()->subMonth(6), now())
            ->minReviews(7);
    }

    protected static function booted()
    {
        static::updated(fn (Book $book) => cache()->forget('book:' . $book->book_id));
        static::deleted(fn (Book $book) => cache()->forget('book:' . $book->book_id));
        static::created(fn (Book $book) => cache()->forget('book:' . $book->book_id));
    }
}
