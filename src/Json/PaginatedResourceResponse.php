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

use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Context;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class PaginatedResourceResponse extends ResourceResponse
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse(PsrRequestInterface $request): PsrResponseInterface
    {
        $response = Context::get(PsrResponseInterface::class);
        $response = $response->withStatus($this->calculateStatus())
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream(Json::encode($this->wrap(
                $this->resource->resolve($request),
                array_merge_recursive(
                    $this->paginationInformation($request),
                    $this->resource->with($request),
                    $this->resource->additional
                )
            ))));

        return tap($response, function ($response) use ($request) {
            $response->original = $this->resource->resource->map(function ($item) {
                return $item->resource;
            });

            $this->resource->withResponse($request, $response);
        });
    }

    /**
     * Add the pagination information to the response.
     */
    protected function paginationInformation(PsrRequestInterface $request): array
    {
        $paginated = $this->resource->resource->toArray();

        return [
            'links' => $this->paginationLinks($paginated),
            'meta' => $this->meta($paginated),
        ];
    }

    /**
     * Get the pagination links for the response.
     */
    protected function paginationLinks(array $paginated): array
    {
        return [
            'first' => $paginated['first_page_url'] ?? null,
            'last' => $paginated['last_page_url'] ?? null,
            'prev' => $paginated['prev_page_url'] ?? null,
            'next' => $paginated['next_page_url'] ?? null,
        ];
    }

    /**
     * Gather the meta data for the response.
     */
    protected function meta(array $paginated): array
    {
        return Arr::except($paginated, [
            'data',
            'first_page_url',
            'last_page_url',
            'prev_page_url',
            'next_page_url',
        ]);
    }
}
