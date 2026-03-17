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

class ProjectionPlan
{
    /**
     * @var array<string, list<ConversionStep>>
     */
    private array $stepsByField = [];

    /**
     * @var list<FieldAddition>
     */
    private array $additions = [];

    /**
     * @var list<string>
     */
    private array $removals = [];


    /**
     * @param string $name
     */
    public function __construct(public readonly string $name)
    {
    }


    /**
     * @param string         $field
     * @param ConversionStep $step
     *
     * @return void
     */
    public function addStep(string $field, ConversionStep $step): void
    {
        $this->stepsByField[$field] ??= [];
        $this->stepsByField[$field][] = $step;
    }


    /**
     * @param FieldAddition $addition
     *
     * @return void
     */
    public function addAddition(FieldAddition $addition): void
    {
        $this->additions[] = $addition;
    }


    /**
     * @param string $field
     *
     * @return void
     */
    public function addRemoval(string $field): void
    {
        $this->removals[] = $field;
    }


    /**
     * @return array<string, list<ConversionStep>>
     */
    public function stepsByField(): array
    {
        return $this->stepsByField;
    }


    /**
     * @return list<FieldAddition>
     */
    public function additions(): array
    {
        return $this->additions;
    }


    /**
     * @return list<string>
     */
    public function removals(): array
    {
        return $this->removals;
    }
}
