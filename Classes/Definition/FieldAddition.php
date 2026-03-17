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

namespace NetGroup\DataTransformationLayer\Classes\Definition;

class FieldAddition
{


    /**
     * @param string               $targetField    Name des neuen Feldes im Output
     * @param class-string         $converterClass FQCN des FieldConverterInterface
     * @param array<string, mixed> $params         Converter-Parameter
     * @param string               $sourceField    Optionales Quellfeld (leer = null als Eingangswert)
     */
    public function __construct(
        public readonly string $targetField,
        public readonly string $converterClass,
        public readonly array $params = [],
        public readonly string $sourceField = '',
    ) {
    }
}
