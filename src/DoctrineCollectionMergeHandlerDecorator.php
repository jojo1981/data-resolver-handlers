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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Jojo1981\DataResolver\Handler\MergeHandlerInterface;
use Jojo1981\DataResolver\Resolver\Context;

/**
 * @package Jojo1981\DataResolverHandlers
 */
class DoctrineCollectionMergeHandlerDecorator implements MergeHandlerInterface
{
    /** @var MergeHandlerInterface */
    private $mergeHandler;

    /**
     * @param MergeHandlerInterface $mergeHandler
     */
    public function __construct(MergeHandlerInterface $mergeHandler)
    {
        $this->mergeHandler = $mergeHandler;
    }

    /**
     * @param Context $context
     * @param array $elements
     * @return mixed
     */
    public function merge(Context $context, array $elements)
    {
        if (!empty($elements) && $this->areAllElementsOfTypeCollectionAndAreAllValuesOfSameType($elements)) {
            return $this->mergeCollections($elements);
        }

        return $this->mergeHandler->merge($context, $elements);
    }

    /**
     * @param Collection[] $collections
     * @return ArrayCollection
     */
    private function mergeCollections(array $collections): ArrayCollection
    {
        $elements = [];
        foreach ($collections as $collection) {
            if ($collection->isEmpty()) {
                continue;
            }
            \array_push($elements, ...$collection->getValues());
        }

        return new ArrayCollection($elements);
    }

    /**
     * @param array $elements
     * @return bool
     */
    private function areAllElementsOfTypeCollectionAndAreAllValuesOfSameType(array $elements): bool
    {
        $type = null;
        foreach ($elements as $element) {
            if (!$element instanceof Collection) {
                return false;
            }
            foreach ($element as $item) {
                if (null === $type) {
                    $type = $this->getType($item);
                    continue;
                }
                if ($this->getType($item) !== $type) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param mixed $item
     * @return string
     */
    private function getType($item): string
    {
        return \is_object($item) ? \get_class($item) : \gettype($item);
    }
}