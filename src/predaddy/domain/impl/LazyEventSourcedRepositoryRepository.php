<?php
/*
 * Copyright (c) 2012-2014 Szurovecz János
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

namespace predaddy\domain\impl;

use LazyMap\CallbackLazyMap;
use precore\lang\ObjectClass;
use predaddy\domain\EventSourcingRepository;
use predaddy\domain\EventStore;
use predaddy\domain\RepositoryRepository;
use predaddy\domain\SnapshotStrategy;
use predaddy\eventhandling\EventBus;

class LazyEventSourcedRepositoryRepository implements RepositoryRepository
{
    /**
     * @var \LazyMap\CallbackLazyMap
     */
    private $map;

    /**
     * @param EventBus $eventBus
     * @param EventStore $eventStore
     * @param SnapshotStrategy $snapshotStrategy
     */
    public function __construct(
        EventBus $eventBus,
        EventStore $eventStore,
        SnapshotStrategy $snapshotStrategy = null
    ) {
        $this->map = new CallbackLazyMap(
            function ($aggregateClass) use ($eventBus, $eventStore, $snapshotStrategy) {
                return new EventSourcingRepository(
                    ObjectClass::forName($aggregateClass),
                    $eventBus,
                    $eventStore,
                    $snapshotStrategy
                );
            }
        );
    }

    /**
     * @param ObjectClass $aggregateClass
     * @return \predaddy\domain\Repository
     */
    public function getRepository(ObjectClass $aggregateClass)
    {
        $className = $aggregateClass->getName();
        return $this->map->$className;
    }
}
