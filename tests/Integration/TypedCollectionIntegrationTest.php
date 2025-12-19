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

namespace tests\Jojo1981\DataResolverHandlers\Integration;

use Jojo1981\DataResolver\Exception\ResolverException;
use Jojo1981\DataResolver\Extractor\Exception\ExtractorException;
use Jojo1981\DataResolver\Factory;
use Jojo1981\DataResolver\Factory\ResolverBuilderFactory;
use Jojo1981\DataResolver\Handler\Exception\HandlerException;
use Jojo1981\DataResolver\Handler\MergeHandler\DefaultMergeHandler;
use Jojo1981\DataResolver\Predicate\Exception\PredicateException;
use Jojo1981\DataResolverHandlers\DoctrineCollectionMergeHandlerDecorator;
use Jojo1981\DataResolverHandlers\DoctrineCollectionSequenceHandler;
use Jojo1981\DataResolverHandlers\TypedCollectionMergeHandlerDecorator;
use Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\Exception\CollectionException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * @package tests\Jojo1981\DataResolverHandlers\Integration
 */
final class TypedCollectionIntegrationTest extends TestCase
{
    /** @var ResolverBuilderFactory */
    private ResolverBuilderFactory $resolverBuilderFactory;

    /**
     * @throws ResolverException
     * @return void
     */
    protected function setUp(): void
    {
        $this->resolverBuilderFactory = (new Factory())
            ->useDefaultSequenceHandlers()
            ->registerSequenceHandler(new DoctrineCollectionSequenceHandler())
            ->registerSequenceHandler(new TypedCollectionSequenceHandler())
            ->setMergeHandler(
                new DoctrineCollectionMergeHandlerDecorator(
                    new TypedCollectionMergeHandlerDecorator(
                        new DefaultMergeHandler()
                    )
                )
            )
            ->getResolverBuilderFactory();
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws ExtractorException
     * @throws HandlerException
     * @throws PredicateException
     * @throws RuntimeException
     * @throws CollectionException
     */
    #[CoversNothing]
    public function testIntegrationTestFlatten(): void
    {
        $expected = new Collection('string', ['Doe', 'Roe']);
        $actual = $this->resolverBuilderFactory
            ->flatten($this->resolverBuilderFactory->get('last_name'))
            ->build()
            ->resolve($this->getTestData());
        self::assertEquals($expected, $actual);

        $expected = new Collection('integer', [32, 30]);
        $actual = $this->resolverBuilderFactory
            ->flatten($this->resolverBuilderFactory->get('age'))
            ->build()
            ->resolve($this->getTestData());
        self::assertEquals($expected, $actual);

        $expected = new Collection('string', ['child1', 'child3', 'child2', 'child4']);
        $actual = $this->resolverBuilderFactory
            ->flatten($this->resolverBuilderFactory->get('children'))
            ->build()
            ->resolve($this->getTestData());
        self::assertEquals($expected, $actual);
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws ExtractorException
     * @throws HandlerException
     * @throws PredicateException
     * @throws RuntimeException
     * @throws CollectionException
     */
    #[CoversNothing]
    public function testIntegrationTestFilter(): void
    {
        $expected = new Collection(stdClass::class, [0 => $this->getJohnDoe()]);
        $actual = $this->resolverBuilderFactory
            ->filter($this->resolverBuilderFactory->where('firstName')->equals('John'))
            ->build()
            ->resolve($this->getTestData());
        self::assertEquals($expected, $actual);

        $expected = new Collection(stdClass::class, [1 => $this->getJaneRoe()]);
        $actual = $this->resolverBuilderFactory
            ->filter($this->resolverBuilderFactory->where('lastName')->equals('Roe'))
            ->build()
            ->resolve($this->getTestData());
        self::assertEquals($expected, $actual);
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws ExtractorException
     * @throws HandlerException
     * @throws PredicateException
     * @throws RuntimeException
     * @throws CollectionException
     */
    #[CoversNothing]
    public function testIntegrationTestMerge(): void
    {
        $expected = new Collection(stdClass::class, [$this->getAddress1(), $this->getAddress2()]);
        $actual = $this->resolverBuilderFactory
            ->flatten($this->resolverBuilderFactory->get('primaryAddresses', 'secondaryAddresses'))
            ->build()
            ->resolve($this->getTestData());
        self::assertEquals($expected, $actual);
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws ExtractorException
     * @throws HandlerException
     * @throws PredicateException
     * @throws RuntimeException
     * @throws CollectionException
     */
    #[CoversNothing]
    public function testIntegrationTestCount(): void
    {
        self::assertEquals(0, $this->resolverBuilderFactory->count()->resolve(new Collection('string')));
        self::assertEquals(2, $this->resolverBuilderFactory->count()->resolve($this->getTestData()));
    }

    /**
     * @return Collection<stdClass>
     * @throws CollectionException
     */
    private function getTestData(): Collection
    {
        return new Collection(stdClass::class, [$this->getJohnDoe(), $this->getJaneRoe()]);
    }

    /**
     * @return stdClass
     * @throws CollectionException
     */
    private function getJohnDoe(): stdClass
    {
        $result = new stdClass();
        $result->first_name = 'John';
        $result->lastName = 'Doe';
        $result->age = 32;
        $result->children = new Collection('string', ['child1', 'child3']);
        $result->primaryAddresses = new Collection(stdClass::class, [$this->getAddress1()]);
        $result->secondaryAddresses = new Collection(stdClass::class, [$this->getAddress2()]);

        return $result;
    }

    /**
     * @return stdClass
     * @throws CollectionException
     */
    private function getJaneRoe(): stdClass
    {
        $result = new stdClass();
        $result->first_name = ' Jane';
        $result->lastName = 'Roe';
        $result->age = 30;
        $result->children = new Collection('string', ['child2', 'child4']);
        $result->primaryAddresses = new Collection(stdClass::class);
        $result->secondaryAddresses = new Collection(stdClass::class);

        return $result;
    }

    /**
     * @return stdClass
     */
    private function getAddress1(): stdClass
    {
        $result = new stdClass();
        $result->street = '4402  Lincoln Drive';
        $result->city = 'Hummelstown';
        $result->state = 'Pennsylvania (PA)';
        $result->zipCode = '17036';


        return $result;
    }

    /**
     * @return stdClass
     */
    private function getAddress2(): stdClass
    {
        $result = new stdClass();
        $result->street = '1673  Rollins Road';
        $result->city = 'Potter';
        $result->state = 'Nebraska (NE)';
        $result->zipCode = '69156';


        return $result;
    }
}
