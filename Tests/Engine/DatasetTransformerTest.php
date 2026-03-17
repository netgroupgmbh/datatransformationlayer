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

use NetGroup\DataTransformationLayer\Classes\Converter\FieldConverterInterface;
use NetGroup\DataTransformationLayer\Classes\Converter\PrefetchingConverterInterface;
use NetGroup\DataTransformationLayer\Classes\Definition\ConversionContext;
use NetGroup\DataTransformationLayer\Classes\Definition\ConversionStep;
use NetGroup\DataTransformationLayer\Classes\Definition\FieldAddition;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use NetGroup\DataTransformationLayer\Classes\Engine\DatasetTransformer;
use NetGroup\DataTransformationLayer\Classes\Engine\ProjectionRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DatasetTransformerTest extends TestCase
{


    /**
     * @var ProjectionRegistry&MockObject
     */
    private ProjectionRegistry $registryMock;


    /**
     * @var ContainerInterface&MockObject
     */
    private ContainerInterface $locatorMock;


    /**
     * @var ConversionContext&MockObject
     */
    private ConversionContext $contextMock;


    /**
     * @var ProjectionPlan&MockObject
     */
    private ProjectionPlan $planMock;


    /**
     * @var DatasetTransformer
     */
    private DatasetTransformer $transformer;


    protected function setUp(): void
    {
        $this->registryMock = $this->getMockBuilder(ProjectionRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->locatorMock = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();

        $this->contextMock = $this->getMockBuilder(ConversionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->planMock = $this->createPlanMock();

        $this->transformer = new DatasetTransformer($this->registryMock, $this->locatorMock);
    }


    /**
     * Testet, dass `transform()` ein leeres Array zurückgibt,
     * wenn das übergebene Rows-Array leer ist.
     */
    public function testTransformReturnsEmptyArrayWhenRowsIsEmpty(): void
    {
        // Anordnen
        $rows           = [];
        $projectionName = 'test_projection';

        // Ausführen
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert
        $this->assertSame([], $result);
    }


    /**
     * Testet, dass `transform()` bei einem leeren Rows-Array die Registry
     * nicht aufruft (Kurzschluss-Optimierung).
     */
    public function testTransformDoesNotCallRegistryWhenRowsIsEmpty(): void
    {
        // Anordnen
        $this->registryMock
            ->expects($this->never())
            ->method('getPlan');

        // Ausführen
        $this->transformer->transform([], 'test_projection', $this->contextMock);
    }


    /**
     * Testet, dass `transform()` einen einzelnen Row korrekt transformiert,
     * indem der Converter für das entsprechende Feld aufgerufen wird.
     */
    public function testTransformAppliesConverterToField(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['title' => 'hello']];
        $step           = new ConversionStep('MyConverter', []);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->method('convert')
            ->willReturn('HELLO');

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['title' => [$step]]);

        $this->registryMock
            ->method('getPlan')
            ->with($projectionName)
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->with('MyConverter')
            ->willReturn($converterMock);

        // Ausführen
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert
        $this->assertSame('HELLO', $result[0]['title']);
    }


    /**
     * Testet, dass `transform()` den Converter mit den korrekten Argumenten aufruft
     * (Wert, kompletter Row, Context, Params).
     */
    public function testTransformCallsConverterWithCorrectArguments(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $params         = ['locale' => 'de'];
        $rows           = [['price' => 100]];
        $step           = new ConversionStep('PriceConverter', $params);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->expects($this->once())
            ->method('convert')
            ->with(100, $rows[0], $this->contextMock, $params)
            ->willReturn('100,00 €');

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['price' => [$step]]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausführen
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass `transform()` mehrere Schritte für dasselbe Feld
     * sequenziell ausführt und den Ausgabewert des vorherigen Schritts
     * als Eingabewert des nächsten Schritts verwendet (Chaining).
     */
    public function testTransformChainsMultipleStepsForSameField(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['value' => 'hello']];
        $step1          = new ConversionStep('ConverterA', []);
        $step2          = new ConversionStep('ConverterB', []);

        $converterA = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterA
            ->method('convert')
            ->willReturn('HELLO');

        $converterB = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterB
            ->method('convert')
            ->with('HELLO')
            ->willReturn('HELLO!');

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['value' => [$step1, $step2]]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturnMap([
                ['ConverterA', $converterA],
                ['ConverterB', $converterB],
            ]);

        // Ausführen
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – der Endwert ist das Ergebnis der verketteten Konvertierungen
        $this->assertSame('HELLO!', $result[0]['value']);
    }


    /**
     * Testet, dass `transform()` mehrere Rows korrekt transformiert.
     */
    public function testTransformHandlesMultipleRows(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [
            ['name' => 'alice'],
            ['name' => 'bob'],
        ];
        $step = new ConversionStep('UpperConverter', []);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->method('convert')
            ->willReturnCallback(static fn (mixed $value): string => \strtoupper((string) $value));

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['name' => [$step]]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausführen
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert
        $this->assertSame('ALICE', $result[0]['name']);
        $this->assertSame('BOB', $result[1]['name']);
    }


    /**
     * Testet, dass `transform()` für ein Feld, das in einem Row nicht vorhanden ist,
     * `null` als Eingabewert an den Converter übergibt.
     */
    public function testTransformPassesNullForMissingFieldInRow(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['other_field' => 'value']];
        $step           = new ConversionStep('MyConverter', []);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->expects($this->once())
            ->method('convert')
            ->with(null)
            ->willReturn(null);

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['missing_field' => [$step]]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausführen
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – das fehlende Feld wird mit null-Wert im Ergebnis gesetzt
        $this->assertNull($result[0]['missing_field']);
    }


    /**
     * Testet, dass `transform()` eine `LogicException` wirft,
     * wenn der Service aus dem Locator kein `FieldConverterInterface` implementiert.
     */
    public function testTransformThrowsLogicExceptionWhenServiceIsNotFieldConverter(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['field' => 'value']];
        $step           = new ConversionStep('InvalidService', []);

        // Ein Objekt, das kein FieldConverterInterface implementiert
        $invalidService = new \stdClass();

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['field' => [$step]]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($invalidService);

        // Assert
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "InvalidService" is not a FieldConverterInterface.');

        // Ausführen
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass `transform()` für einen Converter, der `PrefetchingConverterInterface`
     * implementiert, `prefetch()` genau einmal aufruft (vor der Row-Verarbeitung).
     */
    public function testTransformCallsPrefetchOnPrefetchingConverter(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $params         = ['ids' => [1, 2, 3]];
        $rows           = [['id' => 1], ['id' => 2]];
        $step           = new ConversionStep('PrefetchingConverter', $params);

        // Mock, der beide Interfaces implementiert
        $prefetchingConverterMock = $this->getMockBuilder(PrefetchingFieldConverterInterface::class)->getMock();
        $prefetchingConverterMock
            ->expects($this->once())
            ->method('prefetch')
            ->with($rows, $this->contextMock, $params);

        $prefetchingConverterMock
            ->method('convert')
            ->willReturnArgument(0);

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['id' => [$step]]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($prefetchingConverterMock);

        // Ausführen
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass `transform()` `prefetch()` für denselben Converter (gleiche Klasse + gleiche Params)
     * nur einmal aufruft, auch wenn der Converter für mehrere Felder verwendet wird (Deduplizierung).
     */
    public function testTransformCallsPrefetchOnlyOnceForSameConverterAndParams(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $params         = ['key' => 'value'];
        $rows           = [['field1' => 'a', 'field2' => 'b']];
        $step1          = new ConversionStep('SharedConverter', $params);
        $step2          = new ConversionStep('SharedConverter', $params);

        $prefetchingConverterMock = $this->getMockBuilder(PrefetchingFieldConverterInterface::class)->getMock();

        // prefetch() darf nur einmal aufgerufen werden, obwohl der Converter für zwei Felder genutzt wird
        $prefetchingConverterMock
            ->expects($this->once())
            ->method('prefetch');

        $prefetchingConverterMock
            ->method('convert')
            ->willReturnArgument(0);

        $this->planMock
            ->method('stepsByField')
            ->willReturn([
                'field1' => [$step1],
                'field2' => [$step2],
            ]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($prefetchingConverterMock);

        // Ausführen
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass `transform()` `prefetch()` zweimal aufruft, wenn derselbe Converter
     * mit unterschiedlichen Params verwendet wird (unterschiedliche Prefetch-Keys).
     */
    public function testTransformCallsPrefetchTwiceForSameConverterWithDifferentParams(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $params1        = ['locale' => 'de'];
        $params2        = ['locale' => 'en'];
        $rows           = [['field1' => 'a', 'field2' => 'b']];
        $step1          = new ConversionStep('SharedConverter', $params1);
        $step2          = new ConversionStep('SharedConverter', $params2);

        $prefetchingConverterMock = $this->getMockBuilder(PrefetchingFieldConverterInterface::class)->getMock();

        // prefetch() muss zweimal aufgerufen werden, da die Params unterschiedlich sind
        $prefetchingConverterMock
            ->expects($this->exactly(2))
            ->method('prefetch');

        $prefetchingConverterMock
            ->method('convert')
            ->willReturnArgument(0);

        $this->planMock
            ->method('stepsByField')
            ->willReturn([
                'field1' => [$step1],
                'field2' => [$step2],
            ]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($prefetchingConverterMock);

        // Ausführen
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass `transform()` für einen normalen Converter (ohne Prefetching)
     * `prefetch()` nicht aufruft.
     */
    public function testTransformDoesNotCallPrefetchForNonPrefetchingConverter(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['field' => 'value']];
        $step           = new ConversionStep('SimpleConverter', []);

        // Nur FieldConverterInterface, kein PrefetchingConverterInterface
        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->method('convert')
            ->willReturnArgument(0);

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['field' => [$step]]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausführen – darf keine Exception werfen und kein prefetch() aufrufen
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – der Wert bleibt unverändert
        $this->assertSame('value', $result[0]['field']);
    }


    /**
     * Testet, dass `transform()` die ursprünglichen Felder eines Rows beibehält,
     * die nicht im Projektionsplan enthalten sind.
     */
    public function testTransformPreservesFieldsNotInProjectionPlan(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['title' => 'hello', 'untouched' => 'original']];
        $step           = new ConversionStep('MyConverter', []);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->method('convert')
            ->willReturn('HELLO');

        $this->planMock
            ->method('stepsByField')
            ->willReturn(['title' => [$step]]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($this->planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausführen
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – das nicht transformierte Feld bleibt unverändert
        $this->assertSame('original', $result[0]['untouched']);
    }


    /**
     * Testet, dass `transform()` den korrekten Projektionsnamen an die Registry übergibt.
     */
    public function testTransformCallsRegistryWithCorrectProjectionName(): void
    {
        // Anordnen
        $projectionName = 'my_special_projection';
        $rows           = [['field' => 'value']];

        $this->planMock
            ->method('stepsByField')
            ->willReturn([]);

        $this->registryMock
            ->expects($this->once())
            ->method('getPlan')
            ->with($projectionName)
            ->willReturn($this->planMock);

        // Ausführen
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass `transform()` ein neues Feld via FieldAddition zum Row hinzufuegt.
     */
    public function testTransformAddsNewFieldViaAddition(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['quantity' => 3, 'unit_price' => 100]];
        $addition       = new FieldAddition('total_price', 'CalcConverter', ['op' => 'multiply'], '');

        $planMock = $this->createPlanMock([$addition]);
        $planMock
            ->method('stepsByField')
            ->willReturn([]);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->method('convert')
            ->willReturn(300);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausfuehren
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – das neue Feld ist vorhanden
        $this->assertArrayHasKey('total_price', $result[0]);
        $this->assertSame(300, $result[0]['total_price']);
        // Originalfelder bleiben erhalten
        $this->assertSame(3, $result[0]['quantity']);
        $this->assertSame(100, $result[0]['unit_price']);
    }


    /**
     * Testet, dass `transform()` bei einer Addition mit sourceField den Wert
     * des Quellfeldes als Eingangswert an den Converter uebergibt.
     */
    public function testTransformAdditionUsesSourceFieldValue(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['price' => 100]];
        $addition       = new FieldAddition('formatted_price', 'FormatConverter', ['currency' => 'EUR'], 'price');

        $planMock = $this->createPlanMock([$addition]);
        $planMock
            ->method('stepsByField')
            ->willReturn([]);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->expects($this->once())
            ->method('convert')
            ->with(100, $rows[0], $this->contextMock, ['currency' => 'EUR'])
            ->willReturn('100,00 €');

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausfuehren
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert
        $this->assertSame('100,00 €', $result[0]['formatted_price']);
    }


    /**
     * Testet, dass `transform()` bei einer Addition ohne sourceField (leerer String)
     * null als Eingangswert an den Converter uebergibt.
     */
    public function testTransformAdditionPassesNullWhenSourceFieldIsEmpty(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['quantity' => 3, 'unit_price' => 100]];
        $addition       = new FieldAddition('total_price', 'CalcConverter', [], '');

        $planMock = $this->createPlanMock([$addition]);
        $planMock
            ->method('stepsByField')
            ->willReturn([]);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->expects($this->once())
            ->method('convert')
            ->with(null)
            ->willReturn(300);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausfuehren
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass `transform()` Felder via Removal aus dem Output entfernt.
     */
    public function testTransformRemovesFieldsViaRemoval(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['quantity' => 3, 'unit_price' => 100, 'name' => 'Widget']];

        $planMock = $this->createPlanMock([], ['quantity', 'unit_price']);
        $planMock
            ->method('stepsByField')
            ->willReturn([]);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        // Ausfuehren
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – entfernte Felder sind nicht mehr vorhanden
        $this->assertArrayNotHasKey('quantity', $result[0]);
        $this->assertArrayNotHasKey('unit_price', $result[0]);
        // Nicht entfernte Felder bleiben erhalten
        $this->assertSame('Widget', $result[0]['name']);
    }


    /**
     * Testet die Reihenfolge Convert -> Add -> Remove:
     * Ein bestehendes Feld wird konvertiert, ein neues Feld hinzugefuegt,
     * und ein Feld entfernt.
     */
    public function testTransformExecutesConvertThenAddThenRemove(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['quantity' => 3, 'unit_price' => 100]];
        $step           = new ConversionStep('UpperConverter', []);
        $addition       = new FieldAddition('total', 'CalcConverter', [], '');

        $planMock = $this->createPlanMock([$addition], ['unit_price']);
        $planMock
            ->method('stepsByField')
            ->willReturn(['quantity' => [$step]]);

        $upperConverter = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $upperConverter
            ->method('convert')
            ->willReturn(5);

        $calcConverter = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $calcConverter
            ->method('convert')
            ->willReturn(500);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        $this->locatorMock
            ->method('get')
            ->willReturnMap([
                ['UpperConverter', $upperConverter],
                ['CalcConverter', $calcConverter],
            ]);

        // Ausfuehren
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – Convert wurde ausgefuehrt
        $this->assertSame(5, $result[0]['quantity']);
        // Assert – Addition wurde hinzugefuegt
        $this->assertSame(500, $result[0]['total']);
        // Assert – Removal wurde ausgefuehrt
        $this->assertArrayNotHasKey('unit_price', $result[0]);
    }


    /**
     * Testet, dass `transform()` fuer einen Addition-Converter, der `PrefetchingConverterInterface`
     * implementiert, `prefetch()` aufruft.
     */
    public function testTransformCallsPrefetchOnAdditionConverter(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $params         = ['lookup' => true];
        $rows           = [['id' => 1]];
        $addition       = new FieldAddition('label', 'PrefetchingConverter', $params, 'id');

        $planMock = $this->createPlanMock([$addition]);
        $planMock
            ->method('stepsByField')
            ->willReturn([]);

        $prefetchingConverterMock = $this->getMockBuilder(PrefetchingFieldConverterInterface::class)->getMock();
        $prefetchingConverterMock
            ->expects($this->once())
            ->method('prefetch')
            ->with($rows, $this->contextMock, $params);

        $prefetchingConverterMock
            ->method('convert')
            ->willReturnArgument(0);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($prefetchingConverterMock);

        // Ausfuehren
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass Prefetch ueber Convert-Steps und Additions hinweg dedupliziert wird:
     * Wenn derselbe Converter mit denselben Params sowohl in einem Step als auch in einer
     * Addition verwendet wird, wird `prefetch()` nur einmal aufgerufen.
     */
    public function testTransformDeduplicatesPrefetchAcrossStepsAndAdditions(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $params         = ['key' => 'value'];
        $rows           = [['field1' => 'a']];
        $step           = new ConversionStep('SharedConverter', $params);
        $addition       = new FieldAddition('new_field', 'SharedConverter', $params, '');

        $planMock = $this->createPlanMock([$addition]);
        $planMock
            ->method('stepsByField')
            ->willReturn(['field1' => [$step]]);

        $prefetchingConverterMock = $this->getMockBuilder(PrefetchingFieldConverterInterface::class)->getMock();

        // prefetch() darf nur einmal aufgerufen werden
        $prefetchingConverterMock
            ->expects($this->once())
            ->method('prefetch');

        $prefetchingConverterMock
            ->method('convert')
            ->willReturnArgument(0);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($prefetchingConverterMock);

        // Ausfuehren
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }


    /**
     * Testet, dass `transform()` mehrere Rows korrekt mit Additions und Removals verarbeitet.
     */
    public function testTransformHandlesMultipleRowsWithAdditionsAndRemovals(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [
            ['name' => 'alice', 'age' => 30],
            ['name' => 'bob', 'age' => 25],
        ];
        $addition       = new FieldAddition('greeting', 'GreetConverter', [], 'name');

        $planMock = $this->createPlanMock([$addition], ['age']);
        $planMock
            ->method('stepsByField')
            ->willReturn([]);

        $converterMock = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $converterMock
            ->method('convert')
            ->willReturnCallback(static fn (mixed $value): string => 'Hello ' . $value);

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        $this->locatorMock
            ->method('get')
            ->willReturn($converterMock);

        // Ausfuehren
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – Additions
        $this->assertSame('Hello alice', $result[0]['greeting']);
        $this->assertSame('Hello bob', $result[1]['greeting']);
        // Assert – Removals
        $this->assertArrayNotHasKey('age', $result[0]);
        $this->assertArrayNotHasKey('age', $result[1]);
        // Assert – nicht entfernte Felder bleiben erhalten
        $this->assertSame('alice', $result[0]['name']);
        $this->assertSame('bob', $result[1]['name']);
    }


    /**
     * Testet, dass Additions auf bereits konvertierte Werte zugreifen koennen,
     * da Convert vor Add ausgefuehrt wird.
     */
    public function testAdditionCanAccessConvertedValues(): void
    {
        // Anordnen
        $projectionName = 'test_projection';
        $rows           = [['price' => 100]];
        $step           = new ConversionStep('DoubleConverter', []);
        $addition       = new FieldAddition('formatted', 'FormatConverter', [], 'price');

        $planMock = $this->createPlanMock([$addition]);
        $planMock
            ->method('stepsByField')
            ->willReturn(['price' => [$step]]);

        $doubleConverter = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $doubleConverter
            ->method('convert')
            ->willReturnCallback(static fn (mixed $value): int => (int) $value * 2);

        // Der FormatConverter erhaelt den bereits konvertierten Wert (200),
        // da er nach dem Convert ausgefuehrt wird.
        $formatConverter = $this->getMockBuilder(FieldConverterInterface::class)->getMock();
        $formatConverter
            ->method('convert')
            ->willReturnCallback(static fn (mixed $value): string => $value . ' EUR');

        $this->registryMock
            ->method('getPlan')
            ->willReturn($planMock);

        $this->locatorMock
            ->method('get')
            ->willReturnMap([
                ['DoubleConverter', $doubleConverter],
                ['FormatConverter', $formatConverter],
            ]);

        // Ausfuehren
        $result = $this->transformer->transform($rows, $projectionName, $this->contextMock);

        // Assert – Convert wurde ausgefuehrt
        $this->assertSame(200, $result[0]['price']);
        // Assert – Addition hat den konvertierten Wert erhalten
        $this->assertSame('200 EUR', $result[0]['formatted']);
    }


    /**
     * Erstellt einen frischen ProjectionPlan-Mock mit leeren Defaults
     * fuer additions() und removals().
     *
     * @param list<FieldAddition> $additions
     * @param list<string>        $removals
     *
     * @return ProjectionPlan&MockObject
     */
    private function createPlanMock(array $additions = [], array $removals = []): ProjectionPlan
    {
        $plan = $this->getMockBuilder(ProjectionPlan::class)
            ->disableOriginalConstructor()
            ->getMock();

        $plan->method('additions')->willReturn($additions);
        $plan->method('removals')->willReturn($removals);

        return $plan;
    }
}


/**
 * Hilfs-Interface für Tests, das sowohl `FieldConverterInterface` als auch
 * `PrefetchingConverterInterface` kombiniert, um Mocks für Prefetching-Converter zu erstellen.
 */
interface PrefetchingFieldConverterInterface extends FieldConverterInterface, PrefetchingConverterInterface
{
}
