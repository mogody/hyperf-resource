<?php

declare(strict_types=1);
/**
 * This file is part of mogody/hyperf-resource.
 *
 * @link     https://github.com/mogody/hyperf-resource
 * @document https://github.com/mogody/hyperf-resource/blob/master/README.md
 * @contact  wenghang1228@gmail.com
 * @license  https://github.com/mogody/hyperf-resource/blob/master/LICENSE
 */
namespace Mogody\Resource\Json;

class AnonymousResourceCollection extends ResourceCollection
{
    /**
     * The name of the resource being collected.
     *
     * @var string
     */
    public $collects;

    /**
     * Create a new anonymous resource collection.
     *
     * @param mixed $resource
     */
    public function __construct($resource, string $collects)
    {
        $this->collects = $collects;

        parent::__construct($resource);
    }
}
