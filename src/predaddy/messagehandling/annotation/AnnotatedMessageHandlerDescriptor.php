<?php
/*
 * Copyright (c) 2013 Szurovecz János
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace predaddy\messagehandling\annotation;

use Doctrine\Common\Annotations\Reader;
use precore\lang\ObjectClass;
use predaddy\messagehandling\FunctionDescriptor;
use predaddy\messagehandling\FunctionDescriptorFactory;
use predaddy\messagehandling\MessageHandlerDescriptor;
use ReflectionClass;
use ReflectionMethod;

/**
 * Finds handler methods which are annotated with Subscribe.
 *
 * @author Szurovecz János <szjani@szjani.hu>
 */
class AnnotatedMessageHandlerDescriptor implements MessageHandlerDescriptor
{
    private $handlerClass;
    private $reader;
    private $directHandlerMethodDescriptors = array();
    private $compatibleHandlerMethodsCache = array();

    /**
     * @var FunctionDescriptorFactory
     */
    private $functionDescriptorFactory;

    /**
     * @param ReflectionClass $handlerClass
     * @param Reader $reader
     * @param FunctionDescriptorFactory $functionDescFactory
     */
    public function __construct(
        ReflectionClass $handlerClass,
        Reader $reader,
        FunctionDescriptorFactory $functionDescFactory
    ) {
        $this->handlerClass = $handlerClass;
        $this->reader = $reader;
        $this->functionDescriptorFactory = $functionDescFactory;
        $this->findHandlerMethods();
    }

    /**
     * @param ObjectClass $messageClass
     * @return array of FunctionDescriptor
     */
    public function getFunctionDescriptorsFor(ObjectClass $messageClass)
    {
        $messageClassName = $messageClass->getName();
        if (!array_key_exists($messageClassName, $this->compatibleHandlerMethodsCache)) {
            $this->compatibleHandlerMethodsCache[$messageClassName] = $this->findCompatibleMethodsFor($messageClass);
        }
        return $this->compatibleHandlerMethodsCache[$messageClassName];
    }

    /**
     * Find all handler methods for a specific type of Message
     *
     * @param ObjectClass $messageClass
     * @return array of FunctionDescriptor
     */
    protected function findCompatibleMethodsFor(ObjectClass $messageClass)
    {
        $result = array();
        foreach ($this->directHandlerMethodDescriptors as $handlerMessageClass => $funcDescriptors) {
            $firstDesc = $funcDescriptors[0];
            /* @var $firstDesc FunctionDescriptor */
            if ($firstDesc->isHandlerFor($messageClass)) {
                foreach ($funcDescriptors as $fDesc) {
                    $result[] = $fDesc;
                }
            }
        }
        return $result;
    }

    protected function findHandlerMethods()
    {
        /* @var $reflMethod ReflectionMethod */
        foreach ($this->handlerClass->getMethods($this->methodVisibility()) as $reflMethod) {
            $methodAnnotation = $this->reader->getMethodAnnotation($reflMethod, __NAMESPACE__ . '\Subscribe');
            if ($methodAnnotation === null) {
                continue;
            }
            $funcDescriptor = $this->functionDescriptorFactory->create($reflMethod, $methodAnnotation->priority);
            if (!$funcDescriptor->isValid()) {
                continue;
            }
            $reflMethod->setAccessible(true);
            $this->directHandlerMethodDescriptors[$funcDescriptor->getHandledMessageClassName()][] = $funcDescriptor;
        }
    }

    protected function methodVisibility()
    {
        return ReflectionMethod::IS_PUBLIC;
    }
}
