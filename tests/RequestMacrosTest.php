<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;

class RequestMacrosTest extends TestCase
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

        $request = new Request([
            'filter' => $expected,
        ]);

        $this->assertEquals($expected, $request->filters()->toArray());
    }

    /** @test */
    public function it_can_get_empty_filters_recursively()
    {
        $request = new Request([
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
        $request = new Request([
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
        $request = new Request([
            'sort' => 'foobar',
        ]);

        $this->assertEquals('foobar', $request->sort());
    }

    /** @test */
    public function it_can_get_different_sort_query_parameter_name()
    {
        config(['query-builder.parameters.sort' => 'sorts']);

        $request = new Request([
            'sorts' => 'foobar',
        ]);

        $this->assertEquals('foobar', $request->sort());
    }

    /** @test */
    public function it_will_return_null_when_no_sort_query_param_is_specified()
    {
        $request = new Request();

        $this->assertNull($request->sort());
    }

    /** @test */
    public function it_will_return_the_given_default_value_when_no_sort_query_param_is_specified()
    {
        $request = new Request();

        $this->assertEquals('foobar', $request->sort('foobar'));
    }

    /** @test */
    public function it_can_get_multiple_sort_parameters_from_the_request()
    {
        $request = new Request([
            'sort' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->sorts());
    }

    /** @test */
    public function it_will_return_an_empty_collection_when_no_sort_query_params_are_specified()
    {
        $request = new Request();

        $expected = collect();

        $this->assertEquals($expected, $request->sorts());
    }

    /** @test */
    public function it_will_return_the_given_default_value_when_no_sort_query_params_are_specified()
    {
        $request = new Request();

        $expected = collect(['foobar']);

        $this->assertEquals($expected, $request->sorts('foobar'));
    }

    /** @test */
    public function it_can_get_the_filter_query_params_from_the_request()
    {
        $request = new Request([
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

        $request = new Request([
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

        $request = new Request([
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
        $request = new Request();

        $expected = collect();

        $this->assertEquals($expected, $request->filters());
    }

    /** @test */
    public function it_will_map_true_and_false_as_booleans_when_given_in_a_filter_query_string()
    {
        $request = new Request([
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
        $request = new Request([
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
        $request = new Request([
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
        $request = new Request([
            'filter' => [
                'foo' => 'bar,baz',
            ],
        ]);

        $expected = ['bar', 'baz'];

        $this->assertEquals($expected, $request->filters('foo'));
    }

    /** @test */
    public function it_can_return_a_specific_filters_value_from_the_filter_query_string()
    {
        $request = new Request([
            'filter' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ]);

        $this->assertEquals('qux', $request->filters('baz'));
    }

    /** @test */
    public function it_can_get_the_include_query_params_from_the_request()
    {
        $request = new Request([
            'include' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->includes());
    }

    /** @test */
    public function it_can_get_different_include_query_parameter_name()
    {
        config(['query-builder.parameters.include' => 'includes']);

        $request = new Request([
            'includes' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->includes());
    }

    /** @test */
    public function it_will_return_an_empty_collection_when_no_include_query_params_are_specified()
    {
        $request = new Request();

        $expected = collect();

        $this->assertEquals($expected, $request->includes());
    }

    /** @test */
    public function it_knows_if_a_specific_include_from_the_query_string_is_required()
    {
        $request = new Request([
            'include' => 'foo,bar',
        ]);

        $this->assertEquals(false, $request->includes('baz'));
        $this->assertEquals(true, $request->includes('bar'));
    }

    /** @test */
    public function it_can_get_requested_fields()
    {
        $request = new Request([
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

        $request = new Request([
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
        $request = new Request([
            'append' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->appends());
    }

    /** @test */
    public function it_can_get_different_append_query_parameter_name()
    {
        config(['query-builder.parameters.append' => 'appendit']);

        $request = new Request([
            'appendit' => 'foo,bar',
        ]);

        $expected = collect(['foo', 'bar']);

        $this->assertEquals($expected, $request->appends());
    }

    /** @test */
    public function it_will_return_an_empty_collection_when_no_append_query_params_are_specified()
    {
        $request = new Request();

        $expected = collect();

        $this->assertEquals($expected, $request->appends());
    }

    /** @test */
    public function it_knows_if_a_specific_append_from_the_query_string_is_required()
    {
        $request = new Request([
            'append' => 'foo,bar',
        ]);

        $this->assertEquals(false, $request->appends('baz'));
        $this->assertEquals(true, $request->appends('bar'));
    }
}
