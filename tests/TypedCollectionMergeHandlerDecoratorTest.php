<?php /** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpStrictTypeCheckingInspection */
/** @noinspection PhpParamsInspection */
declare(strict_types=1);
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
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
use Prophecy\Exception\Doubler\ClassNotFoundException;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * @package tests\Jojo1981\DataResolverHandlers
 */
final class TypedCollectionMergeHandlerDecoratorTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|MergeHandlerInterface */
    private ObjectProphecy $mergeHandler;

    /** @var ObjectProphecy|Context */
    private ObjectProphecy $context;

    /** @var ObjectProphecy|Collection */
    private ObjectProphecy $collection1;

    /** @var ObjectProphecy|Collection */
    private ObjectProphecy $collection2;

    /** @var ObjectProphecy|Collection */
    private ObjectProphecy $collection3;

    /**
     * @throws DoubleException
     * @throws InterfaceNotFoundException
     * @throws ClassNotFoundException
     * @return void
     */
    protected function setUp(): void
    {
        $this->mergeHandler = $this->prophesize(MergeHandlerInterface::class);
        $this->context = $this->prophesize(Context::class);
        $this->context->getData()->shouldNotBeCalled();
        $this->context->getPath()->shouldNotBeCalled();
        $this->context->setPath(Argument::any())->shouldNotBeCalled();
        $this->context->pushPathPart(Argument::any())->shouldNotBeCalled();
        $this->context->popPathPart()->shouldNotBeCalled();
        $this->context->copy()->shouldNotBeCalled();

        $this->collection1 = $this->prophesize(Collection::class);
        $this->collection2 = $this->prophesize(Collection::class);
        $this->collection3 = $this->prophesize(Collection::class);
    }

    /**
     * @test
     *
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function mergeWithoutCollectionsAndEmptyElementsShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = [];
        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function mergeWithoutCollectionsAndWithElementsShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = ['propName' => 'text1'];
        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function mergeWithOneCollectionsAndOtherDataShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->isEqualType(Argument::any())->shouldNotBeCalled();
        $this->collection1->getType()->shouldNotBeCalled();

        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => 'text1'];

        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function mergeWithMultipleCollectionsAndOtherDataShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->isEqualType($this->collection2)->willReturn(true)->shouldBeCalled();
        $this->collection1->getType()->shouldNotBeCalled();
        $this->collection2->isEqualType(Argument::any())->shouldNotBeCalled();
        $this->collection2->getType()->shouldNotBeCalled();

        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => $this->collection2->reveal(), 'prop3' => 'text'];

        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function mergeWithMultipleCollectionsAndOtherDataFollowedByCollectionShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->isEqualType($this->collection2)->willReturn(true)->shouldBeCalled();
        $this->collection1->isEqualType($this->collection3)->shouldNotBeCalled();
        $this->collection1->getType()->shouldNotBeCalled();
        $this->collection2->isEqualType(Argument::any())->shouldNotBeCalled();
        $this->collection2->getType()->shouldNotBeCalled();
        $this->collection3->isEqualType(Argument::any())->shouldNotBeCalled();
        $this->collection3->getType()->shouldNotBeCalled();

        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => $this->collection2->reveal(), 'prop3' => 'text', 'prop4' => $this->collection3->reveal()];

        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @return void
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function mergeWithCollectionsButNotWithSameTypeShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->isEqualType($this->collection2)->willReturn(true)->shouldBeCalled();
        $this->collection1->isEqualType($this->collection3)->willReturn(false)->shouldBeCalled();
        $this->collection1->getType()->shouldNotBeCalled();
        $this->collection2->getType()->shouldNotBeCalled();
        $this->collection3->getType()->shouldNotBeCalled();

        $elements = [$this->collection1->reveal(), $this->collection2->reveal(), $this->collection3->reveal()];

        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getTypedCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws ObjectProphecyException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function mergeWithCollectionsOfSameTypeShouldReturnOneCollectionWithAllElementsInIt(): void
    {
        $collection1 = new Collection('string', ['item1', 'item2']);
        $collection2 = new Collection('string', ['item3']);
        $collection3 = new Collection('string');
        $collection4 = new Collection('string', ['item4', 'item5', 'item6']);

        $this->mergeHandler->merge(Argument::any(), Argument::any())->shouldNotBeCalled();

        $elements = ['prop1' => $collection1, 'prop2' => $collection2, 'prop3' => $collection3, 'prop4' => $collection4];

        /** @var Collection $result */
        $result = $this->getTypedCollectionMergeHandler()->merge($this->context->reveal(), $elements);
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
