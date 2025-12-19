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

namespace tests\Jojo1981\DataResolverHandlers;

use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Jojo1981\DataResolver\Handler\MergeHandlerInterface;
use Jojo1981\DataResolver\Resolver\Context;
use Jojo1981\DataResolverHandlers\DoctrineCollectionMergeHandlerDecorator;
use PHPUnit\Framework\Exception as PhpUnitFrameworkException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @package tests\Jojo1981\DataResolverHandlers
 */
final class DoctrineCollectionMergeHandlerDecoratorTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<MergeHandlerInterface> */
    private ObjectProphecy $mergeHandler;

    /** @var ObjectProphecy<Collection> */
    private ObjectProphecy $collection1;

    /** @var ObjectProphecy<Collection> */
    private ObjectProphecy $collection2;

    /** @var ObjectProphecy<Collection> */
    private ObjectProphecy $collection3;

    /**
     * @return void
     * @throws InterfaceNotFoundException
     * @throws DoubleException
     */
    protected function setUp(): void
    {
        $this->mergeHandler = $this->prophesize(MergeHandlerInterface::class);
        $this->collection1 = $this->prophesize(Collection::class);
        $this->collection2 = $this->prophesize(Collection::class);
        $this->collection3 = $this->prophesize(Collection::class);
    }

    /**
     * @throws ObjectProphecyException
     * @return void
     */
    public function testMergeWithoutCollectionsAndEmptyElementsShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = [];
        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @throws ObjectProphecyException
     * @return void
     */
    public function testMergeWithoutCollectionsAndWithElementsShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = ['propName' => 'text1'];
        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws Exception
     * @throws ObjectProphecyException
     */
    public function testMergeWithOneCollectionsAndOtherDataShouldReturnValueFromDefaultMergeHandler(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->collection1->getIterator()->willReturn(new ArrayIterator())->shouldBeCalledOnce();
        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => 'text1'];
        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws Exception
     * @throws Exception
     * @throws ObjectProphecyException
     */
    public function testMergeWithMultipleCollectionsAndOtherDataShouldReturnValueFromDefaultMergeHandler(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->collection1->getIterator()->willReturn(new ArrayIterator())->shouldBeCalledOnce();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->collection2->getIterator()->willReturn(new ArrayIterator())->shouldBeCalledOnce();
        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => $this->collection2->reveal(), 'prop3' => 'text'];
        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws Exception
     * @throws ObjectProphecyException
     */
    public function testMergeWithMultipleCollectionsAndOtherDataFollowedByCollectionShouldReturnValueFromDefaultMergeHandler(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->collection1->getIterator()->willReturn(new ArrayIterator())->shouldBeCalledOnce();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->collection2->getIterator()->willReturn(new ArrayIterator())->shouldBeCalledOnce();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->collection3->getIterator()->shouldNotBeCalled();
        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => $this->collection2->reveal(), 'prop3' => 'text', 'prop4' => $this->collection3->reveal()];
        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @throws ObjectProphecyException
     * @return void
     */
    public function testMergeWithCollectionsButNotWithSameTypeShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = [
            new ArrayCollection(['text1', 'text2']),
            new ArrayCollection([12, 67]),
        ];
        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws ObjectProphecyException
     * @throws PhpUnitFrameworkException
     * @throws ExpectationFailedException
     */
    public function testMergeWithCollectionsOfSameTypeShouldReturnOneCollectionWithAllElementsInIt(): void
    {
        $collection1 = new ArrayCollection(['item1', 'item2']);
        $collection2 = new ArrayCollection(['item3']);
        $collection3 = new ArrayCollection();
        $collection4 = new ArrayCollection(['item4', 'item5', 'item6']);

        /** @noinspection PhpParamsInspection */
        $this->mergeHandler->merge(Argument::any(), Argument::any())->willReturn(null)->shouldNotBeCalled();

        $elements = ['prop1' => $collection1, 'prop2' => $collection2, 'prop3' => $collection3, 'prop4' => $collection4];
        $context = new Context([]);

        /** @var Collection $result */
        $result = $this->getDoctrineCollectionMergeHandler()->merge($context, $elements);
        self::assertInstanceOf(Collection::class, $result);
        self::assertEquals(6, $result->count());
        self::assertEquals(['item1', 'item2', 'item3', 'item4', 'item5', 'item6'], $result->toArray());
    }

    /**
     * @return DoctrineCollectionMergeHandlerDecorator
     * @throws ObjectProphecyException
     */
    private function getDoctrineCollectionMergeHandler(): DoctrineCollectionMergeHandlerDecorator
    {
        return new DoctrineCollectionMergeHandlerDecorator($this->mergeHandler->reveal());
    }
}
