<?php
namespace sample;

/**
 * Although this example works, you should use a real ObservableTransactionManager implementation
 * instead of NOPTransactionManager.
 *
 * @see https://github.com/szjani/trf4php-doctrine
 * @see https://github.com/doctrine/doctrine2
 */

require_once __DIR__ . '/../../bootstrap.php';

use Exception;
use predaddy\eventhandling\AbstractEvent;
use predaddy\eventhandling\EventBus;
use predaddy\eventhandling\EventFunctionDescriptorFactory;
use predaddy\messagehandling\annotation\AnnotatedMessageHandlerDescriptorFactory;
use trf4php\NOPTransactionManager;
use trf4php\TransactionManager;
use predaddy\messagehandling\annotation\Subscribe;

class UserRegistered extends AbstractEvent
{
    protected $email;

    public function __construct($email)
    {
        parent::__construct();
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }
}

class EmailSender
{
    /**
     * @Subscribe
     */
    public function sendMail(UserRegistered $message)
    {
        printf("Sending email to %s...\n", $message->getEmail());
    }
}

/**
 * Initialization
 */

// can be used any ObservableTransactionManager, for example DoctrineTransactionManager
$transactionManager = new NOPTransactionManager();

// event bus initialization
$messageHandlerDescFactory = new AnnotatedMessageHandlerDescriptorFactory(new EventFunctionDescriptorFactory());
$eventBus = new EventBus($messageHandlerDescFactory, $transactionManager);
$eventBus->register(new EmailSender());

/**
 * Code from a service class.
 */

// use transaction
/* @var $transactionManager TransactionManager */
$transactionManager->beginTransaction();
try {
    // database modifications ...
    $eventBus->post(new UserRegistered('example1@example.com'));
    $transactionManager->commit();
    // EmailSender::sendMail will be called at this point
} catch (Exception $e) {
    $transactionManager->rollback();
    // EmailSender::sendMail won't be called
}
