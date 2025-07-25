<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\QueryBuilder\QueryBuilder;
use function PHPStan\Testing\assertType;

class Book extends Model {
    /**
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}

class Author extends Model {}

assertType('Spatie\QueryBuilder\QueryBuilder<Book>', QueryBuilder::for(Book::class));
assertType('Spatie\QueryBuilder\QueryBuilder<Book>', QueryBuilder::for(Book::query()));
assertType('Spatie\QueryBuilder\QueryBuilder<Author>', QueryBuilder::for((new Book)->author()));
