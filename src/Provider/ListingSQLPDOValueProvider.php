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
use TASoft\Util\Record\RecordTransformerAdapter;
use TASoft\Util\Record\StackByTransformer;

class ListingSQLPDOValueProvider extends SimpleSQLPDOValueProvider
{
	const LIST_FIELD = 'list';
	const GROUP_FIELD = self::ID_FIELD;

	const JOIN_LIST_FIELD = 'list';

	/** @var string */
	private $listTableName, $listItemTableName;

	public function __construct(PDO $PDO, string $tableName, string $listTableName = NULL, string $listItemTableName = NULL, array $keyMap = [])
	{
		$keyMap = array_merge([
			static::LIST_FIELD => 'list',
			static::GROUP_FIELD => 'id'
		], $keyMap);
		parent::__construct($PDO, $tableName, $keyMap);

		$this->listTableName = $listTableName ? $listTableName : "{$tableName}_LIST";
		$this->listItemTableName = $listItemTableName ? $listItemTableName : "{$this->listTableName}_ITEM";
	}

	/**
	 * @inheritDoc
	 */
	protected function yieldPreflight(PDO $PDO)
	{
		$t = $this->getSQL();
		foreach((
			new RecordTransformerAdapter(
				new StackByTransformer([static::GROUP_FIELD], [static::LIST_FIELD]),
				$PDO->select($this->getSQL())
			)
		)() as $record) {
			if(isset($record[ $this->getMap(static::NAME_FIELD)]) && isset($record[ $this->getMap(static::VALUE_FIELD) ])) {
				yield $record[ $this->getMap(static::NAME_FIELD) ] => $record;
			}
		}
	}

	protected function makeDefinition(PDO $PDO, string $key, $record, ?string &$valueType, ?int &$options, ?string &$label, ?string &$description, ?string &$placeholder, &$requiredValueList): bool
	{
		$k = $this->getMap(static::LIST_FIELD);
		if(isset($record[$k]) && $record[$k])
			$requiredValueList = $record[$k];

		return parent::makeDefinition($PDO, $key, $record, $valueType, $options, $label, $description, $placeholder, $requiredValueList);
	}
}