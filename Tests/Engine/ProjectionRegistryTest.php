<?php

/**
 * @since       13.03.2026 - 16:00
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Tests\Engine;

use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlanBuilder;
use NetGroup\DataTransformationLayer\Classes\Engine\ProjectionRegistry;
use NetGroup\DataTransformationLayer\Classes\Projection\ProjectionInterface;
use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProjectionRegistryTest extends TestCase
{


    /**
     * @var DefinitionFactory&MockObject
     */
    private DefinitionFactory $factoryMock;


    /**
     * @var ProjectionInterface&MockObject
     */
    private ProjectionInterface $projectionMock;


    /**
     * @var ProjectionPlanBuilder&MockObject
     */
    private ProjectionPlanBuilder $planBuilderMock;


    /**
     * @var ProjectionPlan&MockObject
     */
    private ProjectionPlan $planMock;


    protected function setUp(): void
    {
        $this->factoryMock = $this->getMockBuilder(DefinitionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->projectionMock = $this->getMockBuilder(ProjectionInterface::class)
            ->getMock();

        $this->planBuilderMock = $this->getMockBuilder(ProjectionPlanBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->planMock = $this->getMockBuilder(ProjectionPlan::class)
            ->disableOriginalConstructor()
            ->getMock();
    }


    /**
     * Testet, dass `getPlan()` den Plan über die Factory und die Projection aufbaut
     * und das Ergebnis des Builders zurückgibt.
     */
    public function testGetPlanBuildsAndReturnsPlan(): void
    {
        // Anordnen
        $projectionName = 'test_projection';

        $this->projectionMock
            ->method('name')
            ->willReturn($projectionName);

        $this->factoryMock
            ->method('createProjectionPlanBuilder')
            ->with($projectionName)
            ->willReturn($this->planBuilderMock);

        $this->planBuilderMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $registry = new ProjectionRegistry([$this->projectionMock], $this->factoryMock);

        // Ausführen
        $result = $registry->getPlan($projectionName);

        // Assert
        $this->assertSame($this->planMock, $result);
    }


    /**
     * Testet, dass `build()` auf der Projection genau einmal aufgerufen wird,
     * wenn `getPlan()` zum ersten Mal aufgerufen wird.
     */
    public function testGetPlanCallsBuildOnProjectionOnce(): void
    {
        // Anordnen
        $projectionName = 'test_projection';

        $this->projectionMock
            ->method('name')
            ->willReturn($projectionName);

        $this->factoryMock
            ->method('createProjectionPlanBuilder')
            ->willReturn($this->planBuilderMock);

        $this->planBuilderMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->projectionMock
            ->expects($this->once())
            ->method('build')
            ->with($this->planBuilderMock);

        $registry = new ProjectionRegistry([$this->projectionMock], $this->factoryMock);

        // Ausführen
        $registry->getPlan($projectionName);
    }


    /**
     * Testet, dass `getPlan()` den Plan beim zweiten Aufruf aus dem Cache zurückgibt
     * und `build()` auf der Projection nur einmal aufgerufen wird.
     */
    public function testGetPlanReturnsCachedPlanOnSecondCall(): void
    {
        // Anordnen
        $projectionName = 'test_projection';

        $this->projectionMock
            ->method('name')
            ->willReturn($projectionName);

        $this->factoryMock
            ->method('createProjectionPlanBuilder')
            ->willReturn($this->planBuilderMock);

        $this->planBuilderMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        // build() darf nur einmal aufgerufen werden (zweiter Aufruf kommt aus dem Cache)
        $this->projectionMock
            ->expects($this->once())
            ->method('build');

        $registry = new ProjectionRegistry([$this->projectionMock], $this->factoryMock);

        // Ausführen
        $firstResult    = $registry->getPlan($projectionName);
        $secondResult   = $registry->getPlan($projectionName);

        // Assert – beide Aufrufe liefern dieselbe Instanz
        $this->assertSame($firstResult, $secondResult);
    }


    /**
     * Testet, dass `getPlan()` eine `InvalidArgumentException` wirft,
     * wenn der angeforderte Projektionsname nicht registriert ist.
     */
    public function testGetPlanThrowsInvalidArgumentExceptionForUnknownProjection(): void
    {
        // Anordnen
        $registry = new ProjectionRegistry([], $this->factoryMock);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown projection "unknown".');

        // Ausführen
        $registry->getPlan('unknown');
    }


    /**
     * Testet, dass `getPlan()` die korrekte Fehlermeldung mit dem Projektionsnamen enthält.
     */
    public function testGetPlanExceptionMessageContainsProjectionName(): void
    {
        // Anordnen
        $projectionName = 'my_missing_projection';
        $registry       = new ProjectionRegistry([], $this->factoryMock);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown projection "%s".', $projectionName));

        // Ausführen
        $registry->getPlan($projectionName);
    }


    /**
     * Testet, dass mehrere Projektionen korrekt registriert werden
     * und jede über ihren Namen abgerufen werden kann.
     */
    public function testGetPlanHandlesMultipleProjections(): void
    {
        // Anordnen
        $projectionName1 = 'projection_one';
        $projectionName2 = 'projection_two';

        $projectionMock1 = $this->getMockBuilder(ProjectionInterface::class)->getMock();
        $projectionMock2 = $this->getMockBuilder(ProjectionInterface::class)->getMock();

        $planBuilderMock1 = $this->getMockBuilder(ProjectionPlanBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $planBuilderMock2 = $this->getMockBuilder(ProjectionPlanBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $planMock1 = $this->getMockBuilder(ProjectionPlan::class)
            ->disableOriginalConstructor()
            ->getMock();
        $planMock2 = $this->getMockBuilder(ProjectionPlan::class)
            ->disableOriginalConstructor()
            ->getMock();

        $projectionMock1->method('name')->willReturn($projectionName1);
        $projectionMock2->method('name')->willReturn($projectionName2);

        $this->factoryMock
            ->method('createProjectionPlanBuilder')
            ->willReturnMap([
                [$projectionName1, $planBuilderMock1],
                [$projectionName2, $planBuilderMock2],
            ]);

        $planBuilderMock1->method('getPlan')->willReturn($planMock1);
        $planBuilderMock2->method('getPlan')->willReturn($planMock2);

        $registry = new ProjectionRegistry([$projectionMock1, $projectionMock2], $this->factoryMock);

        // Ausführen
        $result1 = $registry->getPlan($projectionName1);
        $result2 = $registry->getPlan($projectionName2);

        // Assert – jede Projektion liefert ihren eigenen Plan
        $this->assertSame($planMock1, $result1);
        $this->assertSame($planMock2, $result2);
    }


    /**
     * Testet, dass `createProjectionPlanBuilder()` auf der Factory mit dem korrekten
     * Projektionsnamen aufgerufen wird.
     */
    public function testGetPlanCallsFactoryWithCorrectProjectionName(): void
    {
        // Anordnen
        $projectionName = 'my_projection';

        $this->projectionMock
            ->method('name')
            ->willReturn($projectionName);

        $this->factoryMock
            ->expects($this->once())
            ->method('createProjectionPlanBuilder')
            ->with($projectionName)
            ->willReturn($this->planBuilderMock);

        $this->planBuilderMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $registry = new ProjectionRegistry([$this->projectionMock], $this->factoryMock);

        // Ausführen
        $registry->getPlan($projectionName);
    }
}
