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

        $this->planMock = $this->getMockBuilder(ProjectionPlan::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->registryMock
            ->expects($this->once())
            ->method('getPlan')
            ->with($projectionName)
            ->willReturn($this->planMock);

        $this->planMock
            ->method('stepsByField')
            ->willReturn([]);

        // Ausführen
        $this->transformer->transform($rows, $projectionName, $this->contextMock);
    }
}


/**
 * Hilfs-Interface für Tests, das sowohl `FieldConverterInterface` als auch
 * `PrefetchingConverterInterface` kombiniert, um Mocks für Prefetching-Converter zu erstellen.
 */
interface PrefetchingFieldConverterInterface extends FieldConverterInterface, PrefetchingConverterInterface
{
}
