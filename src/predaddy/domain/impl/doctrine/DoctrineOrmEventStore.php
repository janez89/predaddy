<?php
/*
 * Copyright (c) 2014 Szurovecz János
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

namespace predaddy\domain\impl\doctrine;

use ArrayIterator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Iterator;
use precore\lang\Object;
use precore\lang\ObjectClass;
use predaddy\domain\AbstractDomainEventInitializer;
use predaddy\domain\AggregateId;
use predaddy\domain\DomainEvent;
use predaddy\domain\EventSourcedAggregateRoot;
use predaddy\domain\SnapshotEventStore;
use predaddy\serializer\PHPSerializer;
use predaddy\serializer\Serializer;

class DoctrineOrmEventStore extends Object implements SnapshotEventStore
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer Used to serialize events and ARs for snapshotting
     */
    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer = null)
    {
        $this->entityManager = $entityManager;
        if ($serializer === null) {
            $serializer = new PHPSerializer();
        }
        $this->serializer = $serializer;
    }

    /**
     * @param string $aggregateRootClass
     * @param Iterator $events
     * @param $originatingVersion
     * @throws InvalidArgumentException
     * @return void
     */
    public function saveChanges($aggregateRootClass, Iterator $events, $originatingVersion)
    {
        $aggregate = null;
        /* @var $event DomainEvent */
        foreach ($events as $event) {
            $aggregateId = $event->getAggregateId();
            if ($aggregate === null) {
                $aggregate = $this->findAggregate($aggregateRootClass, $aggregateId);
                if ($aggregate === null) {
                    $aggregate = new Aggregate($aggregateId, $aggregateRootClass);
                    $this->entityManager->persist($aggregate);
                    self::getLogger()->debug(
                        "Aggregate [{}, {}] has been persisted",
                        array($aggregateRootClass, $aggregateId)
                    );
                }
                if ($originatingVersion !== null) {
                    $this->entityManager->lock($aggregate, LockMode::OPTIMISTIC, $originatingVersion);
                }
            }
            AbstractDomainEventInitializer::initVersion($event, $aggregate->getVersion());
            $aggregate->touch($event->getTimestamp());
            $metaEvent = new Event(
                $aggregateId,
                $aggregateRootClass,
                $event,
                $this->serializer->serialize($event)
            );
            $this->entityManager->persist($metaEvent);
            self::getLogger()->debug("Event [{}] has been persisted", array($metaEvent));
        }
    }

    /**
     * @param string $aggregateRootClass
     * @param AggregateId $aggregateId
     * @return ArrayIterator
     */
    public function getEventsFor($aggregateRootClass, AggregateId $aggregateId)
    {
        /* @var $snapshot Snapshot */
        $snapshot = $this->findSnapshot($aggregateRootClass, $aggregateId);
        $fromVersion = $snapshot === null ? 0 : $snapshot->getVersion();
        $events = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Event::className(), 'e')
            ->where('e.aggregateType = :type AND e.aggregateId = :aggregateId AND e.version > :version')
            ->orderBy('e.sequenceNumber')
            ->getQuery()
            ->execute(
                array(
                    'type' => $aggregateRootClass,
                    'aggregateId' => $aggregateId->getValue(),
                    'version' => $fromVersion
                )
            );
        $result = array();
        /* @var $event Event */
        foreach ($events as $event) {
            $result[] = $this->serializer->deserialize($event->getData(), ObjectClass::forName($event->getEventType()));
        }
        return new ArrayIterator($result);
    }

    /**
     * Events raised in the current transaction are not being stored in this snapshot.
     * Only the already persisted events are being utilized.
     *
     * @param string $aggregateRootClass
     * @param AggregateId $aggregateId
     */
    public function createSnapshot($aggregateRootClass, AggregateId $aggregateId)
    {
        $events = $this->getEventsFor($aggregateRootClass, $aggregateId);
        if ($events->count() == 0) {
            return;
        }

        /* @var $aggregateRoot EventSourcedAggregateRoot */
        $aggregateRoot = $this->loadSnapshot($aggregateRootClass, $aggregateId);
        if ($aggregateRoot === null) {
            $objectClass = ObjectClass::forName($aggregateRootClass);
            $aggregateRoot = $objectClass->newInstanceWithoutConstructor();
        }
        $aggregateRoot->loadFromHistory($events);

        /* @var $snapshot Snapshot */
        $snapshot = $this->findSnapshot($aggregateRootClass, $aggregateId);
        $serialized = $this->serializer->serialize($aggregateRoot);
        $events->seek($events->count() - 1);
        $version = $events->current()->getVersion();
        if ($snapshot === null) {
            $snapshot = new Snapshot(
                $aggregateId,
                $serialized,
                $aggregateRootClass,
                $version
            );
            $this->entityManager->persist($snapshot);
        } else {
            $snapshot->update($serialized, $version);
        }
        self::getLogger()->debug(
            "Snapshot has been persisted for aggregate [{}, {}], version [{}]",
            array($aggregateRootClass, $aggregateId, $version)
        );
    }

    /**
     * @param $aggregateRootClass
     * @param AggregateId $aggregateId
     * @return EventSourcedAggregateRoot|null
     */
    public function loadSnapshot($aggregateRootClass, AggregateId $aggregateId)
    {
        $aggregateRoot = null;
        /* @var $snapshot Snapshot */
        $snapshot = $this->findSnapshot($aggregateRootClass, $aggregateId);
        if ($snapshot !== null) {
            $reflectionClass = ObjectClass::forName($aggregateRootClass);
            $aggregateRoot = $this->serializer->deserialize($snapshot->getAggregateRoot(), $reflectionClass);
            self::getLogger()->debug("Snapshot loaded [{}, {}]", array($aggregateRootClass, $aggregateId));
        }
        return $aggregateRoot;
    }

    /**
     * @param string $aggregateRootClass
     * @param AggregateId $aggregateId
     * @return Aggregate|null
     */
    protected function findAggregate($aggregateRootClass, AggregateId $aggregateId)
    {
        return $this->entityManager->find(
            Aggregate::className(),
            Aggregate::createPrimaryIdArray($aggregateId, $aggregateRootClass)
        );
    }

    /**
     * @param $aggregateRootClass
     * @param AggregateId $aggregateId
     * @return Snapshot|null
     */
    protected function findSnapshot($aggregateRootClass, AggregateId $aggregateId)
    {
        return $this->entityManager->find(
            Snapshot::className(),
            Snapshot::createPrimaryIdArray($aggregateId, $aggregateRootClass)
        );
    }
}
