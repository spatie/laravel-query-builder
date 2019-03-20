<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\Search;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\Enums\SearchModifier;
use Spatie\QueryBuilder\Tests\Models\AppendModel;
use Spatie\QueryBuilder\Exceptions\InvalidSearchQuery;

class SearchTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp(): void
    {
        parent::setUp();

        $this->models = new Collection([
            factory(AppendModel::class)->create(['firstname' => 'Adriaan', 'lastname' => 'Marain']),
            factory(AppendModel::class)->create(['firstname' => 'Alex', 'lastname' => 'Vanderbist']),
            factory(AppendModel::class)->create(['firstname' => 'Brent', 'lastname' => 'Roose']),
            factory(AppendModel::class)->create(['firstname' => 'Freek', 'lastname' => 'van der Herten']),
            factory(AppendModel::class)->create(['firstname' => 'Jef', 'lastname' => 'van der Voort']),
            factory(AppendModel::class)->create(['firstname' => 'Ruben', 'lastname' => 'van Assche']),
            factory(AppendModel::class)->create(['firstname' => 'Sebastian', 'lastname' => 'de Deyne']),
            factory(AppendModel::class)->create(['firstname' => 'Willem', 'lastname' => 'van Bockstal']),
            factory(AppendModel::class)->create(['firstname' => 'Wouter', 'lastname' => 'Brouwers']),
        ]);
    }

    /** @test */
    public function it_can_search_models_and_return_an_empty_collection()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                ['firstname' => 'Axel'],
            ])
            ->allowedSearches('firstname')
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_can_search_models_by_exact_property_with_partial_modifier_by_default()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                ['firstname' => 'Freek'],
            ])
            ->allowedSearches('firstname')
            ->get();

        $this->assertCount(1, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Freek' && $model->lastname == 'van der Herten';
        }));
    }

    /** @test */
    public function it_can_search_models_by_partial_property_with_partial_modifier_by_default()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                ['lastname' => 'van'],
            ])
            ->allowedSearches('lastname')
            ->get();

        $this->assertCount(5, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Alex' && $model->lastname == 'Vanderbist';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Freek' && $model->lastname == 'van der Herten';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Jef' && $model->lastname == 'van der Voort';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Ruben' && $model->lastname == 'van Assche';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Willem' && $model->lastname == 'van Bockstal';
        }));
    }

    /** @test */
    public function it_searches_in_all_allowed_properties_if_not_specified()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                'i',
            ])
            ->allowedSearches(['firstname', 'lastname'])
            ->get();

        $this->assertCount(4, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Adriaan' && $model->lastname == 'Marain';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Alex' && $model->lastname == 'Vanderbist';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Sebastian' && $model->lastname == 'de Deyne';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Willem' && $model->lastname == 'van Bockstal';
        }));
    }

    /** @test */
    public function it_can_search_models_using_multiple_properties_with_partial_modifier_by_default()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                ['firstname' => 'freek,sebastian'],
            ])
            ->allowedSearches('firstname')
            ->get();

        $this->assertCount(2, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Freek' && $model->lastname == 'van der Herten';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Sebastian' && $model->lastname == 'de Deyne';
        }));
    }

    /** @test */
    public function it_guards_against_invalid_searches()
    {
        $this->expectException(InvalidSearchQuery::class);

        $this
            ->createQueryFromSearchRequest([
                ['firstname' => 'Freek'],
            ])
            ->allowedSearches('lastname');
    }

    /** @test */
    public function it_must_search_models_by_exact_property_with_exact_modifier_none_found_if_not_exact_value()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                SearchModifier::EXACT => [
                    'firstname' => 'A',
                ],
            ])
            ->allowedSearches('firstname')
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_must_search_models_by_exact_property_with_exact_modifier_found_if_exact_value()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                SearchModifier::EXACT => [
                    'firstname' => 'Alex',
                ],
            ])
            ->allowedSearches('firstname')
            ->get();

        $this->assertCount(1, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Alex' && $model->lastname == 'Vanderbist';
        }));
    }

    /** @test */
    public function it_searches_with_fixed_search_driver_regardless_of_syntax_not_found_because_not_exact()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                [
                    'firstname' => 'Al',
                ],
            ])
            ->allowedSearches([
                Search::exact('firstname'),
            ])
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_searches_with_fixed_search_driver_regardless_of_syntax_found_because_exact()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                [
                    'firstname' => 'Alex',
                ],
            ])
            ->allowedSearches([
                Search::exact('firstname'),
            ])
            ->get();

        $this->assertCount(1, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Alex' && $model->lastname == 'Vanderbist';
        }));
    }

    /** @test */
    public function it_can_search_models_by_property_with_begins_modifier()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                SearchModifier::BEGINS => [
                    'firstname' => 'W',
                ],
            ])
            ->allowedSearches('firstname')
            ->get();

        $this->assertCount(2, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Willem' && $model->lastname == 'van Bockstal';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Wouter' && $model->lastname == 'Brouwers';
        }));
    }

    /** @test */
    public function it_can_search_models_by_property_with_ends_modifier()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                SearchModifier::ENDS => [
                    'lastname' => 'e',
                ],
            ])
            ->allowedSearches('lastname')
            ->get();

        $this->assertCount(3, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Brent' && $model->lastname == 'Roose';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Ruben' && $model->lastname == 'van Assche';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Sebastian' && $model->lastname == 'de Deyne';
        }));
    }

    /** @test */
    public function it_can_search_by_multiple_properties_at_once()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                [
                    'firstname' => 'Bre',
                    'lastname' => 'de',
                ],
            ])
            ->allowedSearches(['firstname', 'lastname'])
            ->get();

        $this->assertCount(5, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Brent' && $model->lastname == 'Roose';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Alex' && $model->lastname == 'Vanderbist';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Freek' && $model->lastname == 'van der Herten';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Jef' && $model->lastname == 'van der Voort';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Sebastian' && $model->lastname == 'de Deyne';
        }));
    }

    /** @test */
    public function it_can_search_by_splitting_the_property_with_split_modifier()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                SearchModifier::SPLIT => [
                    'firstname' => 'aa ee ii oo uu',
                    'lastname' => 'aa ee ii oo uu',
                ],
            ])
            ->allowedSearches(['firstname', 'lastname'])
            ->get();

        $this->assertCount(4, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Adriaan' && $model->lastname == 'Marain';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Brent' && $model->lastname == 'Roose';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Freek' && $model->lastname == 'van der Herten';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Jef' && $model->lastname == 'van der Voort';
        }));
    }

    /** @test */
    public function it_can_search_by_splitting_the_property_with_split_begins_modifier()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                SearchModifier::SPLIT_BEGINS => [
                    'firstname' => 'A B W',
                ],
            ])
            ->allowedSearches('firstname')
            ->get();

        $this->assertCount(5, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Adriaan' && $model->lastname == 'Marain';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Alex' && $model->lastname == 'Vanderbist';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Brent' && $model->lastname == 'Roose';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Willem' && $model->lastname == 'van Bockstal';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Wouter' && $model->lastname == 'Brouwers';
        }));
    }

    /** @test */
    public function it_can_search_by_splitting_the_property_with_split_ends_modifier()
    {
        $models = $this
            ->createQueryFromSearchRequest([
                SearchModifier::SPLIT_ENDS => [
                    'lastname' => 'e n',
                ],
            ])
            ->allowedSearches('lastname')
            ->get();

        $this->assertCount(5, $models);
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Adriaan' && $model->lastname == 'Marain';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Brent' && $model->lastname == 'Roose';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Freek' && $model->lastname == 'van der Herten';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Ruben' && $model->lastname == 'van Assche';
        }));
        $this->assertTrue($models->contains(function ($model) {
            return $model->firstname = 'Sebastian' && $model->lastname == 'de Deyne';
        }));
    }

    protected function createQueryFromSearchRequest(array $searches): QueryBuilder
    {
        $searchParameter = config('query-builder.parameters.search');

        $search = collect($searches)
            ->mapToGroups(function ($item, $key) use ($searchParameter) {
                if (is_int($key) || $key == SearchModifier::PARTIAL) {
                    return [$searchParameter => $item];
                }

                return [$searchParameter.':'.$key => $item];
            })->map(function ($item) {
                return $item[0];
            })
            ->toArray();

        $request = new Request($search);

        return QueryBuilder::for(AppendModel::class, $request);
    }
}
