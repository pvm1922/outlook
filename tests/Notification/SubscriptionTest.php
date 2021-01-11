<?php
declare(strict_types=1);

namespace Symplicity\Outlook\Tests\Notification;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symplicity\Outlook\Interfaces\Http\ConnectionInterface;
use function GuzzleHttp\Psr7\stream_for;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symplicity\Outlook\Entities\Subscription as SubscriptionEntity;
use Symplicity\Outlook\Exception\SubscribeFailedException;
use Symplicity\Outlook\Http\Connection;
use Symplicity\Outlook\Interfaces\Entity\SubscriptionResponseEntityInterface;
use Symplicity\Outlook\Notification\Subscription;
use Symplicity\Outlook\Utilities\ChangeType;

class SubscriptionTest extends TestCase
{
    private $connection;
    private $logger;

    public function setUp(): void
    {
        $this->logger = new Logger('outlook_calendar');
        $this->logger->pushHandler(new NullHandler());
        $this->connection = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([$this->logger])
            ->onlyMethods(['createClient', 'createClientWithRetryHandler'])
            ->getMock();
    }

    public function testSubscribe()
    {
        $subscriptionResponse = $this->getSubscriptionResponse();
        $uncheckedSubscriptionDate = array_merge($subscriptionResponse, ['SubscriptionExpirationDateTime' => '']);
        $wrongDateResponse = array_merge($subscriptionResponse, ['SubscriptionExpirationDateTime' => 'wrongdate']);

        $mock = new MockHandler([
            new Response(200, [], stream_for(json_encode($this->getSubscriptionResponse()))),
            new Response(200, [], stream_for(json_encode($uncheckedSubscriptionDate))),
            new Response(200, [], stream_for(json_encode($wrongDateResponse))),
            new Response(200, [], ''),
            new RequestException('Error Communicating with Server', new Request('GET', 'test'), new Response(500, ['X-Foo' => 'Bar']))
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->connection->expects($this->exactly(4))->method('createClient')->willReturn($client);
        $subscriber = new Subscription($this->logger);
        $subscriber->setConnection($this->connection);

        $subscriptionEntity = (new SubscriptionEntity())
            ->setDataType('#Microsoft.OutlookServices.DeleteNotification')
            ->setClientState('123-333')
            ->setNotificationUrl('https://test12.symplicity.com/api/v1/outlook')
            ->setResource('https://outlook.office.com/api/v2.0/me/events')
            ->setChangeType([ChangeType::deleted, ChangeType::updated, ChangeType::missed]);

        $subscriptionResponse = $subscriber->subscribe($subscriptionEntity, 'abc');
        $this->assertInstanceOf(SubscriptionResponseEntityInterface::class, $subscriptionResponse);
        $this->assertEquals('ABC==', $subscriptionResponse->id);
        $this->assertNotEmpty($subscriptionResponse->clientState);
        $this->assertInstanceOf(DateTimeImmutable::class, $subscriptionResponse->getSubscriptionExpirationDate());

        $subscriptionResponse = $subscriber->subscribe($subscriptionEntity, 'abc');
        $this->assertNull($subscriptionResponse->getSubscriptionExpirationDate());

        $subscriptionResponse = $subscriber->subscribe($subscriptionEntity, 'abc');
        $this->assertNull($subscriptionResponse->getSubscriptionExpirationDate());

        $this->expectException(SubscribeFailedException::class);
        $subscriber->subscribe($subscriptionEntity, 'abc');

        $this->expectException(RequestException::class);
        $subscriber->subscribe($subscriptionEntity, 'abc');
    }

    public function testSubscriptionError()
    {
        $subscriptionEntity = (new SubscriptionEntity())
            ->setResource('https://outlook.office.com/api/v2.0/me/events')
            ->setChangeType([ChangeType::deleted, ChangeType::updated, ChangeType::missed]);

        $this->connection->expects($this->never())->method('createClient');

        $subscriber = new Subscription($this->logger);
        $subscriber->setConnection($this->connection);

        $this->expectException(\RuntimeException::class);
        $subscriber->subscribe($subscriptionEntity, 'abc');
    }

    public function testRenewSubscription()
    {
        $mock = new MockHandler([
            new Response(200, [], stream_for(json_encode($this->getSubscriptionResponse()))),
            new Response(200, [], ''),
            new RequestException('Error Communicating with Server', new Request('GET', 'test'), new Response(500, ['X-Foo' => 'Bar']))
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->connection->expects($this->exactly(2))->method('createClient')->willReturn($client);

        $subscriber = new Subscription($this->logger);
        $subscriber->setConnection($this->connection);

        $subscriptionResponse = $subscriber->renew('ABC==', 'abc');
        $this->assertInstanceOf(SubscriptionResponseEntityInterface::class, $subscriptionResponse);
        $this->assertEquals('ABC==', $subscriptionResponse->id);
        $this->assertNotEmpty($subscriptionResponse->clientState);
        $this->assertInstanceOf(DateTimeImmutable::class, $subscriptionResponse->getSubscriptionExpirationDate());

        $this->expectException(SubscribeFailedException::class);
        $subscriber->renew('ABC==', 'abc');

        $this->expectException(RequestException::class);
        $subscriber->renew('ABC==', 'abc');
    }

    public function testDeleteSubscription()
    {
        $mock = new MockHandler([
            new Response(204, [], stream_for(json_encode($this->getSubscriptionResponse()))),
            new Response(202, [], ''),
            new Response(404, [], ''),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->connection->expects($this->exactly(3))->method('createClient')->willReturn($client);

        $subscriber = new Subscription($this->logger);
        $subscriber->setConnection($this->connection);

        $response = $subscriber->delete('ABC==', 'abc');
        $this->assertTrue($response);

        $response = $subscriber->delete('ABC==', 'abc');
        $this->assertFalse($response);

        $this->expectException(ClientException::class);
        $subscriber->delete('ABC==', 'abc');
    }

    public function testConnectionHandler()
    {
        $subscriber = new Subscription($this->logger);
        $connection = $subscriber->getConnection();
        $this->assertInstanceOf(ConnectionInterface::class, $connection);
        $this->assertInstanceOf(Connection::class, $connection);

        $subscriber->setConnection($this->connection);
        $this->assertInstanceOf(ConnectionInterface::class, $connection);
    }

    public function getSubscriptionResponse()
    {
        return [
            '@odata.context' => 'https://outlook.office.com/api/v2.0/$metadata#Me/Subscriptions/$entity',
            '@odata.type' => '#Microsoft.OutlookServices.PushSubscription',
            '@odata.id' => 'https://outlook.office.com/api/v2.0/Users(\'123-45\')/Subscriptions(\'ABC==\')',
            'Id' => 'ABC==',
            'Resource' => 'https://outlook.office.com/api/v2.0/me/events',
            'ChangeType' => 'Updated, Deleted, Missed',
            'NotificationURL' => 'https://test12.symplicity.com/api/v1/outlook',
            'SubscriptionExpirationDateTime' => '2020-09-23T13:58:53.708556Z',
            'ClientState' => '5544434-6e6f-47e1-a611-6b3299ea6a85'
        ];
    }
}
