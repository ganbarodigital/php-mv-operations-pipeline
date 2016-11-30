<?php

/**
 * Copyright (c) 2016-present Ganbaro Digital Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Libraries
 * @package   InstructionPipeline/PipelineBuilders
 * @author    Stuart Herbert <stuherbert@ganbarodigital.com>
 * @copyright 2016-present Ganbaro Digital Ltd www.ganbarodigital.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://ganbarodigital.github.io/php-mv-instruction-pipeline
 */

namespace GanbaroDigital\InstructionPipeline\V1\PipelineBuilders;

use GanbaroDigital\InstructionPipeline\V1\InstructionPipeline;
use GanbaroDigital\InstructionPipeline\V1\InstructionBuilders\FoDiInstructionBuilder;
use GanbaroDigital\InstructionPipeline\V1\InstructionBuilders\ReDiInstructionBuilder;
use GanbaroDigital\InstructionPipeline\V1\Requirements\RequireValidInstruction;
use GanbaroDigital\InstructionPipeline\V1\Requirements\RequireValidInstructionBuilder;

/**
 * assemble a pipeline of instructions to execute
 */
class BuildInstructionPipeline
{
    /**
     * assemble a pipeline of instructions to execute
     *
     * @param  array $definition
     *         a list of the required instruction builders, and the configs
     *         for each builder
     * @param  int $directions
     *         which pipelines do we want to build? (bitwise mask)
     * @return array
     *         the assembled pipelines
     */
    public static function from($definition, $directions = InstructionPipeline::DI_FORWARD|InstructionPipeline::DI_REVERSE)
    {
        // the pipelines that we are building
        $pipelines = [
            InstructionPipeline::DI_FORWARD => [],
            InstructionPipeline::DI_REVERSE => []
        ];

        // how we build each pipeline
        $directionTypes = [
            InstructionPipeline::DI_FORWARD => [
                'interface' => FoDiInstructionBuilder::class,
                'method' => 'buildForwardInstructionFrom',
            ],
            InstructionPipeline::DI_REVERSE => [
                'interface' => ReDiInstructionBuilder::class,
                'method' => 'buildReverseInstructionFrom'
            ],
        ];

        // assemble the pipelines
        foreach($definition as $builderClass => $config) {
            // robustness
            RequireValidInstructionBuilder::apply()->to($builderClass);

            foreach ($directionTypes as $direction => $details) {
                if ($directions & $direction) {
                    // we can add this when the reflection-types package is out
                    // RequireCompatibleWith::apply($details['interface'])->to($builderClass);

                    // build the instruction
                    $funcName = $details['method'];
                    $instruction = $builderClass::$funcName($config);
                    RequireValidInstruction::apply()->to($instruction);

                    // remember it
                    $pipelines[$direction][] = $instruction;
                }
            }
        }

        // special case - the reverse pipeline is currently in the wrong
        // order
        $pipelines[InstructionPipeline::DI_REVERSE] = array_reverse($pipelines[InstructionPipeline::DI_REVERSE]);

        // all done
        return $pipelines;
    }
}