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

use ArrayAccess;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Contracts\Arrayable;
use JsonSerializable;
use Mogody\Resource\ConditionallyLoadsAttributes;
use Mogody\Resource\DelegatesToResource;
use Mogody\Responsable\Contract\Responsable;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonResource implements ArrayAccess, JsonSerializable, Responsable
{
    use ConditionallyLoadsAttributes;
    use DelegatesToResource;

    /**
     * The resource instance.
     *
     * @var mixed
     */
    public $resource;

    /**
     * The additional data that should be added to the top-level resource array.
     *
     * @var array
     */
    public $with = [];

    /**
     * The additional meta data that should be added to the resource response.
     *
     * Added during response construction by the developer.
     *
     * @var array
     */
    public $additional = [];

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'data';

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create a new resource instance.
     *
     * @param mixed ...$parameters
     * @return static
     */
    public static function make(...$parameters)
    {
        return new static(...$parameters);
    }

    /**
     * Create new anonymous resource collection.
     * @param mixed $resource
     */
    public static function collection($resource): AnonymousResourceCollection
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Resolve the resource to an array.
     *
     * @param PsrRequestInterface $request
     */
    public function resolve(PsrRequestInterface $request = null): array
    {
        $containerRequest = ApplicationContext::getContainer()->get(RequestInterface::class);

        $data = $this->toArray(
            $request = $request ?: $containerRequest
        );

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray(ServerRequestInterface $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        return is_array($this->resource)
            ? $this->resource
            : $this->resource->toArray();
    }

    /**
     * Convert the model instance to JSON.
     */
    public function toJson(int $options = 0): string
    {
        return Json::encode($this->jsonSerialize(), $options);
    }

    /**
     * Get any additional data that should be returned with the resource array.
     */
    public function with(PsrRequestInterface $request): array
    {
        return $this->with;
    }

    /**
     * Add additional meta data to the resource response.
     * @return $this
     */
    public function additional(array $data): self
    {
        $this->additional = $data;

        return $this;
    }

    /**
     * Customize the response for a request.
     */
    public function withResponse(PsrRequestInterface $request, PsrResponseInterface $response): void
    {
    }

    /**
     * Set the string that should wrap the outer-most resource array.
     */
    public static function wrap(string $value): void
    {
        static::$wrap = $value;
    }

    /**
     * Disable wrapping of the outer-most resource array.
     */
    public static function withoutWrapping(): void
    {
        static::$wrap = null;
    }

    /**
     * Transform the resource into an HTTP response.
     */
    public function response(PsrRequestInterface $request): PsrResponseInterface
    {
        $containerRequest = ApplicationContext::getContainer()->get(RequestInterface::class);

        return $this->toResponse(
            $request ?: $containerRequest
        );
    }

    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse(PsrRequestInterface $request): PsrResponseInterface
    {
        return (new ResourceResponse($this))->toResponse($request);
    }

    /**
     * Prepare the resource for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        $request = ApplicationContext::getContainer()->get(\Hyperf\HttpServer\Contract\RequestInterface::class);
        return $this->resolve($request);
    }
}
