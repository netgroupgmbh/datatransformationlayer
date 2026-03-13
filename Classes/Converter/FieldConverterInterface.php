<?php

/**
 * @since       26.02.2026 - 07:58
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Converter;

use NetGroup\DataTransformationLayer\Classes\Definition\ConversionContext;

interface FieldConverterInterface
{


    /**
     * @return string
     */
    public function name(): string;


    /**
     * @param mixed             $value   aktueller Feldwert
     * @param mixed[]           $row     kompletter Datensatz (für Cross-Field-Logik)
     * @param ConversionContext $context
     * @param mixed[]           $params  Converter-Parameter
     *
     * @return mixed
     */
    public function convert(mixed $value, array $row, ConversionContext $context, array $params = []): mixed;
}
