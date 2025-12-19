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
use Jojo1981\DataResolver\Handler\Exception\HandlerException;
use Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\CollectionIterator;
use Jojo1981\TypedCollection\Exception\CollectionException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Doubler\ClassNotFoundException;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use stdClass;
use function array_values;

/**
 * @package tests\Jojo1981\DataResolverHandlers
 */
final class TypedCollectionSequenceHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<Collection> */
    private ObjectProphecy $originalCollection;

    /** @var ObjectProphecy<Collection> */
    private ObjectProphecy $returnedCollection;

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
     * @throws ExpectationFailedException
     * @return void
     */
    public function testSupportShouldReturnFalseForDataWhichIsNotACollection(): void
    {
        self::assertFalse($this->getTypedCollectionSequenceHandler()->supports([]));
        self::assertFalse($this->getTypedCollectionSequenceHandler()->supports(['key' => 'value']));
        self::assertFalse($this->getTypedCollectionSequenceHandler()->supports(new ArrayIterator(['key' => 'value'])));
    }

    /**
     * @throws ObjectProphecyException
     * @throws ExpectationFailedException
     * @return void
     */
    public function testSupportShouldReturnTrueForCollection(): void
    {
        self::assertTrue($this->getTypedCollectionSequenceHandler()->supports($this->originalCollection->reveal()));
    }

    /**
     * @throws HandlerException
     * @return void
     */
    public function testGetIteratorShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler` can only handle instances of ' .
            '`Jojo1981\TypedCollection\Collection`. Illegal invocation of method `getIterator`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getTypedCollectionSequenceHandler()->getIterator('Not supported data');
    }

    /**
     * @throws HandlerException
     * @return void
     */
    public function testFilterShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler` can only handle instances of ' .
            '`Jojo1981\TypedCollection\Collection`. Illegal invocation of method `filter`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getTypedCollectionSequenceHandler()->filter('Not supported data', static function () {});
    }

    /**
     * @return void
     * @throws HandlerException
     * @throws CollectionException
     */
    public function testFlattenShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler` can only handle instances of ' .
            '`Jojo1981\TypedCollection\Collection`. Illegal invocation of method `flatten`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getTypedCollectionSequenceHandler()->flatten('Not supported data', static function () {});
    }

    /**
     * @throws HandlerException
     * @return void
     */
    public function testCountShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler` can only handle instances of ' .
            '`Jojo1981\TypedCollection\Collection`. Illegal invocation of method `count`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getTypedCollectionSequenceHandler()->count('Not supported data');
    }

    /**
     * @throws HandlerException
     * @throws ObjectProphecyException
     * @throws ExpectationFailedException
     * @return void
     */
    public function testGetIteratorShouldReturnTheIteratorFromTheCollection(): void
    {
        $iterator = new CollectionIterator(new ArrayIterator([]));
        /** @noinspection PhpUndefinedMethodInspection */
        $this->originalCollection->getIterator()->willReturn($iterator)->shouldBeCalledOnce();
        self::assertSame(
            $iterator,
            $this->getTypedCollectionSequenceHandler()->getIterator($this->originalCollection->reveal())
        );
    }

    /**
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testFilterShouldReturnTheFilterResultOfTheCollection(): void
    {
        $elements = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4'];
        $originalCollection = new Collection('string', $elements);

        // assert initial data
        self::assertEquals(4, $originalCollection->count());
        self::assertEquals(array_values($elements), $originalCollection->toArray());

        // test filter
        $calledTimes = 0;
        $callback = function (string $value, int $index) use (&$calledTimes): bool {
            $expectedCallArguments = [['value1', 0], ['value2', 1], ['value3', 2], ['value4', 3]];
            self::assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return 'value2' !== $value && 2 !== $index;
        };

        $filteredCollection = $this->getTypedCollectionSequenceHandler()->filter($originalCollection, $callback);
        self::assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        self::assertInstanceOf(Collection::class, $filteredCollection);
        self::assertNotSame($filteredCollection, $originalCollection);
        self::assertEquals(2, $filteredCollection->count());
        self::assertEquals('string', $filteredCollection->getType());
        self::assertEquals(['value1', 'value4'], $filteredCollection->toArray());

        // assert no side effect are occurred and original collection is not changed
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertEquals(4, $originalCollection->count());
        self::assertEquals(array_values($elements), $originalCollection->toArray());
    }

    /**
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testFlattenShouldReturnANewCollectionWithFlattenValuesAnTheTypeIsDeterminedByTheFirstResultWhichIsAStringValue(): void
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
        $originalCollection = new Collection(stdClass::class, $elements);

        // assert initial data
        self::assertEquals(4, $originalCollection->count());
        self::assertEquals(array_values($elements), $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [(object) ['name' => 'value1'], 0],
            [(object) ['name' => ['value2.1', 'value2.2']], 1],
            [(object) ['name' => 'value3'], 2],
            [(object) ['name' => 'value4'], 3]
        ];

        $callback = function ($value, int $index) use (&$calledTimes, $expectedCallArguments) {
            self::assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return $value->name;
        };
        $flattenCollection = $this->getTypedCollectionSequenceHandler()->flatten($originalCollection, $callback);
        self::assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        self::assertInstanceOf(Collection::class, $flattenCollection);
        self::assertNotSame($flattenCollection, $originalCollection);
        self::assertEquals(5, $flattenCollection->count());
        self::assertEquals('string', $flattenCollection->getType());
        self::assertEquals(
            ['value1', 'value2.1', 'value2.2', 'value3', 'value4'],
            $flattenCollection->toArray()
        );

        // assert no side effect are occurred and original collection is not changed
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertEquals(4, $originalCollection->count());
        self::assertSame(array_values($elements), $originalCollection->toArray());
    }

    /**
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testFlattenShouldReturnANewCollectionWithFlattenValuesAnTheTypeIsDeterminedByTheFirstResultWhichIsAnArrayAnTheLastElementIsAString(): void
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
        $originalCollection = new Collection(stdClass::class, $elements);

        // assert initial data
        self::assertEquals(4, $originalCollection->count());
        self::assertEquals(array_values($elements), $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [(object) ['name' => ['value1']], 0],
            [(object) ['name' => ['value2.1', 'value2.2']], 1],
            [(object) ['name' => 'value3'], 2],
            [(object) ['name' => 'value4'], 3]
        ];

        $callback = function ($value, int $index) use (&$calledTimes, $expectedCallArguments) {
            self::assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return $value->name;
        };
        $flattenCollection = $this->getTypedCollectionSequenceHandler()->flatten($originalCollection, $callback);
        self::assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        self::assertInstanceOf(Collection::class, $flattenCollection);
        self::assertNotSame($flattenCollection, $originalCollection);
        self::assertEquals(5, $flattenCollection->count());
        self::assertEquals('string', $flattenCollection->getType());
        self::assertEquals(
            ['value1', 'value2.1', 'value2.2', 'value3', 'value4'],
            $flattenCollection->toArray()
        );

        // assert no side effect are occurred and original collection is not changed
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertEquals(4, $originalCollection->count());
        self::assertSame(array_values($elements), $originalCollection->toArray());
    }

    /**
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testFlattenShouldReturnANewCollectionWithFlattenValuesAnTheTypeIsDeterminedByTheFirstResultWhichIsACollection(): void
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
        $originalCollection = new Collection(stdClass::class, $elements);

        // assert initial data
        self::assertEquals(4, $originalCollection->count());
        self::assertEquals(array_values($elements), $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [(object) ['name' => new Collection('string', ['value1'])], 0],
            [(object) ['name' => ['value2.1', 'value2.2']], 1],
            [(object) ['name' => 'value3'], 2],
            [(object) ['name' => 'value4'], 3]
        ];

        $callback = function ($value, int $index) use (&$calledTimes, $expectedCallArguments) {
            self::assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
            $calledTimes++;

            return $value->name;
        };
        $flattenCollection = $this->getTypedCollectionSequenceHandler()->flatten($originalCollection, $callback);
        self::assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        self::assertInstanceOf(Collection::class, $flattenCollection);
        self::assertNotSame($flattenCollection, $originalCollection);
        self::assertEquals(5, $flattenCollection->count());
        self::assertEquals('string', $flattenCollection->getType());
        self::assertEquals(
            ['value1', 'value2.1', 'value2.2', 'value3', 'value4'],
            $flattenCollection->toArray()
        );

        // assert no side effect are occurred and original collection is not changed
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertEquals(4, $originalCollection->count());
        self::assertSame(array_values($elements), $originalCollection->toArray());
    }

    /**
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testFlattenShouldThrowACollectionExceptionWhenAnElementIsNotMatchingTheDeterminedType(): void
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
        $originalCollection = new Collection(stdClass::class, $elements);

        // assert initial data
        self::assertEquals(4, $originalCollection->count());
        self::assertEquals(array_values($elements), $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [(object) ['name' => new Collection('string', ['value1'])], 0],
            [(object) ['name' => ['value2.1', 'value2.2']], 1],
            [(object) ['name' => 5], 2],
            [(object) ['name' => 'value4'], 3]
        ];

        $callback = function ($value, int $index) use (&$calledTimes, $expectedCallArguments) {
            self::assertEquals($expectedCallArguments[$calledTimes], [$value, $index]);
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
        self::assertInstanceOf(CollectionException::class, $exception);
        self::assertEquals('Data is not of expected type: `string`, but of type: `int`', $exception->getMessage());

        self::assertEquals(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        self::assertNull($flattenCollection);

        // assert no side effect are occurred and original collection is not changed
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertEquals(4, $originalCollection->count());
        self::assertSame(array_values($elements), $originalCollection->toArray());
    }

    /**
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testPerformFlattenShouldReturnAnEmptyCollectionOfTheSameTypeWhenAnEmptyCollectionIsPassed(): void
    {
        /** @var Collection $result */
        $result = $this->getTypedCollectionSequenceHandler()->flatten(new Collection('string'), static function () {});
        self::assertInstanceOf(Collection::class, $result);
        self::assertTrue($result->isEmpty());
        self::assertEquals('string', $result->getType());
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws RuntimeException
     * @throws CollectionException
     */
    public function testStaticShouldReturnTheCountOfThePassedCollection(): void
    {
        self::assertEquals(0, $this->getTypedCollectionSequenceHandler()->count(new Collection('string')));
        self::assertEquals(3, $this->getTypedCollectionSequenceHandler()->count(new Collection('integer', [1, 2, 3])));
    }

    /**
     * @return TypedCollectionSequenceHandler
     */
    private function getTypedCollectionSequenceHandler(): TypedCollectionSequenceHandler
    {
        return new TypedCollectionSequenceHandler();
    }
}
