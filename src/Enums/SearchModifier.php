<?php

namespace Spatie\QueryBuilder\Enums;

class SearchModifier
{
    public const BEGINS = 'begins';

    public const ENDS = 'ends';

    public const EXACT = 'exact';

    public const PARTIAL = 'partial';

    public const SPLIT = 'split';

    public const SPLIT_BEGINS = 'split:begins';

    public const SPLIT_ENDS = 'split:ends';
}
