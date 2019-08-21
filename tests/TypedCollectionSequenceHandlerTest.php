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

use Jojo1981\DataResolver\Handler\Exception\HandlerException;
use Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\CollectionIterator;
use Jojo1981\TypedCollection\Exception\CollectionException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Doubler\ClassNotFoundException;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\Prophecy\ObjectProphecy;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * @package tests\Jojo1981\DataResolverHandlers
 */
class TypedCollectionSequenceHandlerTest extends TestCase
{
    /** @var ObjectProphecy|Collection */
    private $originalCollection;

    /** @var ObjectProphecy|Collection */
    private $returnedCollection;

    /**
     * @return void
     * @throws DoubleException
     * @throws InterfaceNotFoundException
     * @throws ClassNotFoundException
     */
    protected function setUp(): void
    {
        $this->originalCollection = $this->prophesize(Collection::class);
        $this->returnedCollection = $this->prophesize(Collection::class);
    }

    /**
     * @test
     *
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @return void
     */
    public function supportShouldReturnFalseForDataWhichIsNotACollection(): void
    {
        $this->assertFalse($this->getTypedCollectionSequenceHandler()->supports([]));
        $this->assertFalse($this->getTypedCollectionSequenceHandler()->supports(['key' => 'value']));
        $this->assertFalse($this->getTypedCollectionSequenceHandler()->supports(
            new \ArrayIterator(['key' => 'value']))
        );
    }

    /**
     * @test
     *
     * @throws ObjectProphecyException
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @return void
     */
    public function supportShouldReturnTrueForCollection(): void
    {
        $this->assertTrue($this->getTypedCollectionSequenceHandler()->supports($this->originalCollection->reveal()));
    }

    /**
     * @test
     *
     * @throws HandlerException
     * @return void
     */
    public function getIteratorShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler` can only handle instances of ' .
            '`Jojo1981\TypedCollection\Collection`. Illegal invocation of method `getIterator`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getTypedCollectionSequenceHandler()->getIterator('Not supported data');
    }

    /**
     * @test
     *
     * @throws CollectionException
     * @throws HandlerException
     * @return void
     */
    public function filterShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler` can only handle instances of ' .
            '`Jojo1981\TypedCollection\Collection`. Illegal invocation of method `filter`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getTypedCollectionSequenceHandler()->filter('Not supported data', function () {});
    }

    /**
     * @test
     *
     * @throws CollectionException
     * @throws HandlerException
     * @return void
     */
    public function flattenShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler` can only handle instances of ' .
            '`Jojo1981\TypedCollection\Collection`. Illegal invocation of method `flatten`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getTypedCollectionSequenceHandler()->flatten('Not supported data', function () {});
    }

    /**
     * @test
     *
     * @throws HandlerException
     * @throws InvalidArgumentException
     * @throws ObjectProphecyException
     * @throws ExpectationFailedException
     * @return void
     */
    public function getIteratorShouldReturnTheIteratorFromTheCollection(): void
    {
        $iterator = new CollectionIterator(new \ArrayIterator([]));
        $this->originalCollection->getIterator()->willReturn($iterator)->shouldBeCalledOnce();
        $this->assertSame(
            $iterator,
            $this->getTypedCollectionSequenceHandler()->getIterator($this->originalCollection->reveal())
        );
    }

    /**
     * @test
     *
     * @throws CollectionException
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws InvalidArgumentException
     * @return void
     */
    public function filterShouldReturnTheFilterResultOfTheCollection(): void
    {
        $elements = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4'];
        $originalCollection = new Collection('string', $elements);

        // assert initial data
        $this->assertEquals(4, $originalCollection->count());
        $this->assertEquals(\array_values($elements), $originalCollection->toArray());

        // test filter
        $calledTimes = 0;
        $expectedCallArguments = [['value1', 0], ['value2', 1], ['value3', 2], ['value4', 3]];
        $callback = function (string $value, int $index) use (&$calledTimes, $expectedCallArguments): bool {
            $this->assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return 'value2' !== $value && 2 !== $index;
        };

        $filteredCollection = $this->getTypedCollectionSequenceHandler()->filter($originalCollection, $callback);
        $this->assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        $this->assertInstanceOf(Collection::class, $filteredCollection);
        $this->assertNotSame($filteredCollection, $originalCollection);
        $this->assertEquals(2, $filteredCollection->count());
        $this->assertEquals('string', $filteredCollection->getType());
        $this->assertEquals(['value1', 'value4'], $filteredCollection->toArray());

        // assert no side effect are occurred and original collection is not changed
        $this->assertEquals(4, $originalCollection->count());
        $this->assertEquals(\array_values($elements), $originalCollection->toArray());
    }

    /**
     * @test
     *
     * @throws CollectionException
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws InvalidArgumentException
     * @return void
     */
    public function flattenShouldReturnANewCollectionWithFlattenValuesAnTheTypeIsDeterminedByTheFirstResultWhichIsAStringValue(): void
    {
        $elements = [
            'key1' => (object) [
                'name' => 'value1'
            ],
            'key2' => (object) [
                'name' => [
                    'value2.1',
                    'value2.2'
                ]
            ],
            'key3' => (object) [
                'name' => 'value3'
            ],
            'key4' => (object) [
                'name' => 'value4'
            ]
        ];
        $originalCollection = new Collection(\stdClass::class, $elements);

        // assert initial data
        $this->assertEquals(4, $originalCollection->count());
        $this->assertEquals(\array_values($elements), $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [(object) ['name' => 'value1'], 0],
            [(object) ['name' => ['value2.1', 'value2.2']], 1],
            [(object) ['name' => 'value3'], 2],
            [(object) ['name' => 'value4'], 3]
        ];

        $callback = function (int $index, $value) use (&$calledTimes, $expectedCallArguments) {
            $this->assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return $value->name;
        };
        $flattenCollection = $this->getTypedCollectionSequenceHandler()->flatten($originalCollection, $callback);
        $this->assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        $this->assertInstanceOf(Collection::class, $flattenCollection);
        $this->assertNotSame($flattenCollection, $originalCollection);
        $this->assertEquals(5, $flattenCollection->count());
        $this->assertEquals('string', $flattenCollection->getType());
        $this->assertEquals(
            ['value1', 'value2.1', 'value2.2', 'value3', 'value4'],
            $flattenCollection->toArray()
        );

        // assert no side effect are occurred and original collection is not changed
        $this->assertEquals(4, $originalCollection->count());
        $this->assertSame(\array_values($elements), $originalCollection->toArray());
    }

    /**
     * @test
     *
     * @throws CollectionException
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws InvalidArgumentException
     * @return void
     */
    public function flattenShouldReturnANewCollectionWithFlattenValuesAnTheTypeIsDeterminedByTheFirstResultWhichIsAnArrayAnTheLastElementIsAString(): void
    {
        $elements = [
            'key1' => (object) [
                'name' => ['value1']
            ],
            'key2' => (object) [
                'name' => [
                    'value2.1',
                    'value2.2'
                ]
            ],
            'key3' => (object) [
                'name' => 'value3'
            ],
            'key4' => (object) [
                'name' => 'value4'
            ]
        ];
        $originalCollection = new Collection(\stdClass::class, $elements);

        // assert initial data
        $this->assertEquals(4, $originalCollection->count());
        $this->assertEquals(\array_values($elements), $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [(object) ['name' => ['value1']], 0],
            [(object) ['name' => ['value2.1', 'value2.2']], 1],
            [(object) ['name' => 'value3'], 2],
            [(object) ['name' => 'value4'], 3]
        ];

        $callback = function (int $index, $value) use (&$calledTimes, $expectedCallArguments) {
            $this->assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return $value->name;
        };
        $flattenCollection = $this->getTypedCollectionSequenceHandler()->flatten($originalCollection, $callback);
        $this->assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        $this->assertInstanceOf(Collection::class, $flattenCollection);
        $this->assertNotSame($flattenCollection, $originalCollection);
        $this->assertEquals(5, $flattenCollection->count());
        $this->assertEquals('string', $flattenCollection->getType());
        $this->assertEquals(
            ['value1', 'value2.1', 'value2.2', 'value3', 'value4'],
            $flattenCollection->toArray()
        );

        // assert no side effect are occurred and original collection is not changed
        $this->assertEquals(4, $originalCollection->count());
        $this->assertSame(\array_values($elements), $originalCollection->toArray());
    }

    /**
     * @test
     *
     * @throws CollectionException
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws InvalidArgumentException
     * @return void
     */
    public function flattenShouldReturnANewCollectionWithFlattenValuesAnTheTypeIsDeterminedByTheFirstResultWhichIsACollection(): void
    {
        $elements = [
            'key1' => (object) [
                'name' => new Collection('string', ['value1'])
            ],
            'key2' => (object) [
                'name' => [
                    'value2.1',
                    'value2.2'
                ]
            ],
            'key3' => (object) [
                'name' => 'value3'
            ],
            'key4' => (object) [
                'name' => 'value4'
            ]
        ];
        $originalCollection = new Collection(\stdClass::class, $elements);

        // assert initial data
        $this->assertEquals(4, $originalCollection->count());
        $this->assertEquals(\array_values($elements), $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [(object) ['name' => new Collection('string', ['value1'])], 0],
            [(object) ['name' => ['value2.1', 'value2.2']], 1],
            [(object) ['name' => 'value3'], 2],
            [(object) ['name' => 'value4'], 3]
        ];

        $callback = function (int $index, $value) use (&$calledTimes, $expectedCallArguments) {
            $this->assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return $value->name;
        };
        $flattenCollection = $this->getTypedCollectionSequenceHandler()->flatten($originalCollection, $callback);
        $this->assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        $this->assertInstanceOf(Collection::class, $flattenCollection);
        $this->assertNotSame($flattenCollection, $originalCollection);
        $this->assertEquals(5, $flattenCollection->count());
        $this->assertEquals('string', $flattenCollection->getType());
        $this->assertEquals(
            ['value1', 'value2.1', 'value2.2', 'value3', 'value4'],
            $flattenCollection->toArray()
        );

        // assert no side effect are occurred and original collection is not changed
        $this->assertEquals(4, $originalCollection->count());
        $this->assertSame(\array_values($elements), $originalCollection->toArray());
    }

    /**
     * @test
     *
     * @throws CollectionException
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws InvalidArgumentException
     * @return void
     */
    public function flattenShouldThrowACollectionExceptionWhenAnElementIsNotMatchingTheDeterminedType(): void
    {
        $elements = [
            'key1' => (object) [
                'name' => new Collection('string', ['value1'])
            ],
            'key2' => (object) [
                'name' => [
                    'value2.1',
                    'value2.2'
                ]
            ],
            'key3' => (object) [
                'name' => 5
            ],
            'key4' => (object) [
                'name' => 'value4'
            ]
        ];
        $originalCollection = new Collection(\stdClass::class, $elements);

        // assert initial data
        $this->assertEquals(4, $originalCollection->count());
        $this->assertEquals(\array_values($elements), $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [(object) ['name' => new Collection('string', ['value1'])], 0],
            [(object) ['name' => ['value2.1', 'value2.2']], 1],
            [(object) ['name' => 5], 2],
            [(object) ['name' => 'value4'], 3]
        ];

        $callback = function (int $index, $value) use (&$calledTimes, $expectedCallArguments) {
            $this->assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return $value->name;
        };

        $exception = null;
        $flattenCollection = null;
        try {
            $flattenCollection = $this->getTypedCollectionSequenceHandler()->flatten($originalCollection, $callback);
        } catch (CollectionException $exception) {
            // nothing to do
        }
        $this->assertEquals(new CollectionException('Data is not of expected type: `string`, but of type: `integer`'), $exception);
        $this->assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        $this->assertNull($flattenCollection);

        // assert no side effect are occurred and original collection is not changed
        $this->assertEquals(4, $originalCollection->count());
        $this->assertSame(\array_values($elements), $originalCollection->toArray());
    }

    /**
     * @test
     *
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws CollectionException
     * @throws HandlerException
     * @return void
     */
    public function performFlattenShouldReturnAnEmptyCollectionOfTheSameTypeWhenAnEmptyCollectionIsPassed(): void
    {
        /** @var Collection $result */
        $result = $this->getTypedCollectionSequenceHandler()->flatten(new Collection('string'), static function () {});
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
        $this->assertEquals('string', $result->getType());
    }

    /**
     * @return TypedCollectionSequenceHandler
     */
    private function getTypedCollectionSequenceHandler(): TypedCollectionSequenceHandler
    {
        return new TypedCollectionSequenceHandler();
    }
}