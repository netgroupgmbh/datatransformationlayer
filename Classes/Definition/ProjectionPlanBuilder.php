<?php

/**
 * @since       26.02.2026 - 13:55
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

class ProjectionPlanBuilder
{


    /**
     * @var ProjectionPlan
     */
    private ProjectionPlan $plan;


    /**
     * @param string $name
     */
    public function __construct(string $name, private readonly DefinitionFactory $factory)
    {
        $this->plan = $this->factory->createProjectionPlan($name);
    }


    /**
     * @param string $field
     *
     * @return FieldRuleBuilder
     */
    public function field(string $field): FieldRuleBuilder
    {
        return $this->factory->createFieldRuleBuilder($this->plan, $field);
    }


    /**
     * @return ProjectionPlan
     */
    public function getPlan(): ProjectionPlan
    {
        return $this->plan;
    }
}
