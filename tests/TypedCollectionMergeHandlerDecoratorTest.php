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

use Jojo1981\DataResolver\Handler\MergeHandlerInterface;
use Jojo1981\DataResolver\Resolver\Context;
use Jojo1981\DataResolverHandlers\TypedCollectionMergeHandlerDecorator;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\Exception\CollectionException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

/**
 * @package tests\Jojo1981\DataResolverHandlers
 */
final class TypedCollectionMergeHandlerDecoratorTest extends TestCase
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
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testMergeWithoutCollectionsAndEmptyElementsShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = [];
        $context = new Context([]);
        $this->mergeHandler->merge(new Context([]), $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testMergeWithoutCollectionsAndWithElementsShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = ['propName' => 'text1'];
        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testMergeWithOneCollectionsAndOtherDataShouldReturnValueFromDefaultMergeHandler(): void
    {
        /** @noinspection PhpParamsInspection */
        $this->collection1->isEqualType(Argument::any())->shouldNotBeCalled();
        $this->collection1->getType()->shouldNotBeCalled();
        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => 'text1'];
        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testMergeWithMultipleCollectionsAndOtherDataShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->isEqualType($this->collection2)->willReturn(true)->shouldBeCalled();
        $this->collection1->getType()->shouldNotBeCalled();
        /** @noinspection PhpParamsInspection */
        $this->collection2->isEqualType(Argument::any())->shouldNotBeCalled();
        $this->collection2->getType()->shouldNotBeCalled();

        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => $this->collection2->reveal(), 'prop3' => 'text'];

        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testMergeWithMultipleCollectionsAndOtherDataFollowedByCollectionShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->isEqualType($this->collection2)->willReturn(true)->shouldBeCalled();
        $this->collection1->isEqualType($this->collection3)->shouldNotBeCalled();
        $this->collection1->getType()->shouldNotBeCalled();
        /** @noinspection PhpParamsInspection */
        $this->collection2->isEqualType(Argument::any())->shouldNotBeCalled();
        $this->collection2->getType()->shouldNotBeCalled();
        /** @noinspection PhpParamsInspection */
        $this->collection3->isEqualType(Argument::any())->shouldNotBeCalled();
        $this->collection3->getType()->shouldNotBeCalled();

        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => $this->collection2->reveal(), 'prop3' => 'text', 'prop4' => $this->collection3->reveal()];

        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testMergeWithCollectionsButNotWithSameTypeShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->isEqualType($this->collection2)->willReturn(true)->shouldBeCalled();
        $this->collection1->isEqualType($this->collection3)->willReturn(false)->shouldBeCalled();
        $this->collection1->getType()->shouldNotBeCalled();
        $this->collection2->getType()->shouldNotBeCalled();
        $this->collection3->getType()->shouldNotBeCalled();

        $elements = [$this->collection1->reveal(), $this->collection2->reveal(), $this->collection3->reveal()];

        $context = new Context([]);
        $this->mergeHandler->merge($context, $elements)->willReturn(null)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($context, $elements);
    }

    /**
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testMergeWithCollectionsOfSameTypeShouldReturnOneCollectionWithAllElementsInIt(): void
    {
        $collection1 = new Collection('string', ['item1', 'item2']);
        $collection2 = new Collection('string', ['item3']);
        $collection3 = new Collection('string');
        $collection4 = new Collection('string', ['item4', 'item5', 'item6']);

        /** @noinspection PhpParamsInspection */
        $this->mergeHandler->merge(Argument::any(), Argument::any())->willReturn(null)->shouldNotBeCalled();

        $elements = ['prop1' => $collection1, 'prop2' => $collection2, 'prop3' => $collection3, 'prop4' => $collection4];

        /** @var Collection $result */
        $result = $this->getTypedCollectionMergeHandler()->merge(new Context([]), $elements);
        self::assertInstanceOf(Collection::class, $result);
        self::assertEquals('string', $result->getType());
        self::assertEquals(6, $result->count());
        self::assertEquals(['item1', 'item2', 'item3', 'item4', 'item5', 'item6'], $result->toArray());
    }

    /**
     * @return TypedCollectionMergeHandlerDecorator
     * @throws ObjectProphecyException
     */
    private function getTypedCollectionMergeHandler(): TypedCollectionMergeHandlerDecorator
    {
        return new TypedCollectionMergeHandlerDecorator($this->mergeHandler->reveal());
    }
}
