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

use Jojo1981\DataResolver\Handler\MergeHandlerInterface;
use Jojo1981\DataResolver\Resolver\Context;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\Exception\CollectionException;
use RuntimeException;
use function array_shift;

/**
 * @package Jojo1981\DataResolverHandlers
 */
final class TypedCollectionMergeHandlerDecorator implements MergeHandlerInterface
{
    /** @var MergeHandlerInterface */
    private MergeHandlerInterface $mergeHandler;

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
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function merge(Context $context, array $elements)
    {
        if (!empty($elements) && $this->areAllElementsOfTypeCollectionAndSameType($elements)) {
            return Collection::createFromCollections($this->getTypeFromElements($elements), $elements);
        }

        return $this->mergeHandler->merge($context, $elements);
    }

    /**
     * @param array $elements
     * @return string
     */
    private function getTypeFromElements(array $elements): string
    {
        /** @var Collection $element */
        $element = array_shift($elements);

        return $element->getType();
    }

    /**
     * @param array $elements
     * @return bool
     */
    private function areAllElementsOfTypeCollectionAndSameType(array $elements): bool
    {
        /** @var null|Collection $firstCollection */
        $firstCollection = null;
        foreach ($elements as $element) {
            if (!$element instanceof Collection) {
                return false;
            }
            if (null === $firstCollection) {
                $firstCollection = $element;
            } else if (!$firstCollection->isEqualType($element)) {
                return false;
            }
        }

        return true;
    }
}
