<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2021, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Skyline\FormBuilder\Representation\Generator;


use Skyline\FormBuilder\Definition\Type\ValueTypeInterface;
use Skyline\FormBuilder\Definition\ValueDefinitionInterface;
use Skyline\FormBuilder\Definition\ValuePromise;

class ChainRepresentationGenerator implements RepresentationGeneratorInterface
{
	/** @var RepresentationGeneratorInterface[] */
	private $generators;

	/**
	 * ChainRepresentationGenerator constructor.
	 * @param RepresentationGeneratorInterface[] $generators
	 */
	public function __construct(...$generators)
	{
		$fn = function($p) use (&$fn) {
			if(is_iterable($p)) {
				foreach ($p as $value)
					$fn($value);
			} elseif($p instanceof RepresentationGeneratorInterface)
				$this->addGenerator($p);
		};
		$fn($generators);
	}

	/**
	 * Adds a provider to the chain
	 *
	 * @param RepresentationGeneratorInterface $generator
	 * @return ChainRepresentationGenerator
	 */
	public function addGenerator(RepresentationGeneratorInterface $generator): ChainRepresentationGenerator
	{
		if(!in_array($generator, $this->generators)) {
			$this->generators[] = $generator;
		}
		return $this;
	}

	/**
	 * @param RepresentationGeneratorInterface $generator
	 * @return ChainRepresentationGenerator
	 */
	public function removeGenerator(RepresentationGeneratorInterface $generator): ChainRepresentationGenerator
	{
		if(($idx = array_search($generator, $this->generators)) !== false) {
			unset($this->generators[$idx]);
		}
		return $this;
	}

	/**
	 * @param RepresentationGeneratorInterface $generator
	 * @return bool
	 */
	public function hasGenerator(RepresentationGeneratorInterface $generator): bool {
		return in_array($generator, $this->generators);
	}

	/**
	 * @inheritDoc
	 */
	public function generateRepresentation(string $key, ValueDefinitionInterface $definition, ValueTypeInterface $type, ValuePromise $defaultValue)
	{
		foreach($this->getGenerators() as $generator) {
			if($g = $generator->generateRepresentation($key, $definition, $type, $defaultValue))
				return $g;
		}
		return NULL;
	}

	/**
	 * @return RepresentationGeneratorInterface[]
	 */
	public function getGenerators(): array
	{
		return $this->generators;
	}
}