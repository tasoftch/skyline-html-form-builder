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
use Skyline\FormBuilder\Definition\ListProvider\ListProviderInterface;
use Skyline\FormBuilder\Definition\ValuePromise;
use TASoft\Util\PDO;

class SimpleSQLPDOValueProvider extends AbstractPDOValueWithDefinitionProvider
{
	/** @var string */
	private $SQL;

	const ID_FIELD = 'id';
	const NAME_FIELD = 'name';
	const VALUE_FIELD = 'value';

	const TYPE_FIELD = 'valueType';
	const OPTIONS_FIELD = 'options';

	const LABEL_FIELD = 'label';
	const DESCRIPTION_FIELD = 'description';
	const PLACEHOLDER_FIELD = 'placeholder';


	protected $keyMap = [
		self::ID_FIELD => 'id',
		self::NAME_FIELD => 'name',
		self::VALUE_FIELD => 'value',
		self::TYPE_FIELD => 'valueType',
		self::OPTIONS_FIELD => 'options',
		self::LABEL_FIELD => 'label',
		self::DESCRIPTION_FIELD => 'description',
		self::PLACEHOLDER_FIELD => 'placeholder'
	];

	public function __construct(PDO $PDO, string $SQL, array $keyMap = [])
	{
		parent::__construct($PDO);
		$this->SQL = $SQL;
		$this->keyMap = array_merge($this->keyMap, $keyMap);
	}

	/**
	 * @return string
	 */
	public function getSQL(): string
	{
		return $this->SQL;
	}

	protected function getMap($k) {
		return $this->keyMap[$k] ?? $k;
	}

	/**
	 * @inheritDoc
	 */
	protected function yieldPreflight(PDO $PDO)
	{
		foreach($PDO->select($this->getSQL()) as $record) {
			if(isset($record[ $this->getMap(static::NAME_FIELD)]) && isset($record[ $this->getMap(static::VALUE_FIELD) ])) {
				yield $record[ $this->getMap(static::NAME_FIELD) ] => $record;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function makeGetter(PDO $PDO, string $key, $record)
	{
		return $record[ $this->getMap(static::VALUE_FIELD) ];
	}

	/**
	 * @inheritDoc
	 */
	protected function makeDefinition(PDO $PDO, string $key, $record, ?string &$valueType, ?int &$options, ?string &$label, ?string &$description, ?string &$placeholder, &$requiredValueList): bool
	{
		$fetch = function($km, $default = NULL) use ($record) {
			$k = $this->keyMap[ $km ] ?? $km;
			return $record[$k] ?? $default;
		};

		$valueType = $fetch(static::TYPE_FIELD, 'string');
		$options = $fetch(static::OPTIONS_FIELD, 0);
		$label = $fetch(static::LABEL_FIELD);
		$description = $fetch(static::DESCRIPTION_FIELD);
		$placeholder = $fetch(static::PLACEHOLDER_FIELD);

		return true;
	}
}