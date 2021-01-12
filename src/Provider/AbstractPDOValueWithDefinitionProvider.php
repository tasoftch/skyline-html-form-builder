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

namespace Skyline\FormBuilder\Provider;


use Skyline\FormBuilder\Definition\DescribedListingValueDefinition;
use Skyline\FormBuilder\Definition\DescribedValueDefinition;
use Skyline\FormBuilder\Definition\ListProvider\ListProviderInterface;
use Skyline\FormBuilder\Definition\ValueDefinition;
use Skyline\FormBuilder\Definition\ValueDefinitionInterface;
use Skyline\FormBuilder\Definition\ValueDefinitionProviderInterface;
use TASoft\Util\PDO;

abstract class AbstractPDOValueWithDefinitionProvider extends AbstractPDOValueProvider implements ValueDefinitionProviderInterface
{
	/**
	 * @param PDO $PDO
	 * @param string $key
	 * @param $record
	 * @param string|null $valueType
	 * @param int|null $options
	 * @param string|null $label
	 * @param string|null $description
	 * @param string|null $placeholder
	 * @param array|ListProviderInterface $requiredValueList
	 * @return bool
	 */
	abstract protected function makeDefinition(PDO $PDO, string $key, $record, ?string &$valueType, ?int &$options, ?string &$label, ?string &$description, ?string &$placeholder, &$requiredValueList): bool;

	/**
	 * @inheritDoc
	 */
	public function getValueDefinition($key): ?ValueDefinitionInterface
	{
		if(!isset($this->cache))
			$this->getProvidedValueKeys();
		if(isset($this->cache[$key]) && $this->makeDefinition($this->PDO, $key, $this->cache[$key], $type, $options, $label, $desc, $placeholder, $list)) {
			if($list)
				return new DescribedListingValueDefinition($type, $list, $label, $desc, $placeholder, $options);
			if($label||$desc||$placeholder)
				return new DescribedValueDefinition($type, $label, $desc, $placeholder, $options);
			return new ValueDefinition($type, $options);
		}
		return NULL;
	}
}