<?php

/**
 * @since       13.03.2026 - 11:58
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Tests\Services\Factories;

use NetGroup\DataTransformationLayer\Classes\Definition\FieldRuleBuilder;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlanBuilder;
use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;
use PHPUnit\Framework\TestCase;

class DefinitionFactoryTest extends TestCase
{


    /**
     * @var DefinitionFactory
     */
    private DefinitionFactory $factory;


    protected function setUp(): void
    {
        $this->factory = new DefinitionFactory();
    }


    /**
     * Testet, dass `createConversionContext()` eine Instanz von `ConversionContext` zurückgibt,
     * bei der der Projection-Klassenname korrekt gesetzt ist.
     */
    public function testCreateConversionContext(): void
    {
        // Anordnen
        $projection = 'SomeConverter';
        $options    = ['key' => 'value', 'number' => 42];

        // Ausführen
        $result = $this->factory->createConversionContext($projection, $options);

        // Assert
        $this->assertSame($projection, $result->projection);
    }


    /**
     * Testet, dass `createConversionStep()` eine Instanz von `ConversionStep` zurückgibt,
     * bei der der Converter-Klassenname korrekt gesetzt ist.
     */
    public function testCreateConversionStepReturnsConversionStepWithCorrectConverterClass(): void
    {
        // Anordnen
        $converterClass = 'SomeConverter';
        $params         = [];

        // Ausführen
        $result = $this->factory->createConversionStep($converterClass, $params);

        // Assert
        $this->assertSame($converterClass, $result->converterClass);
    }


    /**
     * Testet, dass `createConversionStep()` eine Instanz von `ConversionStep` zurückgibt,
     * bei der die übergebenen Parameter korrekt gesetzt sind.
     */
    public function testCreateConversionStepReturnsConversionStepWithCorrectParams(): void
    {
        // Anordnen
        $converterClass = 'SomeConverter';
        $params         = ['key' => 'value', 'number' => 42];

        // Ausführen
        $result = $this->factory->createConversionStep($converterClass, $params);

        // Assert
        $this->assertSame($params, $result->params);
    }


    /**
     * Testet, dass `createConversionStep()` bei einem leeren Params-Array
     * eine `ConversionStep`-Instanz mit leerem Params-Array zurückgibt.
     */
    public function testCreateConversionStepWithEmptyParamsReturnsConversionStepWithEmptyParams(): void
    {
        // Anordnen
        $converterClass = 'SomeConverter';
        $params         = [];

        // Ausführen
        $result = $this->factory->createConversionStep($converterClass, $params);

        // Assert
        $this->assertSame([], $result->params);
    }


    /**
     * Testet, dass `createConversionStep()` bei jedem Aufruf eine neue,
     * eigenständige Instanz von `ConversionStep` zurückgibt.
     */
    public function testCreateConversionStepReturnsNewInstanceOnEachCall(): void
    {
        // Anordnen
        $converterClass = 'SomeConverter';
        $params         = [];

        // Ausführen
        $result1 = $this->factory->createConversionStep($converterClass, $params);
        $result2 = $this->factory->createConversionStep($converterClass, $params);

        // Assert
        $this->assertNotSame($result1, $result2);
    }


    /**
     * Testet, dass `createProjectionPlan()` eine Instanz von `ProjectionPlan` zurückgibt,
     * bei der der Name korrekt gesetzt ist.
     */
    public function testCreateProjectionPlanReturnsProjectionPlanWithCorrectName(): void
    {
        // Anordnen
        $name = 'myPlan';

        // Ausführen
        $result = $this->factory->createProjectionPlan($name);

        // Assert
        $this->assertSame($name, $result->name);
    }


    /**
     * Testet, dass `createProjectionPlan()` bei jedem Aufruf eine neue,
     * eigenständige Instanz von `ProjectionPlan` zurückgibt.
     */
    public function testCreateProjectionPlanReturnsNewInstanceOnEachCall(): void
    {
        // Anordnen
        $name = 'myPlan';

        // Ausführen
        $result1 = $this->factory->createProjectionPlan($name);
        $result2 = $this->factory->createProjectionPlan($name);

        // Assert
        $this->assertNotSame($result1, $result2);
    }


    /**
     * Testet, dass `createFieldRuleBuilder()` eine Instanz von `FieldRuleBuilder` zurückgibt.
     */
    public function testCreateFieldRuleBuilderReturnsFieldRuleBuilder(): void
    {
        // Anordnen
        $plan   = new ProjectionPlan('testPlan');
        $field  = 'testField';

        // Ausführen
        $result = $this->factory->createFieldRuleBuilder($plan, $field);

        // Assert
        $this->assertInstanceOf(FieldRuleBuilder::class, $result);
    }


    /**
     * Testet, dass `createFieldRuleBuilder()` bei jedem Aufruf eine neue,
     * eigenständige Instanz von `FieldRuleBuilder` zurückgibt.
     */
    public function testCreateFieldRuleBuilderReturnsNewInstanceOnEachCall(): void
    {
        // Anordnen
        $plan   = new ProjectionPlan('testPlan');
        $field  = 'testField';

        // Ausführen
        $result1 = $this->factory->createFieldRuleBuilder($plan, $field);
        $result2 = $this->factory->createFieldRuleBuilder($plan, $field);

        // Assert
        $this->assertNotSame($result1, $result2);
    }


    /**
     * Testet, dass `createProjectionPlanBuilder()` eine Instanz von `ProjectionPlanBuilder` zurückgibt.
     */
    public function testCreateProjectionPlanBuilderReturnsProjectionPlanBuilder(): void
    {
        // Anordnen
        $name = 'myProjection';

        // Ausführen
        $result = $this->factory->createProjectionPlanBuilder($name);

        // Assert
        $this->assertInstanceOf(ProjectionPlanBuilder::class, $result);
    }


    /**
     * Testet, dass `createProjectionPlanBuilder()` einen `ProjectionPlanBuilder` zurückgibt,
     * dessen interner Plan den korrekten Namen trägt.
     */
    public function testCreateProjectionPlanBuilderReturnsPlanBuilderWithCorrectPlanName(): void
    {
        // Anordnen
        $name = 'myProjection';

        // Ausführen
        $result = $this->factory->createProjectionPlanBuilder($name);

        // Assert
        $this->assertSame($name, $result->getPlan()->name);
    }


    /**
     * Testet, dass `createProjectionPlanBuilder()` bei jedem Aufruf eine neue,
     * eigenständige Instanz von `ProjectionPlanBuilder` zurückgibt.
     */
    public function testCreateProjectionPlanBuilderReturnsNewInstanceOnEachCall(): void
    {
        // Anordnen
        $name = 'myProjection';

        // Ausführen
        $result1 = $this->factory->createProjectionPlanBuilder($name);
        $result2 = $this->factory->createProjectionPlanBuilder($name);

        // Assert
        $this->assertNotSame($result1, $result2);
    }
}
