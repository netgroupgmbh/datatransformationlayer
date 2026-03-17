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

use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;

class FieldAdditionBuilder
{


    /**
     * @param ProjectionPlan    $plan
     * @param DefinitionFactory $factory
     * @param string            $targetField
     */
    public function __construct(
        private readonly ProjectionPlan $plan,
        private readonly DefinitionFactory $factory,
        private readonly string $targetField,
    ) {
    }


    /**
     * @param class-string         $converterClass
     * @param array<string, mixed> $params
     * @param string               $sourceField
     *
     * @return self
     */
    public function compute(string $converterClass, array $params = [], string $sourceField = ''): self
    {
        $this->plan->addAddition(
            $this->factory->createFieldAddition($this->targetField, $converterClass, $params, $sourceField)
        );

        return $this;
    }
}
