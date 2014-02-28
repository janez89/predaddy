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

namespace predaddy\domain;

use DateTime;
use predaddy\eventhandling\EventBase;

/**
 * Base class for all Domain Events.
 * This class contains the basic behavior expected from any event
 * to be processed by event sourcing engines and aggregates.
 *
 * @author Szurovecz János <szjani@szjani.hu>
 */
abstract class DomainEventBase extends EventBase implements DomainEvent
{
    protected $aggregateId;
    protected $version;

    public function __construct(AggregateId $aggregateId, $originatedVersion)
    {
        parent::__construct();
        $this->aggregateId = $aggregateId;
        $this->version = $originatedVersion + 1;
    }

    /**
     * @return AggregateId
     */
    public function getAggregateId()
    {
        return $this->aggregateId;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function toString()
    {
        return $this->getClassName() . '@' . $this->hashCode()
            . sprintf(
                '[id=%s, timestamp=%s, aggregateId=%s, version=%s]',
                $this->getEventIdentifier(),
                $this->getTimestamp()->format(DateTime::ISO8601),
                $this->getAggregateId(),
                $this->getVersion()
            );
    }
}