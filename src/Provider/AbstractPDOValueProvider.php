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

use Generator;
use Skyline\FormBuilder\Definition\ValuePromise;
use TASoft\Util\PDO;

abstract class AbstractPDOValueProvider implements ValueProviderInterface
{
	/** @var PDO */
	protected $PDO;
	protected $cache;

	/**
	 * PDOValueProvider constructor.
	 * @param PDO $PDO
	 */
	public function __construct(PDO $PDO)
	{
		$this->PDO = $PDO;
	}

	/**
	 * Yield all keys.
	 * There is a $key => $record map expected where the record gets cached for further use.
	 * if $record is a string, it's used as key.
	 *
	 * @param PDO $PDO
	 * @param $nameField
	 * @return Generator
	 */
	abstract protected function yieldPreflight(PDO $PDO);

	/**
	 * This method gets called on value demand
	 *
	 * @param PDO $PDO
	 * @param string $key
	 * @param $record
	 * @return mixed|callable|ValuePromise
	 */
	abstract protected function makeGetter(PDO $PDO, string $key, $record);

	/**
	 * @inheritDoc
	 */
	public function getProvidedValueKeys(): array
	{
		if(NULL === $this->cache) {
			$this->cache = [];
			foreach($this->yieldPreflight($this->PDO) as $key => $record) {
				if(is_string($record))
					$key = $record;
				$this->cache[ $key ] = $record;
			}
		}
		return array_keys($this->cache);
	}

	/**
	 * @inheritDoc
	 */
	public function getProvidedValue($key)
	{
		if(!isset($this->cache))
			$this->getProvidedValueKeys();
		if(isset($this->cache[$key]))
			return $this->makeGetter($this->PDO, $key, $this->cache[$key]);
		return NULL;
	}

	/**
	 * Gets the cached record if available
	 *
	 * @param string $key
	 * @return mixed|null
	 */
	public function getCachedRecord(string $key) {
		return $this->cache[$key] ?? NULL;
	}
}