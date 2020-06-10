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
namespace Mogody\Resource;

use Hyperf\Utils\Arr;

trait ConditionallyLoadsAttributes
{
    /**
     * Filter the given data, removing any optional values.
     */
    protected function filter(array $data): array
    {
        $index = -1;

        foreach ($data as $key => $value) {
            ++$index;

            if (is_array($value)) {
                $data[$key] = $this->filter($value);

                continue;
            }

            if (is_numeric($key) && $value instanceof MergeValue) {
                return $this->mergeData(
                    $data,
                    $index,
                    $this->filter($value->data),
                    array_values($value->data) === $value->data
                );
            }

            if ($value instanceof self && is_null($value->resource)) {
                $data[$key] = null;
            }
        }

        return $this->removeMissingValues($data);
    }

    /**
     * Merge the given data in at the given index.
     */
    protected function mergeData(array $data, int $index, array $merge, bool $numericKeys): array
    {
        if ($numericKeys) {
            return $this->removeMissingValues(array_merge(
                array_merge(array_slice($data, 0, $index, true), $merge),
                $this->filter(array_values(array_slice($data, $index + 1, null, true)))
            ));
        }

        return $this->removeMissingValues(array_slice($data, 0, $index, true) +
                $merge +
                $this->filter(array_slice($data, $index + 1, null, true)));
    }

    /**
     * Remove the missing values from the filtered data.
     */
    protected function removeMissingValues(array $data): array
    {
        $numericKeys = true;

        foreach ($data as $key => $value) {
            if (($value instanceof PotentiallyMissing && $value->isMissing()) ||
                ($value instanceof self &&
                $value->resource instanceof PotentiallyMissing &&
                $value->isMissing())) {
                unset($data[$key]);
            } else {
                $numericKeys = $numericKeys && is_numeric($key);
            }
        }

        if (property_exists($this, 'preserveKeys') && $this->preserveKeys === true) {
            return $data;
        }

        return $numericKeys ? array_values($data) : $data;
    }

    /**
     * Retrieve a value based on a given condition.
     *
     * @param mixed $value
     * @param mixed $default
     * @return mixed|\Mogody\Resource\MissingValue
     */
    protected function when(bool $condition, $value, $default = null)
    {
        if ($condition) {
            return value($value);
        }

        return func_num_args() === 3 ? value($default) : new MissingValue();
    }

    /**
     * Merge a value into the array.
     *
     * @param mixed $value
     * @return mixed|\Mogody\Resource\MergeValue
     */
    protected function merge($value)
    {
        return $this->mergeWhen(true, $value);
    }

    /**
     * Merge a value based on a given condition.
     *
     * @param mixed $value
     * @return mixed|\Mogody\Resource\MergeValue
     */
    protected function mergeWhen(bool $condition, $value)
    {
        return $condition ? new MergeValue(value($value)) : new MissingValue();
    }

    /**
     * Merge the given attributes.
     *
     * @return \Mogody\Resource\MergeValue
     */
    protected function attributes(array $attributes): MergeValue
    {
        return new MergeValue(
            Arr::only($this->resource->toArray(), $attributes)
        );
    }

    /**
     * Retrieve a relationship if it has been loaded.
     *
     * @param mixed $value
     * @param mixed $default
     * @return mixed|\Mogody\Resource\MissingValue
     */
    protected function whenLoaded(string $relationship, $value = null, $default = null)
    {
        if (func_num_args() < 3) {
            $default = new MissingValue();
        }

        if (! $this->resource->relationLoaded($relationship)) {
            return value($default);
        }

        if (func_num_args() === 1) {
            return $this->resource->{$relationship};
        }

        if ($this->resource->{$relationship} === null) {
            return;
        }

        return value($value);
    }

    /**
     * Execute a callback if the given pivot table has been loaded.
     *
     * @param mixed $value
     * @param mixed $default
     * @return mixed|\Mogody\Resource\MissingValue
     */
    protected function whenPivotLoaded(string $table, $value, $default = null)
    {
        return $this->whenPivotLoadedAs('pivot', ...func_get_args());
    }

    /**
     * Execute a callback if the given pivot table with a custom accessor has been loaded.
     *
     * @param mixed $value
     * @param mixed $default
     * @return mixed|\Mogody\Resource\MissingValue
     */
    protected function whenPivotLoadedAs(string $accessor, string $table, $value, $default = null)
    {
        if (func_num_args() === 3) {
            $default = new MissingValue();
        }

        return $this->when(
            $this->resource->{$accessor} &&
            ($this->resource->{$accessor} instanceof $table ||
            $this->resource->{$accessor}->getTable() === $table),
            ...[$value, $default]
        );
    }
}
