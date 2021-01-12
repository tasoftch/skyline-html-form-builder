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

use TASoft\Util\PDO;

class StorableSimpleSQLPDOValueProvider extends SimpleSQLPDOValueProvider implements ValueStorageInterface
{
	/** @var string|callable */
	private $storeSQL;

	public function __construct(PDO $PDO, string $SQL, $storeSQL, array $keyMap = [])
	{
		parent::__construct($PDO, $SQL, $keyMap);
		$this->storeSQL = $storeSQL;
	}

	/**
	 * @inheritDoc
	 */
	protected function makeSetter(PDO $PDO, string $key, $record, $value)
	{
		if(is_callable($s = $this->storeSQL)) {
			$s($PDO, $key, $record, $value);
		} else {
			$id = $record[ $k = $this->getMap(static::ID_FIELD) ] ?? $record[ $k = $this->getMap(static::NAME_FIELD) ];
			if(!$id)
				$id = $key;

			$PDO->inject($s)->send([
				$value,
				$id
			]);
		}
	}

	public function saveValues(array $changedValues)
	{
		$keys = $this->getProvidedValueKeys();
		foreach($changedValues as $key => $value) {
			if(in_array($key, $keys)) {
				$this->makeSetter($this->PDO, $key, $this->cache[$key], $value);
			}
		}
	}
}