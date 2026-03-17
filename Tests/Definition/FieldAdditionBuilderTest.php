<?php

/**
 * @since       17.03.2026 - 10:00
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Tests\Definition;

use NetGroup\DataTransformationLayer\Classes\Definition\FieldAddition;
use NetGroup\DataTransformationLayer\Classes\Definition\FieldAdditionBuilder;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldAdditionBuilderTest extends TestCase
{


    /**
     * @var ProjectionPlan&MockObject
     */
    private ProjectionPlan $plan;


    /**
     * @var DefinitionFactory&MockObject
     */
    private DefinitionFactory $factory;


    /**
     * @var string
     */
    private string $targetField;


    /**
     * @var FieldAdditionBuilder
     */
    private FieldAdditionBuilder $builder;


    protected function setUp(): void
    {
        $this->plan			= $this->createMock(ProjectionPlan::class);
        $this->factory		= $this->createMock(DefinitionFactory::class);
        $this->targetField	= 'total_price';
        $this->builder		= new FieldAdditionBuilder($this->plan, $this->factory, $this->targetField);
    }


    /**
     * Testet, dass `compute()` die Methode `createFieldAddition()` der Factory
     * mit dem korrekten targetField, converterClass, leeren Params und leerem sourceField aufruft.
     */
    public function testComputeCallsFactoryWithCorrectArguments(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $addition		= $this->createMock(FieldAddition::class);

        $this->factory
            ->expects($this->once())
            ->method('createFieldAddition')
            ->with($this->targetField, $converterClass, [], '')
            ->willReturn($addition);

        $this->plan
            ->expects($this->once())
            ->method('addAddition')
            ->with($addition);

        // Ausfuehren
        $this->builder->compute($converterClass);
    }


    /**
     * Testet, dass `compute()` die uebergebenen Params korrekt an die Factory weitergibt.
     */
    public function testComputeCallsFactoryWithParams(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $params			= ['fields' => ['quantity', 'unit_price']];
        $addition		= $this->createMock(FieldAddition::class);

        $this->factory
            ->expects($this->once())
            ->method('createFieldAddition')
            ->with($this->targetField, $converterClass, $params, '')
            ->willReturn($addition);

        $this->plan
            ->expects($this->once())
            ->method('addAddition');

        // Ausfuehren
        $this->builder->compute($converterClass, $params);
    }


    /**
     * Testet, dass `compute()` das sourceField korrekt an die Factory weitergibt.
     */
    public function testComputeCallsFactoryWithSourceField(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $sourceField	= 'unit_price';
        $addition		= $this->createMock(FieldAddition::class);

        $this->factory
            ->expects($this->once())
            ->method('createFieldAddition')
            ->with($this->targetField, $converterClass, [], $sourceField)
            ->willReturn($addition);

        $this->plan
            ->expects($this->once())
            ->method('addAddition');

        // Ausfuehren
        $this->builder->compute($converterClass, [], $sourceField);
    }


    /**
     * Testet, dass `compute()` die Methode `addAddition()` des Plans
     * mit der von der Factory erstellten FieldAddition aufruft.
     */
    public function testComputeCallsAddAdditionOnPlan(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $addition		= $this->createMock(FieldAddition::class);

        $this->factory
            ->method('createFieldAddition')
            ->willReturn($addition);

        $this->plan
            ->expects($this->once())
            ->method('addAddition')
            ->with($addition);

        // Ausfuehren
        $this->builder->compute($converterClass);
    }


    /**
     * Testet, dass `compute()` die eigene Instanz zurueckgibt (Fluent Interface).
     */
    public function testComputeReturnsSelf(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $addition		= $this->createMock(FieldAddition::class);

        $this->factory
            ->method('createFieldAddition')
            ->willReturn($addition);

        $this->plan
            ->method('addAddition');

        // Ausfuehren
        $result = $this->builder->compute($converterClass);

        // Assert
        $this->assertSame($this->builder, $result);
    }


    /**
     * Testet, dass mehrere aufeinanderfolgende Aufrufe von `compute()` jeweils
     * einen eigenen `createFieldAddition()`- und `addAddition()`-Aufruf ausloesen
     * und dabei immer die eigene Instanz zurueckgeben (Fluent Interface).
     */
    public function testComputeCanBeCalledMultipleTimesInChain(): void
    {
        // Anordnen
        $converterClass1	= 'FirstConverter';
        $converterClass2	= 'SecondConverter';
        $params2			= ['option' => true];
        $addition1			= $this->createMock(FieldAddition::class);
        $addition2			= $this->createMock(FieldAddition::class);

        $this->factory
            ->expects($this->exactly(2))
            ->method('createFieldAddition')
            ->willReturnOnConsecutiveCalls($addition1, $addition2);

        $this->plan
            ->expects($this->exactly(2))
            ->method('addAddition');

        // Ausfuehren
        $result = $this->builder
            ->compute($converterClass1)
            ->compute($converterClass2, $params2);

        // Assert
        $this->assertSame($this->builder, $result);
    }
}
