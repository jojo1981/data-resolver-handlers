<?php
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
declare(strict_types=1);

namespace Jojo1981\DataResolverHandlers;

use Doctrine\Common\Collections\Collection;
use Exception;
use Traversable;
use function array_values;
use function is_array;

/**
 * @package Jojo1981\DataResolverHandlers
 */
final class DoctrineCollectionSequenceHandler extends AbstractCollectionSequenceHandler
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
     * @throws Exception
     */
    protected function performGetIterator(mixed $data): Traversable
    {
        return $data->getIterator();
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @return Collection
     */
    protected function performFilter(mixed $data, callable $callback): Collection
    {
        return $data->filter($callback);
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @return Collection
     */
    public function performFlatten(mixed $data, callable $callback): Collection
    {
        $result = clone $data;
        $result->clear();

        foreach ($data as $key => $value) {
            $callbackResult = $callback($value, $key);
            if (null === $callbackResult) {
                continue;
            }
            $callbackResult = !is_array($callbackResult) ? [$callbackResult] : array_values($callbackResult);
            foreach ($callbackResult as $item) {
                $this->addToCollection($result, $item);
            }
        }

        return $result;
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
     * @param Collection $target
     * @param mixed $element
     * @return void
     */
    private function addToCollection(Collection $target, mixed $element): void
    {
        if ($element instanceof Collection) {
            foreach ($element as $item) {
                $target->add($item);
            }
        } else {
            $target->add($element);
        }
    }
}
