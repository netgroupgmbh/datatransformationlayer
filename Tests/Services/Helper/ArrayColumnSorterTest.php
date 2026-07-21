<?php

/**
 * @since       16.07.2026 - 14:21
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Tests\Services\Helper;

use NetGroup\DataTransformationLayer\Classes\Services\Helper\ArrayColumnSorter;
use PHPUnit\Framework\TestCase;

class ArrayColumnSorterTest extends TestCase
{

    public const ORDER = [
        'gender',
        'title',
        'lastname',
        'firstname',
        'title_lastname',
        'email',
        'special_functions',
        'root_organization',
        'place_of_employment',
        'function',
        'has_leadership_function',
        'leadership_function',
        'service_type',
        'landline_number',
        'dect_number',
        'fax_number',
        'mobile_phone_number'
    ];


    /**
     * @var ArrayColumnSorter
     */
    private ArrayColumnSorter $sorter;


    protected function setUp(): void
    {
        $this->sorter = new ArrayColumnSorter();
    }


    /**
     * Ein leeres Array wird unverändert zurückgegeben.
     */
    public function testSortWithEmptyArrayReturnsEmptyArray(): void
    {
        // Ausführen
        $result = $this->sorter->sort([], self::ORDER);

        // Assert
        $this->assertSame([], $result);
    }


    /**
     * Eine einzelne Zeile mit allen Spalten in der korrekten Reihenfolge
     * wird in der definierten ORDER-Reihenfolge zurückgegeben.
     */
    public function testSortReturnsColumnsInDefinedOrder(): void
    {
        // Anordnen – Spalten absichtlich in umgekehrter Reihenfolge
        $row = [
            'mobile_phone_number'   => '0171 111111',
            'fax_number'            => '030 222222',
            'dect_number'           => '1234',
            'landline_number'       => '030 333333',
            'service_type'          => 'Vollzeit',
            'leadership_function'   => 'Teamleitung',
            'has_leadership_function' => '1',
            'function'              => 'Entwickler',
            'place_of_employment'   => 'Berlin',
            'root_organization'     => 'NetGroup GmbH',
            'special_functions'     => 'Datenschutz',
            'email'                 => 'test@example.com',
            'title_lastname'        => 'Dr. Mustermann',
            'firstname'             => 'Max',
            'lastname'              => 'Mustermann',
            'title'                 => 'Dr.',
            'gender'                => 'm',
        ];

        // Ausführen
        $result = $this->sorter->sort([$row], self::ORDER);

        // Assert – die Schlüssel müssen in der ORDER-Reihenfolge vorliegen
        $keys = array_keys($result[0]);
        $this->assertSame(self::ORDER, $keys);
    }


    /**
     * Mehrere Zeilen werden alle korrekt sortiert zurückgegeben.
     */
    public function testSortSortsMultipleRows(): void
    {
        // Anordnen
        $rows = [
            [
                'lastname'  => 'Mustermann',
                'firstname' => 'Max',
                'gender'    => 'm',
            ],
            [
                'firstname' => 'Erika',
                'gender'    => 'w',
                'lastname'  => 'Musterfrau',
            ],
        ];

        // Ausführen
        $result = $this->sorter->sort($rows, self::ORDER);

        // Assert – beide Zeilen sind vorhanden und die Spalten beginnen mit 'gender'
        $this->assertCount(2, $result);
        $this->assertSame('gender', array_key_first($result[0]));
        $this->assertSame('gender', array_key_first($result[1]));
    }


    /**
     * Spalten, die nicht in ORDER definiert sind, werden ans Ende angehängt.
     */
    public function testSortAppendsUnknownColumnsAtEnd(): void
    {
        // Anordnen – 'unknown_field' ist nicht in ORDER enthalten
        $row = [
            'gender'        => 'm',
            'lastname'      => 'Mustermann',
            'unknown_field' => 'Wert',
        ];

        // Ausführen
        $result = $this->sorter->sort([$row], self::ORDER);

        // Assert – 'unknown_field' ist vorhanden und steht nach den ORDER-Spalten
        $this->assertArrayHasKey('unknown_field', $result[0]);
        $keys           = array_keys($result[0]);
        $lastKey        = end($keys);
        $this->assertSame('unknown_field', $lastKey);
    }


    /**
     * Spalten aus ORDER, die in der Eingabezeile fehlen, werden als Platzhalter
     * mit einem Integer-Wert (Index aus array_flip) aufgefüllt und sind im Ergebnis vorhanden.
     */
    public function testSortFillsMissingOrderColumnsWithPlaceholder(): void
    {
        // Anordnen – nur 'gender' und 'lastname' sind vorhanden
        $row = [
            'gender'    => 'm',
            'lastname'  => 'Mustermann',
        ];

        // Ausführen
        $result = $this->sorter->sort([$row], self::ORDER);

        // Assert – alle ORDER-Spalten sind im Ergebnis vorhanden
        foreach (self::ORDER as $column) {
            $this->assertArrayHasKey($column, $result[0]);
        }

        // Assert – vorhandene Werte sind korrekt übernommen
        $this->assertSame('m', $result[0]['gender']);
        $this->assertSame('Mustermann', $result[0]['lastname']);

        // Assert – fehlende ORDER-Spalten erhalten einen Integer-Platzhalter (Index aus array_flip)
        $this->assertIsInt($result[0]['title']);
        $this->assertIsInt($result[0]['firstname']);
        $this->assertIsInt($result[0]['email']);
    }


    /**
     * Vorhandene Werte werden durch das Sortieren nicht verändert.
     */
    public function testSortPreservesValues(): void
    {
        // Anordnen
        $row = [
            'gender'    => 'w',
            'lastname'  => 'Musterfrau',
            'firstname' => 'Erika',
            'email'     => 'erika@example.com',
        ];

        // Ausführen
        $result = $this->sorter->sort([$row], self::ORDER);

        // Assert – Werte sind unverändert
        $this->assertSame('w', $result[0]['gender']);
        $this->assertSame('Musterfrau', $result[0]['lastname']);
        $this->assertSame('Erika', $result[0]['firstname']);
        $this->assertSame('erika@example.com', $result[0]['email']);
    }


    /**
     * Eine Zeile, die ausschließlich Spalten enthält, die nicht in ORDER definiert sind,
     * wird mit allen ORDER-Spalten als Integer-Platzhalter (Index aus array_flip) vorangestellt
     * und die unbekannten Spalten werden angehängt.
     */
    public function testSortWithOnlyUnknownColumnsPrependsOrderColumns(): void
    {
        // Anordnen
        $row = ['custom_a' => 'A', 'custom_b' => 'B'];

        // Ausführen
        $result = $this->sorter->sort([$row], self::ORDER);

        // Assert – alle ORDER-Spalten sind vorhanden und enthalten Integer-Platzhalter
        foreach (self::ORDER as $column) {
            $this->assertArrayHasKey($column, $result[0]);
            $this->assertIsInt($result[0][$column]);
        }

        // Assert – unbekannte Spalten sind ebenfalls vorhanden und ihre Werte sind erhalten
        $this->assertArrayHasKey('custom_a', $result[0]);
        $this->assertArrayHasKey('custom_b', $result[0]);
        $this->assertSame('A', $result[0]['custom_a']);
        $this->assertSame('B', $result[0]['custom_b']);
    }


    /**
     * Die Anzahl der zurückgegebenen Zeilen entspricht der Anzahl der Eingabezeilen.
     */
    public function testSortReturnsCorrectRowCount(): void
    {
        // Anordnen
        $rows = [
            ['gender' => 'm', 'lastname' => 'Alpha'],
            ['gender' => 'w', 'lastname' => 'Beta'],
            ['gender' => 'd', 'lastname' => 'Gamma'],
        ];

        // Ausführen
        $result = $this->sorter->sort($rows, self::ORDER);

        // Assert
        $this->assertCount(3, $result);
    }


    /**
     * Die ORDER-Konstante enthält alle erwarteten Spaltennamen in der richtigen Reihenfolge.
     */
    public function testOrderConstantContainsExpectedColumns(): void
    {
        // Anordnen
        $expectedOrder = [
            'gender',
            'title',
            'lastname',
            'firstname',
            'title_lastname',
            'email',
            'special_functions',
            'root_organization',
            'place_of_employment',
            'function',
            'has_leadership_function',
            'leadership_function',
            'service_type',
            'landline_number',
            'dect_number',
            'fax_number',
            'mobile_phone_number',
        ];

        // Assert
        $this->assertSame($expectedOrder, self::ORDER);
    }
}
