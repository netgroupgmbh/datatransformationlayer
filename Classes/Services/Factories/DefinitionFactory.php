<?php

/**
 * @since       26.02.2026 - 14:47
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Services\Factories;

use NetGroup\DataTransformationLayer\Classes\Definition\ConversionContext;
use NetGroup\DataTransformationLayer\Classes\Definition\ConversionStep;
use NetGroup\DataTransformationLayer\Classes\Definition\FieldAddition;
use NetGroup\DataTransformationLayer\Classes\Definition\FieldAdditionBuilder;
use NetGroup\DataTransformationLayer\Classes\Definition\FieldRuleBuilder;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlanBuilder;

class DefinitionFactory
{


    /**
     * @param string  $projection
     * @param mixed[] $options
     *
     * @return ConversionContext
     */
    public function createConversionContext(string $projection, array $options = []): ConversionContext
    {
        return new ConversionContext($projection, $options);
    }


    /**
     * @param class-string         $converterClass
     * @param array<string, mixed> $params
     *
     * @return ConversionStep
     */
    public function createConversionStep(string $converterClass, array $params): ConversionStep
    {
        return new ConversionStep($converterClass, $params);
    }


    /**
     * @param string $name
     *
     * @return ProjectionPlan
     */
    public function createProjectionPlan(string $name): ProjectionPlan
    {
        return new ProjectionPlan($name);
    }


    /**
     * @param string               $targetField
     * @param class-string         $converterClass
     * @param array<string, mixed> $params
     * @param string               $sourceField
     *
     * @return FieldAddition
     */
    public function createFieldAddition(
        string $targetField,
        string $converterClass,
        array $params,
        string $sourceField,
    ): FieldAddition {
        return new FieldAddition($targetField, $converterClass, $params, $sourceField);
    }


    /**
     * @param ProjectionPlan $plan
     * @param string         $field
     *
     * @return FieldRuleBuilder
     */
    public function createFieldRuleBuilder(ProjectionPlan $plan, string $field): FieldRuleBuilder
    {
        return new FieldRuleBuilder($plan, $this, $field);
    }


    /**
     * @param ProjectionPlan $plan
     * @param string         $targetField
     *
     * @return FieldAdditionBuilder
     */
    public function createFieldAdditionBuilder(ProjectionPlan $plan, string $targetField): FieldAdditionBuilder
    {
        return new FieldAdditionBuilder($plan, $this, $targetField);
    }


    /**
     * @param string $name
     *
     * @return ProjectionPlanBuilder
     */
    public function createProjectionPlanBuilder(string $name): ProjectionPlanBuilder
    {
        return new ProjectionPlanBuilder($name, $this);
    }
}
