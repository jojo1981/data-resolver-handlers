<?php
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace tests\Jojo1981\DataResolverHandlers;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Jojo1981\DataResolver\Handler\MergeHandlerInterface;
use Jojo1981\DataResolver\Resolver\Context;
use Jojo1981\DataResolverHandlers\DoctrineCollectionMergeHandlerDecorator;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Exception\Doubler\ClassNotFoundException;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\Prophecy\ObjectProphecy;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * @package tests\Jojo1981\DataResolverHandlers
 */
class DoctrineCollectionMergeHandlerDecoratorTest extends TestCase
{
    /** @var ObjectProphecy|MergeHandlerInterface */
    private $mergeHandler;

    /** @var ObjectProphecy|Context */
    private $context;

    /** @var ObjectProphecy|Collection */
    private $collection1;

    /** @var ObjectProphecy|Collection */
    private $collection2;

    /** @var ObjectProphecy|Collection */
    private $collection3;

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
     * @throws ObjectProphecyException
     * @return void
     */
    public function mergeWithoutCollectionsAndEmptyElementsShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = [];
        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @throws ObjectProphecyException
     * @return void
     */
    public function mergeWithoutCollectionsAndWithElementsShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = ['propName' => 'text1'];
        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @throws ObjectProphecyException
     * @return void
     */
    public function mergeWithOneCollectionsAndOtherDataShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->getIterator()->willReturn(new \ArrayIterator())->shouldBeCalledOnce();
        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => 'text1'];

        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @throws ObjectProphecyException
     * @return void
     */
    public function mergeWithMultipleCollectionsAndOtherDataShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->getIterator()->willReturn(new \ArrayIterator())->shouldBeCalledOnce();
        $this->collection2->getIterator()->willReturn(new \ArrayIterator())->shouldBeCalledOnce();
        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => $this->collection2->reveal(), 'prop3' => 'text'];

        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @throws ObjectProphecyException
     * @return void
     */
    public function mergeWithMultipleCollectionsAndOtherDataFollowedByCollectionShouldReturnValueFromDefaultMergeHandler(): void
    {
        $this->collection1->getIterator()->willReturn(new \ArrayIterator())->shouldBeCalledOnce();
        $this->collection2->getIterator()->willReturn(new \ArrayIterator())->shouldBeCalledOnce();
        $this->collection3->getIterator()->shouldNotBeCalled();
        $elements = ['prop1' => $this->collection1->reveal(), 'prop2' => $this->collection2->reveal(), 'prop3' => 'text', 'prop4' => $this->collection3->reveal()];

        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @throws ObjectProphecyException
     * @return void
     */
    public function mergeWithCollectionsButNotWithSameTypeShouldReturnValueFromDefaultMergeHandler(): void
    {
        $elements = [
            new ArrayCollection(['text1', 'text2']),
            new ArrayCollection([12, 67]),
        ];

        $this->mergeHandler->merge($this->context, $elements)->shouldBeCalledOnce();
        $this->getDoctrineCollectionMergeHandler()->merge($this->context->reveal(), $elements);
    }

    /**
     * @test
     *
     * @throws ObjectProphecyException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @return void
     */
    public function mergeWithCollectionsOfSameTypeShouldReturnOneCollectionWithAllElementsInIt(): void
    {
        $collection1 = new ArrayCollection(['item1', 'item2']);
        $collection2 = new ArrayCollection(['item3']);
        $collection3 = new ArrayCollection();
        $collection4 = new ArrayCollection(['item4', 'item5', 'item6']);

        $this->mergeHandler->merge(Argument::any(), Argument::any())->shouldNotBeCalled();

        $elements = ['prop1' => $collection1, 'prop2' => $collection2, 'prop3' => $collection3, 'prop4' => $collection4];

        /** @var Collection $result */
        $result = $this->getDoctrineCollectionMergeHandler()->merge($this->context->reveal(), $elements);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(6, $result->count());
        $this->assertEquals(['item1', 'item2', 'item3', 'item4', 'item5', 'item6'], $result->toArray());
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