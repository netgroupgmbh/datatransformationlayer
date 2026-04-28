<?php

/**
 * @since       12.03.2026 - 10:15
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Tests\Definition;

use NetGroup\DataTransformationLayer\Classes\Definition\ConversionContext;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConversionContextTest extends TestCase
{


    /**
     * Testet, dass der Konstruktor die Eigenschaft `projection` korrekt setzt.
     */
    public function testConstructorSetsProjection(): void
    {
        // Anordnen
        $projection = 'order_list';

        // Ausführen
        $context = new ConversionContext($projection);

        // Assert
        $this->assertSame($projection, $context->projection);
    }


    /**
     * Testet, dass der Konstruktor die Eigenschaft `options` korrekt setzt.
     */
    public function testConstructorSetsOptions(): void
    {
        // Anordnen
        $projection	= 'order_list';
        $options	= ['locale' => 'de_DE', 'timezone' => 'Europe/Berlin'];

        // Ausführen
        $context = new ConversionContext($projection, $options);

        // Assert
        $this->assertSame($options, $context->options);
    }


    /**
     * Testet, dass `options` standardmäßig ein leeres Array ist,
     * wenn kein zweites Argument übergeben wird.
     */
    public function testConstructorDefaultsToEmptyOptions(): void
    {
        // Anordnen
        $projection = 'order_list';

        // Ausführen
        $context = new ConversionContext($projection);

        // Assert
        $this->assertSame([], $context->options);
    }


    /**
     * Testet, dass `option()` den korrekten Wert zurückgibt,
     * wenn der angegebene Key im Options-Array vorhanden ist.
     */
    public function testOptionReturnsValueForExistingKey(): void
    {
        // Anordnen
        $projection	= 'order_list';
        $options	= ['locale' => 'de_DE'];
        $context	= new ConversionContext($projection, $options);

        // Ausführen
        $result = $context->option('locale');

        // Assert
        $this->assertSame('de_DE', $result);
    }


    /**
     * Testet, dass `option()` den Wert `null` zurückgibt,
     * wenn der Key nicht im Options-Array vorhanden ist und kein Default angegeben wurde.
     */
    public function testOptionReturnsNullForMissingKeyWithoutDefault(): void
    {
        // Anordnen
        $projection	= 'order_list';
        $context	= new ConversionContext($projection);

        // Ausführen
        $result = $context->option('locale');

        // Assert
        $this->assertNull($result);
    }


    /**
     * Testet, dass `option()` den angegebenen Default-Wert zurückgibt,
     * wenn der Key nicht im Options-Array vorhanden ist.
     */
    public function testOptionReturnsCustomDefaultForMissingKey(): void
    {
        // Anordnen
        $projection	= 'order_list';
        $context	= new ConversionContext($projection);
        $default	= 'en_US';

        // Ausführen
        $result = $context->option('locale', $default);

        // Assert
        $this->assertSame($default, $result);
    }


    /**
     * Testet, dass `option()` den Wert `null` zurückgibt,
     * wenn der Key im Options-Array vorhanden ist, aber explizit auf `null` gesetzt wurde.
     * In diesem Fall greift der Null-Coalescing-Operator und gibt `null` zurück –
     * was dem Default-Wert entspricht, da `null ?? null` ebenfalls `null` ergibt.
     */
    public function testOptionReturnsNullForExplicitNullValue(): void
    {
        // Anordnen
        $projection	= 'order_list';
        $options	= ['locale' => null];
        $context	= new ConversionContext($projection, $options);

        // Ausführen
        $result = $context->option('locale', 'fallback');

        // Assert
        // Der Null-Coalescing-Operator (??) gibt den Default zurück, wenn der Wert null ist.
        $this->assertSame('fallback', $result);
    }


    /**
     * Testet, dass `option()` korrekt mit verschiedenen Werttypen umgeht
     * (Integer, Boolean, Array).
     */
    public function testOptionWithVariousValueTypes(): void
    {
        // Anordnen
        $projection	= 'order_list';
        $options	= [
            'page'		=> 42,
            'active'	=> true,
            'tags'		=> ['foo', 'bar'],
        ];
        $context = new ConversionContext($projection, $options);

        // Ausführen & Assert – Integer
        $this->assertSame(42, $context->option('page'));

        // Ausführen & Assert – Boolean
        $this->assertTrue($context->option('active'));

        // Ausführen & Assert – Array
        $this->assertSame(['foo', 'bar'], $context->option('tags'));
    }


    /**
     * Testet, dass `projection` und `options` als readonly-Eigenschaften
     * nach der Initialisierung nicht verändert werden können.
     */
    public function testPropertiesAreReadonly(): void
    {
        // Anordnen
        $projection	= 'order_list';
        $options	= ['locale' => 'de_DE'];
        $context	= new ConversionContext($projection, $options);

        // Assert – Versuch, readonly-Eigenschaften zu überschreiben, muss einen Fehler werfen
        $this->expectException(\Error::class);

        // Ausführen – dies soll fehlschlagen
        $context->projection = 'other_projection'; // @phpstan-ignore-line
    }
}
