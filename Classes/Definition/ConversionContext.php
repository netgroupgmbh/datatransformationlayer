<?php

/**
 * @since       26.02.2026 - 08:02
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Definition;

class ConversionContext
{


    /**
     * @param string  $projection
     * @param mixed[] $options
     */
    public function __construct(
        public readonly string $projection,     // z.B. "order_list"
        public readonly array $options = [],    // z.B. locale, timezone, user, ...
    ) {
    }


    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
}
