<?php

/**
 * @since       13.03.2026 - 11:51
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Tests\Definition;

use NetGroup\DataTransformationLayer\Classes\Definition\FieldRuleBuilder;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlanBuilder;
use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectionPlanBuilderTest extends TestCase
{


    /**
     * @var DefinitionFactory&MockObject
     */
    private DefinitionFactory $factory;


    /**
     * @var ProjectionPlan&MockObject
     */
    private ProjectionPlan $plan;


    /**
     * @var string
     */
    private string $name;


    /**
     * @var ProjectionPlanBuilder
     */
    private ProjectionPlanBuilder $builder;


    protected function setUp(): void
    {
        $this->factory	= $this->createMock(DefinitionFactory::class);
        $this->plan		= $this->createMock(ProjectionPlan::class);
        $this->name		= 'testProjection';

        $this->factory
            ->method('createProjectionPlan')
            ->willReturn($this->plan);

        $this->builder = new ProjectionPlanBuilder($this->name, $this->factory);
    }


    /**
     * Testet, dass der Konstruktor die Methode `createProjectionPlan()` der Factory
     * mit dem übergebenen Namen aufruft, um den internen Plan zu erstellen.
     */
    public function testConstructorCallsFactoryWithName(): void
    {
        // Anordnen
        $name		= 'myProjection';
        $plan		= $this->createMock(ProjectionPlan::class);
        $factory	= $this->createMock(DefinitionFactory::class);

        $factory
            ->expects($this->once())
            ->method('createProjectionPlan')
            ->with($name)
            ->willReturn($plan);

        // Ausführen
        new ProjectionPlanBuilder($name, $factory);
    }


    /**
     * Testet, dass `getPlan()` den vom Konstruktor über die Factory erstellten
     * ProjectionPlan zurückgibt.
     */
    public function testGetPlanReturnsThePlanCreatedByFactory(): void
    {
        // Ausführen
        $result = $this->builder->getPlan();

        // Assert
        $this->assertSame($this->plan, $result);
    }


    /**
     * Testet, dass `field()` die Methode `createFieldRuleBuilder()` der Factory
     * mit dem internen Plan und dem übergebenen Feldnamen aufruft.
     */
    public function testFieldCallsFactoryWithPlanAndFieldName(): void
    {
        // Anordnen
        $fieldName		= 'myField';
        $fieldBuilder	= $this->createMock(FieldRuleBuilder::class);

        $this->factory
            ->expects($this->once())
            ->method('createFieldRuleBuilder')
            ->with($this->plan, $fieldName)
            ->willReturn($fieldBuilder);

        // Ausführen
        $this->builder->field($fieldName);
    }


    /**
     * Testet, dass `field()` den von der Factory erstellten FieldRuleBuilder zurückgibt.
     */
    public function testFieldReturnsFieldRuleBuilderFromFactory(): void
    {
        // Anordnen
        $fieldName		= 'myField';
        $fieldBuilder	= $this->createMock(FieldRuleBuilder::class);

        $this->factory
            ->method('createFieldRuleBuilder')
            ->willReturn($fieldBuilder);

        // Ausführen
        $result = $this->builder->field($fieldName);

        // Assert
        $this->assertSame($fieldBuilder, $result);
    }


    /**
     * Testet, dass mehrere Aufrufe von `field()` mit unterschiedlichen Feldnamen
     * jeweils einen eigenen `createFieldRuleBuilder()`-Aufruf auslösen.
     */
    public function testFieldCanBeCalledMultipleTimesWithDifferentFieldNames(): void
    {
        // Anordnen
        $fieldBuilder1	= $this->createMock(FieldRuleBuilder::class);
        $fieldBuilder2	= $this->createMock(FieldRuleBuilder::class);

        $this->factory
            ->expects($this->exactly(2))
            ->method('createFieldRuleBuilder')
            ->willReturnOnConsecutiveCalls($fieldBuilder1, $fieldBuilder2);

        // Ausführen
        $result1 = $this->builder->field('firstField');
        $result2 = $this->builder->field('secondField');

        // Assert
        $this->assertSame($fieldBuilder1, $result1);
        $this->assertSame($fieldBuilder2, $result2);
    }


    /**
     * Testet, dass `getPlan()` bei mehrfachem Aufruf immer dieselbe Plan-Instanz
     * zurückgibt (keine neue Instanz wird erzeugt).
     */
    public function testGetPlanAlwaysReturnsSamePlanInstance(): void
    {
        // Ausführen
        $result1 = $this->builder->getPlan();
        $result2 = $this->builder->getPlan();

        // Assert
        $this->assertSame($result1, $result2);
    }
}
