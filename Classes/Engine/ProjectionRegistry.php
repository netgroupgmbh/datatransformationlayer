<?php

/**
 * @since       26.02.2026 - 14:07
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Engine;

use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlan;
use NetGroup\DataTransformationLayer\Classes\Projection\ProjectionInterface;
use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;

class ProjectionRegistry
{


    /**
     * @var array<string, ProjectionPlan>
     */
    private array $cache = [];

    /**
     * @var array<string, ProjectionInterface>
     */
    private array $projectionsByName = [];


    /**
     * @param iterable<ProjectionInterface> $projections
     */
    public function __construct(iterable $projections, private readonly DefinitionFactory $factory)
    {
        foreach ($projections as $projection) {
            $this->projectionsByName[$projection->name()] = $projection;
        }
    }


    /**
     * @param string $name
     *
     * @return ProjectionPlan
     */
    public function getPlan(string $name): ProjectionPlan
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $projection = $this->projectionsByName[$name] ?? null;
        if (!$projection) {
            throw new \InvalidArgumentException(sprintf('Unknown projection "%s".', $name));
        }

        $builder = $this->factory->createProjectionPlanBuilder($name);
        $projection->build($builder);

        return $this->cache[$name] = $builder->getPlan();
    }
}
