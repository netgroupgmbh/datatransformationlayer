<?php

/**
 * @since       26.02.2026 - 13:56
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

class FieldRuleBuilder
{


    /**
     * @param ProjectionPlan    $plan
     * @param DefinitionFactory $factory
     * @param string            $field
     */
    public function __construct(
        private readonly ProjectionPlan $plan,
        private readonly DefinitionFactory $factory,
        private readonly string $field,
    ) {
    }


    /**
     * @param class-string         $converterClass
     * @param array<string, mixed> $params
     */
    public function convert(string $converterClass, array $params = []): self
    {
        $this->plan->addStep($this->field, $this->factory->createConversionStep($converterClass, $params));

        return $this;
    }
}
