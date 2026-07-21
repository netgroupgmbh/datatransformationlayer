<?php

/**
 * @since       16.07.2026 - 10:42
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Services\Helper;

class ArrayColumnSorter
{


    /**
     * Sortiert die Spalten eines Arrays.
     *
     * @param array<array-key, array<string|int, mixed>> $rows  Array mit den zu sortiereneden Daten
     * @param array<int|string>                          $order Array mit den Namen der Felder, in der Reihnfolge in der das Datenarray sortiert wird
     *
     * @return mixed[]
     */
    public function sort(array $rows, array $order): array
    {
        return array_map(static fn (array $row) => array_replace(array_flip($order), $row), $rows);
    }
}
