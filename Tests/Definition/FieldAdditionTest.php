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
use PHPUnit\Framework\TestCase;

class FieldAdditionTest extends TestCase
{


    /**
     * Testet, dass der Konstruktor die Eigenschaft `targetField` korrekt setzt.
     */
    public function testConstructorSetsTargetField(): void
    {
        // Anordnen
        $targetField = 'total_price';

        // Ausfuehren
        $addition = new FieldAddition($targetField, 'SomeConverter', [], '');

        // Assert
        $this->assertSame($targetField, $addition->targetField);
    }


    /**
     * Testet, dass der Konstruktor die Eigenschaft `converterClass` korrekt setzt.
     */
    public function testConstructorSetsConverterClass(): void
    {
        // Anordnen
        $converterClass = 'MyConverter';

        // Ausfuehren
        $addition = new FieldAddition('field', $converterClass, [], '');

        // Assert
        $this->assertSame($converterClass, $addition->converterClass);
    }


    /**
     * Testet, dass der Konstruktor die Eigenschaft `params` korrekt setzt.
     */
    public function testConstructorSetsParams(): void
    {
        // Anordnen
        $params = ['key' => 'value', 'number' => 42];

        // Ausfuehren
        $addition = new FieldAddition('field', 'SomeConverter', $params, '');

        // Assert
        $this->assertSame($params, $addition->params);
    }


    /**
     * Testet, dass der Konstruktor die Eigenschaft `sourceField` korrekt setzt.
     */
    public function testConstructorSetsSourceField(): void
    {
        // Anordnen
        $sourceField = 'unit_price';

        // Ausfuehren
        $addition = new FieldAddition('field', 'SomeConverter', [], $sourceField);

        // Assert
        $this->assertSame($sourceField, $addition->sourceField);
    }


    /**
     * Testet, dass `params` standardmaessig ein leeres Array ist.
     */
    public function testConstructorDefaultParamsIsEmptyArray(): void
    {
        // Ausfuehren
        $addition = new FieldAddition('field', 'SomeConverter');

        // Assert
        $this->assertSame([], $addition->params);
    }


    /**
     * Testet, dass `sourceField` standardmaessig ein leerer String ist.
     */
    public function testConstructorDefaultSourceFieldIsEmptyString(): void
    {
        // Ausfuehren
        $addition = new FieldAddition('field', 'SomeConverter');

        // Assert
        $this->assertSame('', $addition->sourceField);
    }


    /**
     * Testet, dass die Eigenschaft `targetField` als readonly deklariert ist
     * und nach der Initialisierung nicht veraendert werden kann.
     */
    public function testTargetFieldPropertyIsReadonly(): void
    {
        // Anordnen
        $addition = new FieldAddition('field', 'SomeConverter');

        // Assert
        $this->expectException(\Error::class);

        // Ausfuehren
        $addition->targetField = 'other'; // @phpstan-ignore-line
    }


    /**
     * Testet, dass die Eigenschaft `converterClass` als readonly deklariert ist
     * und nach der Initialisierung nicht veraendert werden kann.
     */
    public function testConverterClassPropertyIsReadonly(): void
    {
        // Anordnen
        $addition = new FieldAddition('field', 'SomeConverter');

        // Assert
        $this->expectException(\Error::class);

        // Ausfuehren
        $addition->converterClass = 'other'; // @phpstan-ignore-line
    }


    /**
     * Testet, dass die Eigenschaft `params` als readonly deklariert ist
     * und nach der Initialisierung nicht veraendert werden kann.
     */
    public function testParamsPropertyIsReadonly(): void
    {
        // Anordnen
        $addition = new FieldAddition('field', 'SomeConverter');

        // Assert
        $this->expectException(\Error::class);

        // Ausfuehren
        $addition->params = ['new' => 'value']; // @phpstan-ignore-line
    }


    /**
     * Testet, dass die Eigenschaft `sourceField` als readonly deklariert ist
     * und nach der Initialisierung nicht veraendert werden kann.
     */
    public function testSourceFieldPropertyIsReadonly(): void
    {
        // Anordnen
        $addition = new FieldAddition('field', 'SomeConverter');

        // Assert
        $this->expectException(\Error::class);

        // Ausfuehren
        $addition->sourceField = 'other'; // @phpstan-ignore-line
    }
}
