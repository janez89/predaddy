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
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO message SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace predaddy\messagehandling;

use DateTime;
use precore\lang\Object;
use precore\lang\ObjectInterface;
use precore\util\UUID;
use predaddy\serializer\ReflectionSerializer;
use predaddy\serializer\Serializer;

/**
 * Base class for all types of messages. Contains the message identifier and timestamp.
 *
 * @author Szurovecz János <szjani@szjani.hu>
 */
abstract class AbstractMessage extends Object implements Message
{
    protected $id;
    protected $timestamp;

    public function __construct()
    {
        $this->timestamp = new DateTime();
        $this->id = UUID::randomUUID()->toString();
    }

    /**
     * @return string
     */
    public function getMessageIdentifier()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getTimestamp()
    {
        return clone $this->timestamp;
    }

    public function equals(ObjectInterface $object = null)
    {
        return $object instanceof self && $this->id === $object->id;
    }

    public function toString()
    {
        return parent::toString()
            . sprintf(
                '[id=%s, timestamp=%s]',
                $this->getMessageIdentifier(),
                $this->getTimestamp()->format(DateTime::ISO8601)
            );
    }
}