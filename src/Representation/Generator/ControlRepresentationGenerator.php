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


use Skyline\FormBuilder\Definition\CustomListingValueDefinitionInterface;
use Skyline\FormBuilder\Definition\DescribedValueDefinitionInterface;
use Skyline\FormBuilder\Definition\ListProvider\ListProviderInterface;
use Skyline\FormBuilder\Definition\Type\BooleanType;
use Skyline\FormBuilder\Definition\Type\DateType;
use Skyline\FormBuilder\Definition\Type\EmailTypeValueType;
use Skyline\FormBuilder\Definition\Type\HTMLType;
use Skyline\FormBuilder\Definition\Type\IntegralOptionType;
use Skyline\FormBuilder\Definition\Type\MonthType;
use Skyline\FormBuilder\Definition\Type\PasswordType;
use Skyline\FormBuilder\Definition\Type\StringType;
use Skyline\FormBuilder\Definition\Type\TextType;
use Skyline\FormBuilder\Definition\Type\ValueTypeInterface;
use Skyline\FormBuilder\Definition\Type\ValidationRequiredValueTypeInterface;
use Skyline\FormBuilder\Definition\ValidationRequiredValueDefinitionInterface;
use Skyline\FormBuilder\Definition\ValueDefinitionInterface;
use Skyline\FormBuilder\Definition\ValuePromise;
use Skyline\FormBuilder\Representation\ControlRepresentation;
use Skyline\HTML\Form\Control\AbstractControl;
use Skyline\HTML\Form\Control\AbstractLabelControl;
use Skyline\HTML\Form\Control\Option\IntegralOptionListControl;
use Skyline\HTML\Form\Control\Option\OptionValuesInterface;
use Skyline\HTML\Form\Control\Option\PopUpControl;
use Skyline\HTML\Form\Control\Text\TextAreaControl;
use Skyline\HTML\Form\Control\Text\TextFieldControl;

class ControlRepresentationGenerator implements RepresentationGeneratorInterface
{
	protected $valueControlMap = [
		BooleanType::class => PopUpControl::class,
		DateType::class => [TextFieldControl::class, 'type' => TextFieldControl::TYPE_DATE],
		EmailTypeValueType::class => [TextFieldControl::class, 'type' => TextFieldControl::TYPE_EMAIL],
		HTMLType::class => [TextAreaControl::class, "rows" => 10],
		MonthType::class => PopUpControl::class,
		PasswordType::class => [TextFieldControl::class, 'type' => TextFieldControl::TYPE_PASSWORD],
		TextType::class => TextAreaControl::class,
		StringType::class => TextFieldControl::class,
		IntegralOptionType::class => IntegralOptionListControl::class
	];

	/**
	 * ControlRepresentationGenerator constructor.
	 * @param array $valueControlMap
	 */
	public function __construct(array $valueControlMap = [])
	{
		$this->valueControlMap = array_merge($this->valueControlMap, $valueControlMap);
	}

	/**
	 * @param string $key
	 * @param ValueTypeInterface $type
	 * @return AbstractControl
	 */
	protected function makeControl(string $key, ValueTypeInterface $type): ?AbstractControl
	{
		foreach($this->valueControlMap as $valueClass => $map) {
			if($type instanceof $valueClass) {
				if(!is_array($map)) {
					$map = [$map];
				}
				$class = array_unshift($map);
				if(class_exists($class)) {
					/** @var AbstractControl $control */
					$control = new $class($key, $key);
					foreach($map as $attr => $val)
						$control[$attr] = $val;
					return $control;
				}
				return NULL;
			}
		}
		return NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function generateRepresentation(string $key, ValueDefinitionInterface $definition, ValueTypeInterface $type, ValuePromise $defaultValue)
	{
		if($control = $this->makeControl($key, $type)) {
			if($control instanceof AbstractLabelControl && $definition instanceof DescribedValueDefinitionInterface) {
				$control->setLabel( $definition->getLabel() );
				$control->setDescription( $definition->getDescription() );

				if(method_exists($control, 'setPlaceholder'))
					$control->setPlaceholder( $definition->getPlaceholder() );
			}

			$addVals = function($v) use ($control) { $control->addValidator($v); };
			if($type instanceof ValidationRequiredValueTypeInterface) {
				if($vals = $type->getValidators())
					array_walk($vals, $addVals);
			}
			if($definition instanceof ValidationRequiredValueDefinitionInterface) {
				if($vals = $definition->getValidators())
					array_walk($vals, $addVals);
			}

			if($control instanceof OptionValuesInterface) {
				if($definition instanceof CustomListingValueDefinitionInterface) {
					if($list = $definition->getAvailableValueList()) {
						foreach(($list instanceof ListProviderInterface ? $list->yieldAvailableValues() : $list) as $key => $label)
							$control->setOption($key, $label);
					}
				}

				if(!isset($list) || !$list) {
					if($list = $type->getAvailableValueList()) {
						foreach(($list instanceof ListProviderInterface ? $list->yieldAvailableValues() : $list) as $key => $label)
							$control->setOption($key, $label);
					}
				}
			}

			return new ControlRepresentation(
				$control,
				$defaultValue
			);
		}
		return NULL;
	}
}