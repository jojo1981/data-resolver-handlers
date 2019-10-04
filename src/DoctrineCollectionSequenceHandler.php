<?php
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\DataResolverHandlers;

use Doctrine\Common\Collections\Collection;

/**
 * @package Jojo1981\DataResolverHandlers
 */
class DoctrineCollectionSequenceHandler extends AbstractCollectionSequenceHandler
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
     * @return \Traversable
     */
    protected function performGetIterator($data): \Traversable
    {
        return $data->getIterator();
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @return Collection
     */
    protected function performFilter($data, callable $callback): Collection
    {
        return $data->filter($callback);
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @return Collection
     */
    public function performFlatten($data, callable $callback): Collection
    {
        $result = clone $data;
        $result->clear();

        foreach ($data as $key => $value) {
            $callbackResult = $callback($value, $key);
            if (null === $callbackResult) {
                continue;
            }
            $callbackResult = !\is_array($callbackResult) ? [$callbackResult] : \array_values($callbackResult);
            foreach ($callbackResult as $item) {
                $this->addToCollection($result, $item);
            }
        }

        return $result;
    }

    /**
     * @param Collection $target
     * @param mixed $element
     * @return void
     */
    private function addToCollection(Collection $target, $element): void
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