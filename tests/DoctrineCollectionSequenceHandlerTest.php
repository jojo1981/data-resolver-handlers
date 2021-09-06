<?php /** @noinspection PhpUndefinedMethodInspection */
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

use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Jojo1981\DataResolver\Handler\Exception\HandlerException;
use Jojo1981\DataResolverHandlers\DoctrineCollectionSequenceHandler;
use PHPUnit\Framework\Exception as PhpUnitFrameworkException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Doubler\ClassNotFoundException;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * @package tests\Jojo1981\DataResolverHandlers
 */
final class DoctrineCollectionSequenceHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|Collection */
    private ObjectProphecy $originalCollection;

    /** @var ObjectProphecy|Collection */
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
     * @test
     *
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @return void
     */
    public function supportShouldReturnFalseForDataWhichIsNotACollection(): void
    {
        self::assertFalse($this->getDoctrineCollectionSequenceHandler()->supports([]));
        self::assertFalse($this->getDoctrineCollectionSequenceHandler()->supports(['key' => 'value']));
        self::assertFalse($this->getDoctrineCollectionSequenceHandler()->supports(new ArrayIterator(['key' => 'value'])));
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
        self::assertTrue($this->getDoctrineCollectionSequenceHandler()->supports($this->originalCollection->reveal()));
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
            'The `' . DoctrineCollectionSequenceHandler::class . '` can only handle instances of ' .
            '`' . Collection::class . '`. Illegal invocation of method `getIterator`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getDoctrineCollectionSequenceHandler()->getIterator('Not supported data');
    }

    /**
     * @test
     *
     * @throws HandlerException
     * @return void
     */
    public function filterShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `' . DoctrineCollectionSequenceHandler::class . '` can only handle instances of ' .
            '`' . Collection::class . '`. Illegal invocation of method `filter`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getDoctrineCollectionSequenceHandler()->filter('Not supported data', static function () {});
    }

    /**
     * @test
     *
     * @throws HandlerException
     * @return void
     */
    public function flattenShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `' . DoctrineCollectionSequenceHandler::class . '` can only handle instances of ' .
            '`' . Collection::class . '`. Illegal invocation of method `flatten`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getDoctrineCollectionSequenceHandler()->flatten('Not supported data', static function () {});
    }

    /**
     * @test
     *
     * @throws HandlerException
     * @return void
     */
    public function countShouldThrowHandlerExceptionWhenCalledAnNotSupportTheData(): void
    {
        $this->expectExceptionObject(new HandlerException(
            'The `' . DoctrineCollectionSequenceHandler::class . '` can only handle instances of ' .
            '`' . Collection::class . '`. Illegal invocation of method `count`. You should ' .
            'invoke the `supports` method first!'
        ));

        $this->getDoctrineCollectionSequenceHandler()->count('Not supported data');
    }

    /**
     * @test
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws ObjectProphecyException
     * @throws ExpectationFailedException
     * @throws Exception
     * @throws HandlerException
     */
    public function getIteratorShouldReturnTheIteratorFromTheCollection(): void
    {
        $iterator = new ArrayIterator([]);
        $this->originalCollection->getIterator()->willReturn($iterator)->shouldBeCalledOnce();
        self::assertSame(
            $iterator,
            $this->getDoctrineCollectionSequenceHandler()->getIterator($this->originalCollection->reveal())
        );
    }

    /**
     * @test
     *
     * @return void
     * @throws PhpUnitFrameworkException
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws InvalidArgumentException
     */
    public function filterShouldReturnTheFilterResultOfTheCollection(): void
    {
        $elements = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4'];
        $originalCollection = new ArrayCollection($elements);

        // assert initial data
        self::assertEquals(4, $originalCollection->count());
        self::assertSame($elements, $originalCollection->toArray());

        // test filter
        $calledTimes = 0;
        $expectedCallArguments = [['value1', 'key1'], ['value2', 'key2'], ['value3', 'key3'], ['value4', 'key4']];
        $callback = function (string $value, string $key) use (&$calledTimes, $expectedCallArguments): bool {
            self::assertEquals($expectedCallArguments[$calledTimes], [$value, $key]);
            $calledTimes++;

            return 'value2' !== $value && 'key3' !== $key;
        };

        $filteredCollection = $this->getDoctrineCollectionSequenceHandler()->filter($originalCollection, $callback);
        self::assertSame(4, $calledTimes, 'Callback is expected to be called exactly 4 times');
        self::assertInstanceOf(ArrayCollection::class, $filteredCollection);
        self::assertNotSame($filteredCollection, $originalCollection);
        self::assertEquals(2, $filteredCollection->count());
        self::assertEquals(['key1' => 'value1', 'key4' => 'value4'], $filteredCollection->toArray());

        // assert no side effect are occurred and original collection is not changed
        self::assertEquals(4, $originalCollection->count());
        self::assertSame($elements, $originalCollection->toArray());
    }

    /**
     * @test
     *
     * @return void
     * @throws PhpUnitFrameworkException
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws InvalidArgumentException
     */
    public function flattenShouldReturnANewCollectionOfTheSameCollectionClassTypeWithTheFlattenValues(): void
    {
        $elements = [
            'key1' => [
                'name' => 'value1'
            ],
            'key2' => [
                'name' => [
                    'value2.1',
                    'value2.2',
                    'value2.3'
                ]
            ],
            'key3' => [
                'name' => 'value3'
            ],
            'key4' => [
                'name' => []
            ],
            'key5' => [
                'name' => 'value5'
            ],
            'key6' => [
                'name' => null
            ],
            'key7' => [
                'name' => [
                    'key1' => 'value7.1',
                    'key2' => 'value7.2',
                ]
            ],
            'key8' => [
                'name' => false
            ],
            'key9' => [
                'name' => true
            ],
            'key10' => [
                'name' => new ArrayCollection(['value10.1', 'value10.2'])
            ]
        ];
        $originalCollection = new ArrayCollection($elements);

        // assert initial data
        self::assertEquals(10, $originalCollection->count());
        self::assertSame($elements, $originalCollection->toArray());

        // test flatten
        $calledTimes = 0;
        $expectedCallArguments = [
            [['name' => 'value1'], 'key1'],
            [['name' => ['value2.1', 'value2.2', 'value2.3']], 'key2'],
            [['name' => 'value3'], 'key3'],
            [['name' => []], 'key4'],
            [['name' => 'value5'], 'key5'],
            [['name' => null], 'key6'],
            [['name' => ['key1' => 'value7.1', 'key2' => 'value7.2',]], 'key7'],
            [['name' => false], 'key8'],
            [['name' => true], 'key9'],
            [['name' => new ArrayCollection(['value10.1', 'value10.2'])], 'key10']
        ];

        $callback = function (array $value, string $key) use (&$calledTimes, $expectedCallArguments) {
            self::assertEquals($expectedCallArguments[$calledTimes], [$value, $key]);
            $calledTimes++;

            return $value['name'];
        };
        $flattenCollection = $this->getDoctrineCollectionSequenceHandler()->flatten($originalCollection, $callback);
        self::assertSame(10, $calledTimes, 'Callback is expected to be called exactly 4 times');
        self::assertInstanceOf(ArrayCollection::class, $flattenCollection);
        self::assertNotSame($flattenCollection, $originalCollection);
        self::assertEquals(12, $flattenCollection->count());
        self::assertEquals(
            ['value1', 'value2.1', 'value2.2', 'value2.3', 'value3', 'value5', 'value7.1', 'value7.2', false, true, 'value10.1', 'value10.2'],
            $flattenCollection->toArray()
        );

        // assert no side effect are occurred and original collection is not changed
        self::assertEquals(10, $originalCollection->count());
        self::assertSame($elements, $originalCollection->toArray());
    }

    /**
     * @test
     *
     * @return void
     * @throws ExpectationFailedException
     * @throws HandlerException
     * @throws InvalidArgumentException
     */
    public function staticShouldReturnTheCountOfThePassedCollection(): void
    {
        self::assertEquals(0, $this->getDoctrineCollectionSequenceHandler()->count(new ArrayCollection()));
        self::assertEquals(3, $this->getDoctrineCollectionSequenceHandler()->count(new ArrayCollection([1, 2, 3])));
    }

    /**
     * @return DoctrineCollectionSequenceHandler
     */
    private function getDoctrineCollectionSequenceHandler(): DoctrineCollectionSequenceHandler
    {
        return new DoctrineCollectionSequenceHandler();
    }
}
