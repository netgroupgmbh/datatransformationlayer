<?php

/**
 * @since       13.03.2026 - 11:42
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
use NetGroup\DataTransformationLayer\Classes\Definition\FieldAddition;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectionPlanTest extends TestCase
{


    /**
     * @var ConversionStep&MockObject
     */
    private ConversionStep $stepMock;


    protected function setUp(): void
    {
        $this->stepMock = $this->getMockBuilder(ConversionStep::class)
            ->disableOriginalConstructor()
            ->getMock();
    }


    /**
     * Testet, dass der Konstruktor die Eigenschaft `name` korrekt setzt.
     */
    public function testConstructorSetsName(): void
    {
        // Anordnen
        $name = 'my_projection';

        // Ausführen
        $plan = new ProjectionPlan($name);

        // Assert
        $this->assertSame($name, $plan->name);
    }


    /**
     * Testet, dass `stepsByField()` initial ein leeres Array zurückgibt,
     * bevor Schritte hinzugefügt wurden.
     */
    public function testStepsByFieldReturnsEmptyArrayInitially(): void
    {
        // Anordnen
        $plan = new ProjectionPlan('my_projection');

        // Ausführen
        $result = $plan->stepsByField();

        // Assert
        $this->assertSame([], $result);
    }


    /**
     * Testet, dass `addStep()` einen Schritt für ein Feld korrekt hinzufügt
     * und `stepsByField()` diesen zurückgibt.
     */
    public function testAddStepAddsStepForField(): void
    {
        // Anordnen
        $plan   = new ProjectionPlan('my_projection');
        $field  = 'title';

        // Ausführen
        $plan->addStep($field, $this->stepMock);
        $result = $plan->stepsByField();

        // Assert
        $this->assertArrayHasKey($field, $result);
        $this->assertCount(1, $result[$field]);
        $this->assertSame($this->stepMock, $result[$field][0]);
    }


    /**
     * Testet, dass `addStep()` mehrere Schritte für dasselbe Feld
     * korrekt akkumuliert (als Liste).
     */
    public function testAddStepAccumulatesMultipleStepsForSameField(): void
    {
        // Anordnen
        $plan       = new ProjectionPlan('my_projection');
        $field      = 'title';
        $stepMock2  = $this->getMockBuilder(ConversionStep::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ausführen
        $plan->addStep($field, $this->stepMock);
        $plan->addStep($field, $stepMock2);
        $result = $plan->stepsByField();

        // Assert
        $this->assertArrayHasKey($field, $result);
        $this->assertCount(2, $result[$field]);
        $this->assertSame($this->stepMock, $result[$field][0]);
        $this->assertSame($stepMock2, $result[$field][1]);
    }


    /**
     * Testet, dass `addStep()` Schritte für verschiedene Felder
     * unabhängig voneinander speichert.
     */
    public function testAddStepHandlesMultipleFields(): void
    {
        // Anordnen
        $plan       = new ProjectionPlan('my_projection');
        $field1     = 'title';
        $field2     = 'description';
        $stepMock2  = $this->getMockBuilder(ConversionStep::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ausführen
        $plan->addStep($field1, $this->stepMock);
        $plan->addStep($field2, $stepMock2);
        $result = $plan->stepsByField();

        // Assert
        $this->assertArrayHasKey($field1, $result);
        $this->assertArrayHasKey($field2, $result);
        $this->assertCount(1, $result[$field1]);
        $this->assertCount(1, $result[$field2]);
        $this->assertSame($this->stepMock, $result[$field1][0]);
        $this->assertSame($stepMock2, $result[$field2][0]);
    }


    /**
     * Testet, dass `stepsByField()` die Reihenfolge der hinzugefügten Schritte
     * für ein Feld beibehält (FIFO-Reihenfolge).
     */
    public function testAddStepPreservesInsertionOrder(): void
    {
        // Anordnen
        $plan       = new ProjectionPlan('my_projection');
        $field      = 'price';
        $stepMock2  = $this->getMockBuilder(ConversionStep::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stepMock3  = $this->getMockBuilder(ConversionStep::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ausführen
        $plan->addStep($field, $this->stepMock);
        $plan->addStep($field, $stepMock2);
        $plan->addStep($field, $stepMock3);
        $result = $plan->stepsByField();

        // Assert – Reihenfolge muss der Einfügereihenfolge entsprechen
        $this->assertSame($this->stepMock, $result[$field][0]);
        $this->assertSame($stepMock2, $result[$field][1]);
        $this->assertSame($stepMock3, $result[$field][2]);
    }


    /**
     * Testet, dass die Eigenschaft `name` als readonly deklariert ist
     * und nach der Initialisierung nicht verändert werden kann.
     */
    public function testNamePropertyIsReadonly(): void
    {
        // Anordnen
        $plan = new ProjectionPlan('my_projection');

        // Assert – Versuch, readonly-Eigenschaft zu überschreiben, muss einen Fehler werfen
        $this->expectException(\Error::class);

        // Ausführen – dies soll fehlschlagen
        $plan->name = 'other_projection'; // @phpstan-ignore-line
    }


    /**
     * Testet, dass `additions()` initial ein leeres Array zurueckgibt,
     * bevor Additions hinzugefuegt wurden.
     */
    public function testAdditionsReturnsEmptyArrayInitially(): void
    {
        // Anordnen
        $plan = new ProjectionPlan('my_projection');

        // Ausfuehren
        $result = $plan->additions();

        // Assert
        $this->assertSame([], $result);
    }


    /**
     * Testet, dass `addAddition()` eine FieldAddition korrekt hinzufuegt
     * und `additions()` diese zurueckgibt.
     */
    public function testAddAdditionAddsFieldAddition(): void
    {
        // Anordnen
        $plan       = new ProjectionPlan('my_projection');
        $addition   = $this->getMockBuilder(FieldAddition::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ausfuehren
        $plan->addAddition($addition);
        $result = $plan->additions();

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame($addition, $result[0]);
    }


    /**
     * Testet, dass `addAddition()` mehrere FieldAdditions korrekt akkumuliert.
     */
    public function testAddAdditionAccumulatesMultipleAdditions(): void
    {
        // Anordnen
        $plan       = new ProjectionPlan('my_projection');
        $addition1  = $this->getMockBuilder(FieldAddition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addition2  = $this->getMockBuilder(FieldAddition::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ausfuehren
        $plan->addAddition($addition1);
        $plan->addAddition($addition2);
        $result = $plan->additions();

        // Assert
        $this->assertCount(2, $result);
        $this->assertSame($addition1, $result[0]);
        $this->assertSame($addition2, $result[1]);
    }


    /**
     * Testet, dass `removals()` initial ein leeres Array zurueckgibt,
     * bevor Removals hinzugefuegt wurden.
     */
    public function testRemovalsReturnsEmptyArrayInitially(): void
    {
        // Anordnen
        $plan = new ProjectionPlan('my_projection');

        // Ausfuehren
        $result = $plan->removals();

        // Assert
        $this->assertSame([], $result);
    }


    /**
     * Testet, dass `addRemoval()` einen Feldnamen korrekt hinzufuegt
     * und `removals()` diesen zurueckgibt.
     */
    public function testAddRemovalAddsFieldName(): void
    {
        // Anordnen
        $plan   = new ProjectionPlan('my_projection');
        $field  = 'quantity';

        // Ausfuehren
        $plan->addRemoval($field);
        $result = $plan->removals();

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame($field, $result[0]);
    }


    /**
     * Testet, dass `addRemoval()` mehrere Feldnamen korrekt akkumuliert.
     */
    public function testAddRemovalAccumulatesMultipleRemovals(): void
    {
        // Anordnen
        $plan   = new ProjectionPlan('my_projection');
        $field1 = 'quantity';
        $field2 = 'unit_price';

        // Ausfuehren
        $plan->addRemoval($field1);
        $plan->addRemoval($field2);
        $result = $plan->removals();

        // Assert
        $this->assertCount(2, $result);
        $this->assertSame($field1, $result[0]);
        $this->assertSame($field2, $result[1]);
    }


    /**
     * Testet, dass `addAddition()` die Einfuegereihenfolge beibehaelt (FIFO).
     */
    public function testAddAdditionPreservesInsertionOrder(): void
    {
        // Anordnen
        $plan       = new ProjectionPlan('my_projection');
        $addition1  = $this->getMockBuilder(FieldAddition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addition2  = $this->getMockBuilder(FieldAddition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addition3  = $this->getMockBuilder(FieldAddition::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ausfuehren
        $plan->addAddition($addition1);
        $plan->addAddition($addition2);
        $plan->addAddition($addition3);
        $result = $plan->additions();

        // Assert
        $this->assertSame($addition1, $result[0]);
        $this->assertSame($addition2, $result[1]);
        $this->assertSame($addition3, $result[2]);
    }


    /**
     * Testet, dass `addRemoval()` die Einfuegereihenfolge beibehaelt (FIFO).
     */
    public function testAddRemovalPreservesInsertionOrder(): void
    {
        // Anordnen
        $plan = new ProjectionPlan('my_projection');

        // Ausfuehren
        $plan->addRemoval('field_a');
        $plan->addRemoval('field_b');
        $plan->addRemoval('field_c');
        $result = $plan->removals();

        // Assert
        $this->assertSame('field_a', $result[0]);
        $this->assertSame('field_b', $result[1]);
        $this->assertSame('field_c', $result[2]);
    }
}
