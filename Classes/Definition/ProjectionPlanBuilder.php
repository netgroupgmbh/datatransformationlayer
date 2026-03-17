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
     * Erstellt einen Builder fuer ein neues, berechnetes Feld.
     *
     * @param string $targetField
     *
     * @return FieldAdditionBuilder
     */
    public function addField(string $targetField): FieldAdditionBuilder
    {
        return $this->factory->createFieldAdditionBuilder($this->plan, $targetField);
    }


    /**
     * Markiert ein Feld zur Entfernung aus dem Output.
     *
     * @param string $field
     *
     * @return self
     */
    public function removeField(string $field): self
    {
        $this->plan->addRemoval($field);

        return $this;
    }


    /**
     * @return ProjectionPlan
     */
    public function getPlan(): ProjectionPlan
    {
        return $this->plan;
    }
}
