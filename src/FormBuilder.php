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

namespace Skyline\FormBuilder;


use LogicException;
use Skyline\FormBuilder\Definition\Type\ValueTypeFactoryInterface;
use Skyline\FormBuilder\Definition\Type\ValueTypeInterface;
use Skyline\FormBuilder\Definition\ValueDefinitionInterface;
use Skyline\FormBuilder\Definition\ValueDefinitionProviderInterface;
use Skyline\FormBuilder\Definition\ValuePromise;
use Skyline\FormBuilder\Provider\ValueProviderInterface;
use Skyline\FormBuilder\Provider\ValueStorageInterface;
use Skyline\FormBuilder\Representation\Generator\ControlRepresentationGenerator;
use Skyline\FormBuilder\Representation\RepresentationFinalizerInterface;
use Skyline\FormBuilder\Representation\Generator\RepresentationGeneratorInterface;
use Skyline\FormBuilder\Representation\RepresentationInterface;
use Skyline\HTML\Form\Control\Button\ButtonControl;
use Skyline\HTML\Form\FormElement;
use Symfony\Component\HttpFoundation\Request;
use TASoft\Util\ValueInjector;

class FormBuilder
{
	/** @var ValueProviderInterface */
	private $valueProvider;
	/** @var ValueStorageInterface|null */
	private $valueStorage;

	/** @var ValueDefinitionProviderInterface|null */
	private $definitionProvider;
	/** @var RepresentationGeneratorInterface */
	private $representationGenerator;

	/** @var RepresentationFinalizerInterface|null */
	private $representationFinalizer;

	private $defCache = [];
	private $valueTypeCache = [];

	/**
	 * FormBuilder constructor.
	 * @param ValueProviderInterface $provider
	 * @param RepresentationGeneratorInterface|null $representationGenerator
	 * @param ValueDefinitionProviderInterface|null $definitionProvider
	 */
	public function __construct(ValueProviderInterface $provider, RepresentationGeneratorInterface $representationGenerator = NULL, ValueDefinitionProviderInterface $definitionProvider = NULL)
	{
		$this->valueProvider = $provider;
		$this->definitionProvider = $definitionProvider;
		$this->representationGenerator = $representationGenerator;
	}

	/**
	 * @return ValueProviderInterface
	 */
	public function getValueProvider(): ValueProviderInterface
	{
		return $this->valueProvider;
	}

	/**
	 * @return ValueDefinitionProviderInterface|null
	 */
	public function getDefinitionProvider(): ?ValueDefinitionProviderInterface
	{
		return $this->definitionProvider;
	}

	/**
	 * @param ValueDefinitionProviderInterface|null $definitionProvider
	 * @return static
	 */
	public function setDefinitionProvider(?ValueDefinitionProviderInterface $definitionProvider)
	{
		$this->definitionProvider = $definitionProvider;
		$this->defCache = [];
		return $this;
	}

	/**
	 * @return RepresentationGeneratorInterface
	 */
	public function getRepresentationGenerator()
	{
		if(!$this->representationGenerator)
			$this->representationGenerator = new ControlRepresentationGenerator();
		return $this->representationGenerator;
	}

	/**
	 * @param RepresentationGeneratorInterface $representationGenerator
	 * @return static
	 */
	public function setRepresentationGenerator($representationGenerator)
	{
		$this->representationGenerator = $representationGenerator;
		return $this;
	}

	/**
	 * @return RepresentationFinalizerInterface|null
	 */
	public function getRepresentationFinalizer(): ?RepresentationFinalizerInterface
	{
		return $this->representationFinalizer;
	}

	/**
	 * @param RepresentationFinalizerInterface|null $representationFinalizer
	 * @return FormBuilder
	 */
	public function setRepresentationFinalizer(?RepresentationFinalizerInterface $representationFinalizer): FormBuilder
	{
		$this->representationFinalizer = $representationFinalizer;
		return $this;
	}

	/**
	 * @return ValueStorageInterface|null
	 */
	public function getValueStorage(): ?ValueStorageInterface
	{
		return $this->valueStorage;
	}

	/**
	 * @param ValueStorageInterface|null $valueStorage
	 * @return FormBuilder
	 */
	public function setValueStorage(?ValueStorageInterface $valueStorage): FormBuilder
	{
		$this->valueStorage = $valueStorage;
		return $this;
	}

	/**
	 * @param $type
	 * @param null $name
	 * @return static
	 */
	public function addValueType($type, $name = NULL) {
		if(is_callable($type) && $name) {
			if($name)
				$this->valueTypeCache[$name] = $type;
			else
				throw new LogicException("Callback requires a value type");
		} elseif ($type instanceof ValueTypeInterface) {
			$this->valueTypeCache[ $name ? $name : $type->getName() ] = $type;
		}
		return $this;
	}

	/**
	 * @param array|ValueTypeFactoryInterface $values
	 * @return static
	 */
	public function addValueTypes($values) {
		if($values instanceof ValueTypeFactoryInterface)
			$values = $values->getValueTypes();
		if(is_iterable($values)) {
			foreach($values as $name => $value) {
				$this->addValueType($value, $name);
			}
		}
		return $this;
	}

	/**
	 * @param $name
	 * @return static
	 */
	public function removeValueType($name) {
		if(isset($this->valueTypeCache[$name]))
			unset($this->valueTypeCache[$name]);
		return $this;
	}

	/**
	 * @param $name
	 * @return ValueTypeInterface|null
	 */
	public function getValueType($name): ?ValueTypeInterface {
		if($v = $this->valueTypeCache[$name] ?? NULL) {
			if(is_callable($v))
				$v = $v($name);
		}
		return $v;
	}

	/**
	 * @param $key
	 * @return ValueDefinitionInterface|null
	 */
	public function getDefinition($key): ?ValueDefinitionInterface {
		if(!isset($this->defCache[$key])) {
			$def = NULL;
			if($this->getValueProvider() instanceof ValueDefinitionProviderInterface) {
				$def = $this->getValueProvider()->getValueDefinition($key);
			}
			if(!$def && $this->getDefinitionProvider()) {
				$def = $this->getDefinitionProvider()->getValueDefinition($key);
			}
			$this->defCache[$key] = $def ?: false;
		}
		return $this->defCache[$key] ?: NULL;
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getValue($key) {
		if($v = $this->getValueProvider()->getProvidedValue($key)) {
			if(is_callable($v))
				$v = $v($key);
		}
		return $v;
	}

	/**
	 * Call this method to get all reauired stuff to build the form for a specific value.
	 *
	 * @param $key
	 * @param ValueDefinitionInterface|null $definition
	 * @param ValueTypeInterface|null $valueType
	 * @param callable|null $defaultValue
	 * @return bool
	 */
	public function canHandleValue($key, ValueDefinitionInterface &$definition = NULL, ValueTypeInterface &$valueType = NULL, callable &$defaultValue = NULL): bool {
		if($definition = $this->getDefinition($key)) {
			if($valueType = $this->getValueType( $definition->getValueType() )) {
				$defaultValue = new ValuePromise(function($raw = false) use ($key, $valueType, $definition) {
					$v = $this->getValue($key);
					return $raw ? $v : $valueType->toValue($v, $definition->getOptions());
				});
				return true;
			} else
				trigger_error(sprintf("No value type %s defined", $definition->getValueType()), E_USER_WARNING);
		}
		return false;
	}

	/**
	 * Builds the form to edit the provided values.
	 *
	 * @param FormElement $element
	 * @param null|string|array|callable $keyFilter
	 * @return BuildResult
	 */
	public function build(FormElement $element, $keyFilter = NULL): BuildResult {
		$accepts = function($key) use ($keyFilter) {
			if(is_string($keyFilter))
				return preg_match($keyFilter, $key) ? true : false;
			if(is_array($keyFilter))
				return in_array($key, $keyFilter);
			if(is_callable($keyFilter))
				return $keyFilter($key);
			return true;
		};

		$representations = [];
		foreach($this->getValueProvider()->getProvidedValueKeys() as $key) {
			if($accepts($key)) {
				if($this->canHandleValue($key, $def, $type, $default)) {
					if($r = $this->getRepresentationGenerator()->generateRepresentation($key, $def, $type, $default)) {
						if(is_array($r))
							$representations = array_merge($representations, $r);
						else
							$representations[] = $r;
					}
				}
			}
		}

		if($representations) {
			$f = $this->getRepresentationFinalizer() ?: $this->getRepresentationGenerator();
			if($f instanceof RepresentationFinalizerInterface)
				$representations = $f->finalizeRepresentations($representations);

			array_walk($representations, function(RepresentationInterface $R) use ($element) {
				$R->prepare($element);
			});
		}
		$vi = new ValueInjector($br = new BuildResult());
		$vi->representations = $representations;
		return $br;
	}

	/**
	 * @param FormElement $element
	 * @param Request $request
	 * @param string $actionName
	 * @param null $keyFilter
	 * @return BuildResult
	 */
	public function buildAndRun(FormElement $element, Request $request, string $actionName = 'apply', $keyFilter = NULL): BuildResult {
		$vi = new ValueInjector($br = $this->build($element, $keyFilter));
		$element->setActionControl(new ButtonControl($actionName));

		$vi->state = $state = $element->prepareWithRequest($request);
		if($state == $element::FORM_STATE_VALID) {
			$storage = $this->getValueStorage() ?: $this->getValueProvider();
			if($storage instanceof ValueStorageInterface) {
				$keyNames = $storage->getProvidedValueKeys();

				if($storage instanceof ValueProviderInterface) {
					$filter = function($v, $k) use ($keyNames, $storage) {
						return in_array($k, $keyNames) && $v !== $storage->getProvidedValue($k);
					};
				} else {
					$filter = function($v, $k) use ($keyNames) {
						return in_array($k, $keyNames);
					};
				}

				$data = array_filter($element->getData(), $filter, ARRAY_FILTER_USE_BOTH);

				$values = [];
				foreach($data as $key => $value) {
					$def = $this->getDefinition($key);
					$values[$key] = $this->getValueType($def->getValueType())->toScalar($value, $def->getOptions());
				}
				$storage->saveValues( $values );
			}
		} elseif($state == $element::FORM_STATE_NONE) {
			$contents = [];
			foreach($br->getRepresentations() as $representation) {
				if($name = $representation->getName()) {
					$value = $representation->getInitialValue();
					if(is_array($value))
						array_walk($value, function($v, $k) use (&$contents) { $contents[$k] = $v; });
					else
						$contents[$name] = $value;
				}
			}
			$element->setData($contents);
		}

		return $br;
	}
}