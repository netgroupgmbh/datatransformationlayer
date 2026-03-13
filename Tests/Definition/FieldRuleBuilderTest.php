<?php

/**
 * @since       12.03.2026 - 10:25
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Tests\Definition;

use NetGroup\DataTransformationLayer\Classes\Definition\ConversionStep;
use NetGroup\DataTransformationLayer\Classes\Definition\FieldRuleBuilder;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldRuleBuilderTest extends TestCase
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
    private string $field;


    /**
     * @var FieldRuleBuilder
     */
    private FieldRuleBuilder $builder;


    protected function setUp(): void
    {
        $this->plan		= $this->createMock(ProjectionPlan::class);
        $this->factory	= $this->createMock(DefinitionFactory::class);
        $this->field	= 'testField';
        $this->builder	= new FieldRuleBuilder($this->plan, $this->factory, $this->field);
    }


    /**
     * Testet, dass `convert()` die Methode `createConversionStep()` der Factory
     * mit dem übergebenen Converter-Klassennamen und einem leeren Params-Array aufruft,
     * wenn kein zweites Argument übergeben wird.
     */
    public function testConvertCallsFactoryWithConverterClassAndEmptyParams(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $step			= $this->createMock(ConversionStep::class);

        $this->factory
            ->expects($this->once())
            ->method('createConversionStep')
            ->with($converterClass, [])
            ->willReturn($step);

        $this->plan
            ->expects($this->once())
            ->method('addStep');

        // Ausführen
        $this->builder->convert($converterClass);
    }


    /**
     * Testet, dass `convert()` die Methode `createConversionStep()` der Factory
     * mit dem übergebenen Converter-Klassennamen und den übergebenen Params aufruft.
     */
    public function testConvertCallsFactoryWithConverterClassAndParams(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $params			= ['key' => 'value', 'number' => 42];
        $step			= $this->createMock(ConversionStep::class);

        $this->factory
            ->expects($this->once())
            ->method('createConversionStep')
            ->with($converterClass, $params)
            ->willReturn($step);

        $this->plan
            ->expects($this->once())
            ->method('addStep');

        // Ausführen
        $this->builder->convert($converterClass, $params);
    }


    /**
     * Testet, dass `convert()` die Methode `addStep()` des Plans
     * mit dem korrekten Feldnamen und dem von der Factory erstellten ConversionStep aufruft.
     */
    public function testConvertCallsAddStepOnPlanWithFieldAndStep(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $step			= $this->createMock(ConversionStep::class);

        $this->factory
            ->method('createConversionStep')
            ->willReturn($step);

        $this->plan
            ->expects($this->once())
            ->method('addStep')
            ->with($this->field, $step);

        // Ausführen
        $this->builder->convert($converterClass);
    }


    /**
     * Testet, dass `convert()` die eigene Instanz zurückgibt (Fluent Interface),
     * damit Methodenaufrufe verkettet werden können.
     */
    public function testConvertReturnsSelf(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $step			= $this->createMock(ConversionStep::class);

        $this->factory
            ->method('createConversionStep')
            ->willReturn($step);

        $this->plan
            ->method('addStep');

        // Ausführen
        $result = $this->builder->convert($converterClass);

        // Assert
        $this->assertSame($this->builder, $result);
    }


    /**
     * Testet, dass mehrere aufeinanderfolgende Aufrufe von `convert()` jeweils
     * einen eigenen `createConversionStep()`- und `addStep()`-Aufruf auslösen
     * und dabei immer die eigene Instanz zurückgeben (Fluent Interface).
     */
    public function testConvertCanBeCalledMultipleTimesInChain(): void
    {
        // Anordnen
        $converterClass1	= 'FirstConverter';
        $converterClass2	= 'SecondConverter';
        $params2			= ['option' => true];
        $step1				= $this->createMock(ConversionStep::class);
        $step2				= $this->createMock(ConversionStep::class);

        $this->factory
            ->expects($this->exactly(2))
            ->method('createConversionStep')
            ->willReturnOnConsecutiveCalls($step1, $step2);

        $this->plan
            ->expects($this->exactly(2))
            ->method('addStep');

        // Ausführen
        $result = $this->builder
            ->convert($converterClass1)
            ->convert($converterClass2, $params2);

        // Assert
        $this->assertSame($this->builder, $result);
    }


    /**
     * Testet, dass `convert()` auch mit einem leeren Params-Array korrekt funktioniert,
     * wenn dieses explizit übergeben wird.
     */
    public function testConvertWithExplicitEmptyParamsArray(): void
    {
        // Anordnen
        $converterClass	= 'SomeConverter';
        $params			= [];
        $step			= $this->createMock(ConversionStep::class);

        $this->factory
            ->expects($this->once())
            ->method('createConversionStep')
            ->with($converterClass, $params)
            ->willReturn($step);

        $this->plan
            ->expects($this->once())
            ->method('addStep')
            ->with($this->field, $step);

        // Ausführen
        $result = $this->builder->convert($converterClass, $params);

        // Assert
        $this->assertSame($this->builder, $result);
    }
}
