<?php

/**
 * @since       26.02.2026 - 13:54
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Definition;

class ConversionStep
{


    /**
     * @param class-string         $converterClass
     * @param array<string, mixed> $params
     */
    public function __construct(public readonly string $converterClass, public readonly array $params = [])
    {
    }
}
