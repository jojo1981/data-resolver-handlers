<?php
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace tests\Jojo1981\DataResolverHandlers\Integration;

use Doctrine\Common\Collections\ArrayCollection;
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
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * @package tests\Jojo1981\DataResolverHandlers\Integration
 */
class DoctrineCollectionIntegrationTest extends TestCase
{
    /** @var ResolverBuilderFactory */
    private $resolverBuilderFactory;

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
                new TypedCollectionMergeHandlerDecorator(
                    new DoctrineCollectionMergeHandlerDecorator(
                        new DefaultMergeHandler()
                    )
                )
            )
            ->getResolverBuilderFactory();
    }

    /**
     * @test
     *
     * @throws HandlerException
     * @throws PredicateException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws ExtractorException
     * @return void
     */
    public function integrationTestFlatten(): void
    {
        $testData = new ArrayCollection($this->getTestData());

        $expected = new ArrayCollection(['Doe', 'Roe']);
        $actual = $this->resolverBuilderFactory
            ->flatten($this->resolverBuilderFactory->get('last_name'))
            ->build()
            ->resolve($testData);
        $this->assertEquals($expected, $actual);

        $expected = new ArrayCollection([32, 30]);
        $actual = $this->resolverBuilderFactory
            ->flatten($this->resolverBuilderFactory->get('age'))
            ->build()
            ->resolve($testData);
        $this->assertEquals($expected, $actual);

        $expected = new ArrayCollection(['child1', 'child3', 'child2', 'child4']);
        $actual = $this->resolverBuilderFactory
            ->flatten($this->resolverBuilderFactory->get('children'))
            ->build()
            ->resolve($testData);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     *
     * @throws HandlerException
     * @throws PredicateException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws ExtractorException
     * @return void
     */
    public function integrationTestFilter(): void
    {
        $testData = new ArrayCollection($this->getTestData());
        $expected = new ArrayCollection([0 => $this->getJohnDoe()]);
        $actual = $this->resolverBuilderFactory
            ->filter($this->resolverBuilderFactory->where('firstName')->equals('John'))
            ->build()
            ->resolve($testData);
        $this->assertEquals($expected, $actual);

        $expected = new ArrayCollection([1 => $this->getJaneRoe()]);
        $actual = $this->resolverBuilderFactory
            ->filter($this->resolverBuilderFactory->where('lastName')->equals('Roe'))
            ->build()
            ->resolve($testData);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     *
     * @throws HandlerException
     * @throws PredicateException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws ExtractorException
     * @return void
     */
    public function integrationTestMerge(): void
    {
        $testData = new ArrayCollection($this->getTestData());
        $expected = new ArrayCollection([$this->getAddress1(), $this->getAddress2()]);
        $actual = $this->resolverBuilderFactory
            ->flatten($this->resolverBuilderFactory->get('primaryAddresses', 'secondaryAddresses'))
            ->build()
            ->resolve($testData);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return \stdClass[]
     */
    private function getTestData(): array
    {
        return [$this->getJohnDoe(), $this->getJaneRoe()];
    }

    /**
     * @return \stdClass
     */
    private function getJohnDoe(): \stdClass
    {
        $result = new \stdClass();
        $result->first_name = 'John';
        $result->lastName = 'Doe';
        $result->age = 32;
        $result->children = new ArrayCollection(['child1', 'child3']);
        $result->primaryAddresses = new ArrayCollection([$this->getAddress1()]);
        $result->secondaryAddresses = new ArrayCollection([$this->getAddress2()]);

        return $result;
    }

    /**
     * @return \stdClass
     */
    private function getJaneRoe(): \stdClass
    {
        $result = new \stdClass();
        $result->first_name = ' Jane';
        $result->lastName = 'Roe';
        $result->age = 30;
        $result->children = new ArrayCollection(['child2', 'child4']);
        $result->primaryAddresses = new ArrayCollection();
        $result->secondaryAddresses = new ArrayCollection();

        return $result;
    }

    /**
     * @return \stdClass
     */
    private function getAddress1(): \stdClass
    {
        $result = new \stdClass();
        $result->street = '4402  Lincoln Drive';
        $result->city = 'Hummelstown';
        $result->state = 'Pennsylvania (PA)';
        $result->zipCode = '17036';


        return $result;
    }

    /**
     * @return \stdClass
     */
    private function getAddress2(): \stdClass
    {
        $result = new \stdClass();
        $result->street = '1673  Rollins Road';
        $result->city = 'Potter';
        $result->state = 'Nebraska (NE)';
        $result->zipCode = '69156';


        return $result;
    }
}