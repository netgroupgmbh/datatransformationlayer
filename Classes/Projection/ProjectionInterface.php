<?php

/**
 * @since       26.02.2026 - 13:58
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Projection;

use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlanBuilder;

interface ProjectionInterface
{

    /**
     * @return string
     */
    public function name(): string;


    /**
     * @param ProjectionPlanBuilder $builder
     *
     * @return void
     */
    public function build(ProjectionPlanBuilder $builder): void;
}
