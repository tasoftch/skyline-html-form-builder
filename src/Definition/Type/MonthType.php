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

namespace Skyline\FormBuilder\Definition\Type;

class MonthType implements ValueTypeInterface
{
	const SHORT_NAME_OPTION = 1;
	const LONG_NAME_OPTION = 2;

	protected $shortNames = [
		1 => 'January',
		2 => 'February',
		3 => 'March',
		4 => 'April',
		5 => 'May',
		6 => 'June',
		7 => 'July',
		8 => 'August',
		9 => 'September',
		10 => 'October',
		11 => 'November',
		12 => 'December',
	];
	protected $longNames = [
		1 => 'Jan',
		2 => 'Feb',
		3 => 'Mar',
		4 => 'Apr',
		5 => 'May',
		6 => 'Jun',
		7 => 'Jul',
		8 => 'Aug',
		9 => 'Sep',
		10 => 'Oct',
		11 => 'Nov',
		12 => 'Dec',
	];

	/**
	 * MonthType constructor.
	 * @param string[] $shortNames
	 * @param string[] $longNames
	 */
	public function __construct(array $shortNames = NULL, array $longNames = NULL)
	{
		if($shortNames)
			$this->shortNames = $shortNames;
		if($longNames)
			$this->longNames = $longNames;
	}


	public function getName(): string
	{
		return "month";
	}

	public function getAvailableValueList()
	{
		return $this->longNames;
	}


	public function toValue(?string $scalarRepresentation, int $options)
	{
		switch ($options) {
			case static::SHORT_NAME_OPTION:
				return $this->shortNames[ $scalarRepresentation*1 ] ?? NULL;
			case static::LONG_NAME_OPTION:
				return $this->longNames[ $scalarRepresentation*1 ] ?? NULL;
			default:
				return $scalarRepresentation * 1;
		}
	}

	public function toScalar($value, int $options): ?string
	{
		if(($idx = array_search($value, $this->longNames)) !== false)
			return $idx;
		if(($idx = array_search($value, $this->shortNames)) !== false)
			return $idx;
		return $value*1;
	}
}