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


use Skyline\FormBuilder\Definition\ValueDefinitionInterface;

final class ChainValueProvider implements ValueDefinitionProviderInterface, ValueProviderInterface, ValueStorageInterface
{
	private $providers = [];
	private $_KK;

	/**
	 * Pass providers as array or comma separated to the chain provider.
	 *
	 * ChainValueProvider constructor.
	 * @param mixed ...$providers
	 */
	public function __construct(...$providers)
	{
		$fn = function($p) use (&$fn) {
			if(is_iterable($p)) {
				foreach ($p as $value)
					$fn($value);
			} elseif($p instanceof ValueProviderInterface)
				$this->addProvider($p);
		};
		$fn($providers);
	}

	/**
	 * Adds a provider to the chain
	 *
	 * @param ValueProviderInterface $provider
	 * @return ChainValueProvider
	 */
	public function addProvider(ValueProviderInterface $provider): ChainValueProvider
	{
		if(!in_array($provider, $this->providers)) {
			$this->providers[] = $provider;
			$this->_KK = NULL;
		}
		return $this;
	}

	/**
	 * @param ValueProviderInterface $provider
	 * @return ChainValueProvider
	 */
	public function removeProvider(ValueProviderInterface $provider): ChainValueProvider
	{
		if(($idx = array_search($provider, $this->providers)) !== false) {
			unset($this->providers[$idx]);
			$this->_KK = NULL;
		}
		return $this;
	}

	/**
	 * @param ValueProviderInterface $provider
	 * @return bool
	 */
	public function hasProvider(ValueProviderInterface $provider): bool {
		return in_array($provider, $this->providers);
	}

	private function _initialize() {
		if(NULL === $this->_KK) {
			$this->_KK = [];

			/** @var ValueProviderInterface $p */
			foreach($this->providers as $p) {
				foreach($p->getProvidedValueKeys() as $k)
					$this->_KK[$k] = $p;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getProvidedValueKeys(): array
	{
		$this->_initialize();
		return array_keys($this->_KK);
	}

	/**
	 * @inheritDoc
	 */
	public function getProvidedValue($key)
	{
		$this->_initialize();
		$k = $this->_KK[$key];
		return $k instanceof ValueProviderInterface ? $k->getValueDefinition($key) : NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function getValueDefinition($key): ?ValueDefinitionInterface
	{
		$this->_initialize();
		$k = $this->_KK[$key];
		return $k instanceof ValueDefinitionProviderInterface ? $k->getValueDefinition($key) : NULL;
	}


	/**
	 * @inheritDoc
	 */
	public function saveValues(array $changedValues)
	{
		$list = [];
		$this->_initialize();
		foreach($changedValues as $k => $v) {
			if($p = $this->_KK[$k]) {
				$idx = array_search($p, $this->providers);
				$list[$idx][$k] = $v;
			}
		}

		foreach($list as $idx => $values) {
			$this->providers[ $idx ]->storeValues($values);
		}
	}
}