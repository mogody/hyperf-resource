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

use Countable;
use Hyperf\HttpServer\Contract\RequestInterface as HyperfRequestInterface;
use Hyperf\Paginator\AbstractPaginator;
use IteratorAggregate;
use Mogody\Resource\CollectsResources;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class ResourceCollection extends JsonResource implements Countable, IteratorAggregate
{
    use CollectsResources;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var \Hyperf\Utils\Collection
     */
    public $collection;

    /**
     * Indicates if all existing request query parameters should be added to pagination links.
     *
     * @var bool
     */
    protected $preserveAllQueryParameters = false;

    /**
     * The query parameters that should be added to the pagination links.
     *
     * @var array
     */
    protected $queryParameters;

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
    }

    /**
     * Indicate that all current query parameters should be appended to pagination links.
     */
    public function preserveQuery(): self
    {
        $this->preserveAllQueryParameters = true;

        return $this;
    }

    /**
     * Specify the query string parameters that should be present on pagination links.
     */
    public function withQuery(array $query): self
    {
        $this->preserveAllQueryParameters = false;

        $this->queryParameters = $query;

        return $this;
    }

    /**
     * Return the count of items in the resource collection.
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Transform the resource into a JSON array.
     */
    public function toArray(HyperfRequestInterface $request): array
    {
        return $this->collection->map->toArray($request)->all();
    }

    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse(PsrRequestInterface $request): PsrResponseInterface
    {
        if ($this->resource instanceof AbstractPaginator) {
            return $this->preparePaginatedResponse($request);
        }

        return parent::toResponse($request);
    }

    /**
     * Create a paginate-aware HTTP response.
     */
    protected function preparePaginatedResponse(PsrRequestInterface $request): PsrResponseInterface
    {
        if ($this->preserveAllQueryParameters) {
            $this->resource->appends($request->query());
        } elseif (! is_null($this->queryParameters)) {
            $this->resource->appends($this->queryParameters);
        }

        return (new PaginatedResourceResponse($this))->toResponse($request);
    }
}
