<?php

namespace Spatie\QueryBuilder\Tests;

use Spatie\QueryBuilder\QueryBuilderRequest;

class QueryBuilderRequestTest extends TestCase
{
    /** @test */
    public function it_can_filter_nested_arrays()
    {
        $expected = [
            'info' => [
                'foo' => [
                    'bar' => 1,
                ],
            ],
        ];

        $request = new QueryBuilderRequest([
            'filter' => $expected,
        ]);

        $this->assertEquals($expected, $request->filters()->toArray());
    }

    /** @test */
    public function it_can_get_empty_filters_recursively()
    {
        $request = new QueryBuilderRequest([
            'filter' => [
                'info' => [
                    'foo' => [
                        'bar' => null,
                    ],
                ],
            ],
        ]);

        $expected = [
            'info' => [
                'foo' => [
                    'bar' => '',
                ],
            ],
        ];

        $this->assertEquals($expected, $request->filters()->toArray());
    }

    /** @test */
    public function it_will_map_true_and_false_as_booleans_recursively()
    {
        $request = new QueryBuilderRequest([
            'filter' => [
                'info' => [
                    'foo' => [
                        'bar' => 'true',
                        'baz' => 'false',
                        'bazs' => '0',
                    ],
                ],
            ],
        ]);

        $expected = [
            'info' => [
                'foo' => [
                    'bar' => true,
                    'baz' => false,
                    'bazs' => '0',
                ],
            ],
        ];

        $this->assertEquals($expected, $request->filters()->toArray());
    }

    /** @test */
    public function it_can_get_the_sort_query_param_from_the_request()
    {
        $request = new QueryBuilderRequest([
            'sort' => 'foobar',
        ]);

        $this->assertEquals(['foobar'], $request->sorts()->toArray());
    }

    /** @test */
    public function it_can_get_different_sort_query_parameter_name()
    {
        config(['query-builder.parameters.sort' => 'sorts']);

        $request = new QueryBuilderRequest([
            'sorts' => 'foobar',
        ]);

        $this->assertEquals(['foobar'], $request->sorts()->toArray());
    }

    /** @test */
    public function it_will_return_an_empty_collection_when_no_sort_query_param_is_specified()
    {
        $request = new QueryBuilderRequest();

        $this->assertEmpty($request->sorts());
    }

    /** @test */
    public function it_can_get_multiple_sort_parameters_from_the_request()
    {
        $request = new QueryBuilderRequest([
            'sort' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->sorts());
    }

    /** @test */
    public function it_will_return_an_empty_collection_when_no_sort_query_params_are_specified()
    {
        $request = new QueryBuilderRequest();

        $expected = collect();

        $this->assertEquals($expected, $request->sorts());
    }

    /** @test */
    public function it_can_get_the_filter_query_params_from_the_request()
    {
        $request = new QueryBuilderRequest([
            'filter' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ]);

        $expected = collect([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertEquals($expected, $request->filters());
    }

    /** @test */
    public function it_can_get_different_filter_query_parameter_name()
    {
        config(['query-builder.parameters.filter' => 'filters']);

        $request = new QueryBuilderRequest([
            'filters' => [
                'foo' => 'bar',
                'baz' => 'qux,lex',
            ],
        ]);

        $expected = collect([
            'foo' => 'bar',
            'baz' => ['qux', 'lex'],
        ]);

        $this->assertEquals($expected, $request->filters());
    }

    /** @test */
    public function it_can_get_empty_filters()
    {
        config(['query-builder.parameters.filter' => 'filters']);

        $request = new QueryBuilderRequest([
            'filters' => [
                'foo' => 'bar',
                'baz' => null,
            ],
        ]);

        $expected = collect([
            'foo' => 'bar',
            'baz' => '',
        ]);

        $this->assertEquals($expected, $request->filters());
    }

    /** @test */
    public function it_will_return_an_empty_collection_when_no_filter_query_params_are_specified()
    {
        $request = new QueryBuilderRequest();

        $expected = collect();

        $this->assertEquals($expected, $request->filters());
    }

    /** @test */
    public function it_will_map_true_and_false_as_booleans_when_given_in_a_filter_query_string()
    {
        $request = new QueryBuilderRequest([
            'filter' => [
                'foo' => 'true',
                'bar' => 'false',
                'baz' => '0',
            ],
        ]);

        $expected = collect([
            'foo' => true,
            'bar' => false,
            'baz' => '0',
        ]);

        $this->assertEquals($expected, $request->filters());
    }

    /** @test */
    public function it_will_map_comma_separated_values_as_arrays_when_given_in_a_filter_query_string()
    {
        $request = new QueryBuilderRequest([
            'filter' => [
                'foo' => 'bar,baz',
            ],
        ]);

        $expected = collect(['foo' => ['bar', 'baz']]);

        $this->assertEquals($expected, $request->filters());
    }

    /** @test */
    public function it_will_map_array_in_filter_recursively_when_given_in_a_filter_query_string()
    {
        $request = new QueryBuilderRequest([
            'filter' => [
                'foo' => 'bar,baz',
                'bar' => [
                    'foobar' => 'baz,bar',
                ],
            ],
        ]);

        $expected = collect(['foo' => ['bar', 'baz'], 'bar' => ['foobar' => ['baz', 'bar']]]);

        $this->assertEquals($expected, $request->filters());
    }

    /** @test */
    public function it_will_map_comma_separated_values_as_arrays_when_given_in_a_filter_query_string_and_get_those_by_key()
    {
        $request = new QueryBuilderRequest([
            'filter' => [
                'foo' => 'bar,baz',
            ],
        ]);

        $expected = ['foo' => ['bar', 'baz']];

        $this->assertEquals($expected, $request->filters()->toArray());
    }

    /** @test */
    public function it_can_get_the_include_query_params_from_the_request()
    {
        $request = new QueryBuilderRequest([
            'include' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->includes());
    }

    /** @test */
    public function it_can_get_different_include_query_parameter_name()
    {
        config(['query-builder.parameters.include' => 'includes']);

        $request = new QueryBuilderRequest([
            'includes' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->includes());
    }

    /** @test */
    public function it_will_return_an_empty_collection_when_no_include_query_params_are_specified()
    {
        $request = new QueryBuilderRequest();

        $expected = collect();

        $this->assertEquals($expected, $request->includes());
    }

    /** @test */
    public function it_can_get_requested_fields()
    {
        $request = new QueryBuilderRequest([
            'fields' => [
                'table' => 'name,email',
            ],
        ]);

        $expected = collect(['table' => ['name', 'email']]);

        $this->assertEquals($expected, $request->fields());
    }

    /** @test */
    public function it_can_get_different_fields_parameter_name()
    {
        config(['query-builder.parameters.fields' => 'field']);

        $request = new QueryBuilderRequest([
            'field' => [
                'column' => 'name,email',
            ],
        ]);

        $expected = collect(['column' => ['name', 'email']]);

        $this->assertEquals($expected, $request->fields());
    }

    /** @test */
    public function it_can_get_the_append_query_params_from_the_request()
    {
        $request = new QueryBuilderRequest([
            'append' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->appends());
    }

    /** @test */
    public function it_can_get_different_append_query_parameter_name()
    {
        config(['query-builder.parameters.append' => 'appendit']);

        $request = new QueryBuilderRequest([
            'appendit' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->appends());
    }

    /** @test */
    public function it_will_return_an_empty_collection_when_no_append_query_params_are_specified()
    {
        $request = new QueryBuilderRequest();

        $expected = collect();

        $this->assertEquals($expected, $request->appends());
    }
}
