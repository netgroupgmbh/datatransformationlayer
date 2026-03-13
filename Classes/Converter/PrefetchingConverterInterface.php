<?php

/**
 * @since       26.02.2026 - 07:59
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

interface PrefetchingConverterInterface
{


    /**
     * Möglichkeit, IDs zu sammeln und Cache aufzubauen.
     * Wird 1x pro Dataset aufgerufen, bevor convert() pro Row ausgeführt wird.
     *
     * @param mixed[]           $rows
     * @param ConversionContext $context
     * @param mixed[]           $params
     *
     * @return void
     */
    public function prefetch(array $rows, ConversionContext $context, array $params = []): void;
}
