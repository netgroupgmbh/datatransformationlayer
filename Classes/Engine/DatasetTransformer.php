<?php

/**
 * @since       26.02.2026 - 14:08
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Classes\Engine;

use NetGroup\DataTransformationLayer\Classes\Converter\FieldConverterInterface;
use NetGroup\DataTransformationLayer\Classes\Converter\PrefetchingConverterInterface;
use NetGroup\DataTransformationLayer\Classes\Definition\ConversionContext;
use NetGroup\DataTransformationLayer\Classes\Definition\ConversionStep;
use Psr\Container\ContainerInterface;

class DatasetTransformer
{


    /**
     * @param ProjectionRegistry $projectionRegistry
     * @param ContainerInterface $converterLocator
     */
    public function __construct(
        private readonly ProjectionRegistry $projectionRegistry,
        private readonly ContainerInterface $converterLocator, // ServiceLocator
    ) {
    }


    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string                           $projectionName
     * @param ConversionContext                $context
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \JsonException
     */
    public function transform(array $rows, string $projectionName, ConversionContext $context): array
    {
        if ([] === $rows) {
            return [];
        }

        $plan = $this->projectionRegistry->getPlan($projectionName);

        // 1) Prefetch (dedupliziert nach ConverterClass + Params)
        $prefetched = [];

        foreach ($plan->stepsByField() as $field => $steps) {
            foreach ($steps as $step) {
                $key = $this->prefetchKey($step);
                if (isset($prefetched[$key])) {
                    continue;
                }

                $converter = $this->getConverter($step->converterClass);

                if ($converter instanceof PrefetchingConverterInterface) {
                    $converter->prefetch($rows, $context, $step->params);
                }

                $prefetched[$key] = true;
            }
        }

        // 2) Pro Row konvertieren
        foreach ($rows as $i => $row) {
            foreach ($plan->stepsByField() as $field => $steps) {
                $value = $row[$field] ?? null;

                foreach ($steps as $step) {
                    $converter  = $this->getConverter($step->converterClass);
                    $value      = $converter->convert($value, $row, $context, $step->params);
                }

                $row[$field] = $value;
            }

            $rows[$i] = $row;
        }

        return $rows;
    }


    /**
     * @param ConversionStep $step
     *
     * @return string
     *
     * @throws \JsonException
     */
    private function prefetchKey(ConversionStep $step): string
    {
        return $step->converterClass . '|' . \md5(\json_encode($step->params, JSON_THROW_ON_ERROR));
    }


    /**
     * @param string $converterClass
     *
     * @return FieldConverterInterface
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function getConverter(string $converterClass): FieldConverterInterface
    {
        $service = $this->converterLocator->get($converterClass);

        if (!$service instanceof FieldConverterInterface) {
            throw new \LogicException(sprintf('Service "%s" is not a FieldConverterInterface.', $converterClass));
        }

        return $service;
    }
}
