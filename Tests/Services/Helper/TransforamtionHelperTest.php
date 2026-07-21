<?php

/**
 * @since       09.07.2026 - 09:24
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */

declare(strict_types=1);

namespace NetGroup\DataTransformationLayer\Tests\Services\Helper;

use NetGroup\DataTransformationLayer\Classes\Definition\ConversionContext;
use NetGroup\DataTransformationLayer\Classes\Engine\DatasetTransformer;
use NetGroup\DataTransformationLayer\Classes\Services\Factories\DefinitionFactory;
use NetGroup\DataTransformationLayer\Classes\Services\Helper\TransforamtionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransforamtionHelperTest extends TestCase
{


    /**
     * @var (DefinitionFactory&MockObject)|MockObject
     */
    private $factory;


    /**
     * @var (DatasetTransformer&MockObject)|MockObject
     */
    private $transformer;


    private $context;


    /**
     * @var TransforamtionHelper
     */
    private $helper;


    protected function setUp(): void
    {
        $this->factory      = $this->getMockBuilder(DefinitionFactory::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $this->transformer  = $this->getMockBuilder(DatasetTransformer::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $this->context      = $this->createStub(ConversionContext::class);

        $this->helper = new TransforamtionHelper($this->factory, $this->transformer);
    }


    /**
     * @return void
     *
     * @throws \JsonException
     */
    public function testTransform(): void
    {
        $projectionName = 'test_projection';
        $rows           = [['id' => 12, 'title' => 'test title'], ['id' => 34, 'title' => 'test title 002']];
        $options        = ['timezone' => 'Europe/Berlin'];

        $this->factory->expects($this->once())
                      ->method('createConversionContext')
                      ->with($projectionName, $options)
                      ->willReturn($this->context);

        $this->transformer->expects($this->once())
                          ->method('transform')
                          ->with($rows, $this->context)
                          ->willReturn($rows);

        $this->assertSame($rows, $this->helper->transform($projectionName, $rows, $options));
    }
}
