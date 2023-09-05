<?php

namespace Crane\Router;

class Route
{

    const OPTIONAL_REGEX = '(\?(?:.*?=(?:.*?&)?)*(.*?=(?:.*?))?)?';

    const GET = 'GET';
    const POST = 'POST';
    const ALL = 'ALL';

    public $path;
    public $handlers = [];

    public $matches = [];

    public function __construct($path)
    {
        $this->path = $path;
    }
}
