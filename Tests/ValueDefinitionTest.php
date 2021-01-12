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

use PHPUnit\Framework\TestCase;
use Skyline\FormBuilder\Definition\DescribedListingValueDefinition;
use Skyline\FormBuilder\Definition\DescribedValueDefinition;
use Skyline\FormBuilder\Definition\ValueDefinition as ValueDefinitionAlias;

class ValueDefinitionTest extends TestCase
{
	public function testValueDefinition() {
		$vd = new ValueDefinitionAlias('string', 89);
		$this->assertSame('string', $vd->getValueType());
		$this->assertSame(89, $vd->getOptions());
	}

	public function testDescribedValueDefinition() {
		$vd = new DescribedValueDefinition("number", 'Hello', 'Here I am', 'Uhh', 16);
		$this->assertSame('number', $vd->getValueType());
		$this->assertSame(16, $vd->getOptions());
		$this->assertSame('Hello', $vd->getLabel());
		$this->assertSame('Here I am', $vd->getDescription());
		$this->assertSame('Uhh', $vd->getPlaceholder());
	}

	public function testListingDefinition() {
		$vd = new DescribedListingValueDefinition("popup", [
			1, 2, 3
		]);

		$this->assertSame([1,2,3], $vd->getAvailableValueList());
		$this->assertSame('popup', $vd->getValueType());
	}
}
