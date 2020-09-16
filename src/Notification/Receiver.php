<?php

declare(strict_types=1);

namespace Symplicity\Outlook\Notification;

use Psr\Log\LoggerInterface;
use Symplicity\Outlook\Entities\NotificationReaderEntity;
use Symplicity\Outlook\Exception\ValidationException;
use Symplicity\Outlook\Interfaces\CalendarInterface;
use Symplicity\Outlook\Interfaces\Entity\ReaderEntityInterface;
use Symplicity\Outlook\Interfaces\Notification\ReceiverInterface;

abstract class Receiver implements ReceiverInterface
{
    protected $context;
    protected $state;

    /** @var array $entities */
    protected $entities;

    public function hydrate(array $data = []): ReceiverInterface
    {
        $this->context = $data['@odata.context'] ?? null;
        $this->state = $data['state'] ?? null;
        $this->setEntities($data['value']);
        return $this;
    }

    public function exec(CalendarInterface $calendar, LoggerInterface $logger, array $params = [])
    {
        /** @var NotificationReaderEntity $notificationEntity */
        foreach ($this->entities as $notificationEntity) {
            try {
                $this->validate($calendar, $logger, $notificationEntity);
                $this->willWrite($calendar, $logger, $notificationEntity, $params);
                $outlookEntity = $calendar->getEvent($notificationEntity->getResource(), $params);
                $this->didWrite($calendar, $logger, $outlookEntity, $notificationEntity);
            } catch (\Exception $e) {
                $eventInfo = [
                    'resource' => $this->resource,
                    'subscriptionId' => $this->subscriptionId,
                    'id' => $this->id
                ];

                $this->eventWriteFailed($calendar, $logger, $eventInfo);
                $logger->warning('Event did not process successfully', $eventInfo);
            }
        }
    }

    public function setEntities(array $entities): ReceiverInterface
    {
        foreach ($entities as $entity) {
            if ($entity instanceof NotificationReaderEntity) {
                $this->entities[] = $entity;
            } else {
                $this->entities[] = new NotificationReaderEntity($entity);
            }
        }

        return $this;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getState(): string
    {
        return $this->state;
    }

    // Mark Protected
    protected function validate(CalendarInterface $calendar, LoggerInterface $logger, NotificationReaderEntity $entity): bool
    {
        if ($entity->has('resource')
            && $entity->has('subscriptionId')
            && $entity->has('id')) {
            $this->validateSequenceNumber($calendar, $logger, $entity);
            return true;
        }

        throw new ValidationException('Missing resource/subscription-id/id');
    }

    protected function willWrite(CalendarInterface $calendar, LoggerInterface $logger, NotificationReaderEntity $notificationReaderEntity, array &$params = []): void
    {
    }

    protected function didWrite(CalendarInterface $calendar, LoggerInterface $logger, ?ReaderEntityInterface $entity, NotificationReaderEntity $notificationReaderEntity): void
    {
    }

    // Mark abstract
    abstract protected function validateSequenceNumber(CalendarInterface $calendar, LoggerInterface $logger, NotificationReaderEntity $entity): void;
    abstract protected function eventWriteFailed(CalendarInterface $calender, LoggerInterface $logger, array $info): void;
}
