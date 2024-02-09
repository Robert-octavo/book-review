<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $title = $request->input('title');
        $filter = $request->input('filter', '');


        $books = Book::when(
            $title,
            fn ($query, $title) => $query->title($title)
        );

        $books = match ($filter) {
            'popular_last_month' => $books->popularlastmonth(),
            'popular_last_6months' => $books->popularlast6months(),
            'highest_rated_last_month' => $books->highestratedlastmonth(),
            'highest_rated_last_6months' => $books->highestratedlast6months(),
            default => $books->latest()->withAvgRating()->withReviewsCount()
        };

        $cacheKey = 'books:' . $filter . ':' . $title;
        $books = cache()->remember($cacheKey, now()->addMinutes(10), fn () => $books->get());

        //$books = $books->get();
        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $cacheKey = 'book:' . $id;
        $book = cache()->remember($cacheKey, now()->addMinutes(10), fn () => Book::with([
            'reviews' => fn ($query) => $query->latest()
        ])->withAvgRating()->withReviewsCount()->findOrFail($id));

        return view('books.show', compact('book'));
        // return view(
        //     'books.show',
        //     [
        //         'book' => $book,
        //         'reviews' => $book->reviews()->latest()
        //     ]
        // );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
