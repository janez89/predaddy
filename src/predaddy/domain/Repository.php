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

namespace predaddy\domain;

/**
 * Repository interface for loading and persisting aggregates.
 *
 * @package predaddy\domain
 */
interface Repository
{
    /**
     * Load the aggregate identified by $aggregateId from the persistent storage.
     *
     * @param AggregateId $aggregateId
     * @return AggregateRoot
     * @throws \InvalidArgumentException If the $aggregateId is invalid
     */
    public function load(AggregateId $aggregateId);

    /**
     * Persisting the given $aggregateRoot, $version can be used for locking.
     * Events raised in $aggregateRoot should be posted to the domain event bus.
     *
     * @param AggregateRoot $aggregateRoot
     * @param int|null null means explicit version check is not necessary
     */
    public function save(AggregateRoot $aggregateRoot, $version = null);
}
