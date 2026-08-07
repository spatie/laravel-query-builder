<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Enums\FilterOperator;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Includes\IncludeInterface;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Sorts\Sort;

use function PHPStan\Testing\assertType;

class Book extends Model
{
    /**
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}

class Author extends Model {}

/**
 * A filter written against one specific model.
 *
 * @implements Filter<Book>
 */
class BooksPublishedInFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->whereYear('published_at', $value);
    }
}

/**
 * A filter written against any model.
 *
 * @implements Filter<Model>
 */
class NotNullFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->whereNotNull($property);
    }
}

/**
 * @implements Sort<Book>
 */
class BooksByPopularitySort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy('popularity', $descending ? 'desc' : 'asc');
    }
}

/**
 * @implements IncludeInterface<Book>
 */
class BooksWithAuthorInclude implements IncludeInterface
{
    public function __invoke(Builder $query, string $include): void
    {
        $query->with('author');
    }
}

assertType('Spatie\QueryBuilder\QueryBuilder<Book>', QueryBuilder::for(Book::class));
assertType('Spatie\QueryBuilder\QueryBuilder<Book>', QueryBuilder::for(Book::query()));
assertType('Spatie\QueryBuilder\QueryBuilder<Author>', QueryBuilder::for((new Book)->author()));

assertType('Spatie\QueryBuilder\QueryBuilder<Book>', QueryBuilder::for(Book::class)
    ->allowedFields('title', 'author.name')
    ->allowedFilters(
        'title',
        AllowedFilter::exact('author_id'),
        AllowedFilter::scope('published'),
        AllowedFilter::trashed(),
        AllowedFilter::operator('pages', FilterOperator::GREATER_THAN),
        AllowedFilter::callback('title_length', fn (Builder $query, mixed $value) => $query->whereRaw('length(title) = ?', [$value])),
        AllowedFilter::custom('published_in', new BooksPublishedInFilter),
        AllowedFilter::custom('subtitle', new NotNullFilter),
        AllowedFilter::groupOr('search', [
            AllowedFilter::partial('title'),
            AllowedFilter::partial('author.name'),
        ]),
    )
    ->allowedSorts(
        'title',
        AllowedSort::field('published', 'published_at'),
        AllowedSort::callback('random', fn (Builder $query, bool $descending) => $query->inRandomOrder()),
        AllowedSort::custom('popularity', new BooksByPopularitySort),
    )
    ->allowedIncludes(
        'author',
        AllowedInclude::count('reviewsCount'),
        AllowedInclude::callback('recentReviews', fn (Builder $query) => $query->latest()),
        AllowedInclude::custom('authorDetails', new BooksWithAuthorInclude),
    ));
