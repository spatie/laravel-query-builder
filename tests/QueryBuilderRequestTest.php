<?php

use Spatie\QueryBuilder\QueryBuilderRequest;

uses(TestCase::class);

it('can filter nested arrays', function () {
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
});

it('can get empty filters recursively', function () {
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
});

it('will map true and false as booleans recursively', function () {
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
});

it('can get the sort query param from the request', function () {
    $request = new QueryBuilderRequest([
        'sort' => 'foobar',
    ]);

    $this->assertEquals(['foobar'], $request->sorts()->toArray());
});

it('can get the sort query param from the request body', function () {
    config(['query-builder.request_data_source' => 'body']);

    $request = new QueryBuilderRequest([], [
        'sort' => 'foobar',
    ], [], [], [], ['REQUEST_METHOD' => 'POST']);

    $this->assertEquals(['foobar'], $request->sorts()->toArray());
});

it('can get different sort query parameter name', function () {
    config(['query-builder.parameters.sort' => 'sorts']);

    $request = new QueryBuilderRequest([
        'sorts' => 'foobar',
    ]);

    $this->assertEquals(['foobar'], $request->sorts()->toArray());
});

it('will return an empty collection when no sort query param is specified', function () {
    $request = new QueryBuilderRequest();

    $this->assertEmpty($request->sorts());
});

it('can get multiple sort parameters from the request', function () {
    $request = new QueryBuilderRequest([
        'sort' => 'foo,bar',
    ]);

    $expected = collect(['foo', 'bar']);

    $this->assertEquals($expected, $request->sorts());
});

it('will return an empty collection when no sort query params are specified', function () {
    $request = new QueryBuilderRequest();

    $expected = collect();

    $this->assertEquals($expected, $request->sorts());
});

it('can get the filter query params from the request', function () {
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
});

it('can get the filter query params from the request body', function () {
    config(['query-builder.request_data_source' => 'body']);

    $request = new QueryBuilderRequest([], [
            'filter' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

    $expected = collect([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

    $this->assertEquals($expected, $request->filters());
});

it('can get different filter query parameter name', function () {
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
});

it('can get empty filters', function () {
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
});

it('will return an empty collection when no filter query params are specified', function () {
    $request = new QueryBuilderRequest();

    $expected = collect();

    $this->assertEquals($expected, $request->filters());
});

it('will map true and false as booleans when given in a filter query string', function () {
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
});

it('will map comma separated values as arrays when given in a filter query string', function () {
    $request = new QueryBuilderRequest([
        'filter' => [
            'foo' => 'bar,baz',
        ],
    ]);

    $expected = collect(['foo' => ['bar', 'baz']]);

    $this->assertEquals($expected, $request->filters());
});

it('will map array in filter recursively when given in a filter query string', function () {
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
});

it('will map comma separated values as arrays when given in a filter query string and get those by key', function () {
    $request = new QueryBuilderRequest([
        'filter' => [
            'foo' => 'bar,baz',
        ],
    ]);

    $expected = ['foo' => ['bar', 'baz']];

    $this->assertEquals($expected, $request->filters()->toArray());
});

it('can get the include query params from the request', function () {
    $request = new QueryBuilderRequest([
        'include' => 'foo,bar',
    ]);

    $expected = collect(['foo', 'bar']);

    $this->assertEquals($expected, $request->includes());
});

it('can get the include from the request body', function () {
    config(['query-builder.request_data_source' => 'body']);

    $request = new QueryBuilderRequest([], [
        'include' => 'foo,bar',
    ], [], [], [], ['REQUEST_METHOD' => 'POST']);

    $expected = collect(['foo', 'bar']);

    $this->assertEquals($expected, $request->includes());
});

it('can get different include query parameter name', function () {
    config(['query-builder.parameters.include' => 'includes']);

    $request = new QueryBuilderRequest([
        'includes' => 'foo,bar',
    ]);

    $expected = collect(['foo', 'bar']);

    $this->assertEquals($expected, $request->includes());
});

it('will return an empty collection when no include query params are specified', function () {
    $request = new QueryBuilderRequest();

    $expected = collect();

    $this->assertEquals($expected, $request->includes());
});

it('can get requested fields', function () {
    $request = new QueryBuilderRequest([
        'fields' => [
            'table' => 'name,email',
        ],
    ]);

    $expected = collect(['table' => ['name', 'email']]);

    $this->assertEquals($expected, $request->fields());
});

it('can get requested fields from the request body', function () {
    config(['query-builder.request_data_source' => 'body']);

    $request = new QueryBuilderRequest([], [
        'fields' => [
            'table' => 'name,email',
        ],
    ], [], [], [], ['REQUEST_METHOD' => 'POST']);

    $expected = collect(['table' => ['name', 'email']]);

    $this->assertEquals($expected, $request->fields());
});

it('can get different fields parameter name', function () {
    config(['query-builder.parameters.fields' => 'field']);

    $request = new QueryBuilderRequest([
        'field' => [
            'column' => 'name,email',
        ],
    ]);

    $expected = collect(['column' => ['name', 'email']]);

    $this->assertEquals($expected, $request->fields());
});

it('can get the append query params from the request', function () {
    $request = new QueryBuilderRequest([
        'append' => 'foo,bar',
    ]);

    $expected = collect(['foo', 'bar']);

    $this->assertEquals($expected, $request->appends());
});

it('can get different append query parameter name', function () {
    config(['query-builder.parameters.append' => 'appendit']);

    $request = new QueryBuilderRequest([
        'appendit' => 'foo,bar',
    ]);

    $expected = collect(['foo', 'bar']);

    $this->assertEquals($expected, $request->appends());
});

it('will return an empty collection when no append query params are specified', function () {
    $request = new QueryBuilderRequest();

    $expected = collect();

    $this->assertEquals($expected, $request->appends());
});

it('can get the append query params from the request body', function () {
    config(['query-builder.request_data_source' => 'body']);

    $request = new QueryBuilderRequest([], [
        'append' => 'foo,bar',
    ], [], [], [], ['REQUEST_METHOD' => 'POST']);

    $expected = collect(['foo', 'bar']);

    $this->assertEquals($expected, $request->appends());
});

it('takes custom delimiters for splitting request parameters', function () {
    $request = new QueryBuilderRequest([
        'filter' => [
            'foo' => 'values, contain, commas|and are split on vertical| lines',
        ],
    ]);

    QueryBuilderRequest::setArrayValueDelimiter('|');

    $expected = ['foo' => ['values, contain, commas', 'and are split on vertical', ' lines']];

    $this->assertEquals($expected, $request->filters()->toArray());
});

it('adds any appends as they come from the request', function () {
    $request = new QueryBuilderRequest([
        'append' => 'aCamelCaseAppend,anotherappend',
    ]);

    $expected = collect(['aCamelCaseAppend', 'anotherappend']);

    $this->assertEquals($expected, $request->appends());
});
