<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\DataResolverHandlers;

use Jojo1981\PhpTypes\AbstractType;
use Jojo1981\PhpTypes\Exception\TypeException;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\Exception\CollectionException;
use RuntimeException;
use Traversable;
use function array_push;
use function end;
use function is_array;

/**
 * @package Jojo1981\DataResolverHandlers
 */
final class TypedCollectionSequenceHandler extends AbstractCollectionSequenceHandler
{
    /**
     * @return string
     */
    protected function getSupportedType(): string
    {
        return Collection::class;
    }

    /**
     * @param mixed|Collection $data
     * @return Traversable
     */
    protected function performGetIterator(mixed $data): Traversable
    {
        return $data->getIterator();
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @return Collection
     * @throws CollectionException
     */
    protected function performFilter(mixed $data, callable $callback): Collection
    {
        return $data->filter($callback);
    }

    /**
     * @param mixed|Collection $data
     * @return int
     */
    protected function performCount(mixed $data): int
    {
        return $data->count();
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @return Collection
     * @throws TypeException
     * @throws RuntimeException
     * @throws CollectionException
     */
    protected function performFlatten(mixed $data, callable $callback): Collection
    {
        $elements = [];
        $type = $data instanceof Collection ? $data->getType() : null;
        foreach ($data as $key => $value) {
            $callbackResult = $callback($value, $key);
            if ($callbackResult instanceof Collection) {
                $type = $type ?? $callbackResult->getType();
                $callbackResult = $callbackResult->toArray();
            }

            $callbackResult = is_array($callbackResult) ? $callbackResult : [$callbackResult];
            if (!empty($callbackResult)) {
                $type = (AbstractType::createFromValue(end($callbackResult)))->getName();
                array_push($elements, ...$callbackResult);
            }
        }

        return new Collection($type, $elements);
    }
}
