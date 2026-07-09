<?php

/**
 * @since       09.07.2026 - 09:14
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Services\Helper;

use NetGroup\DataTransformationLayer\Classes\Engine\DatasetTransformer;
use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;

class TransforamtionHelper
{


    /**
     * @param DefinitionFactory  $factory
     * @param DatasetTransformer $transformer
     */
    public function __construct(
        private readonly DefinitionFactory $factory,
        private readonly DatasetTransformer $transformer
    ) {
    }


    /**
     * @param string                           $projectionName
     * @param array<int, array<string, mixed>> $rows
     * @param mixed[]                          $options
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \JsonException
     */
    public function transform(string $projectionName, array $rows, array $options = []): array
    {
        $context = $this->factory->createConversionContext($projectionName, $options);

        return $this->transformer->transform($rows, $context);
    }
}
