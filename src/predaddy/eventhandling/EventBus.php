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

namespace predaddy\eventhandling;

use ArrayIterator;
use predaddy\messagehandling\interceptors\TransactionSynchronizedBuffererInterceptor;
use predaddy\messagehandling\MessageHandlerDescriptorFactory;
use predaddy\messagehandling\SimpleMessageBus;
use trf4php\ObservableTransactionManager;

/**
 * This kind of MessageBus synchronizes message dispatching with the given ObservableTransactionManager.
 * All posted messages will be buffered until the transaction is committed.
 *
 * It's highly recommended to use an EventFunctionDescriptorFactory instance.
 * In that case Messages must implement the Event interface.
 *
 * @package predaddy\messagehandling\event
 *
 * @author Szurovecz János <szjani@szjani.hu>
 */
class EventBus extends SimpleMessageBus
{
    const DEFAULT_NAME = 'event-bus';

    /**
     * @param MessageHandlerDescriptorFactory $handlerDescFactory
     * @param ObservableTransactionManager $transactionManager
     * @param $identifier
     */
    public function __construct(
        MessageHandlerDescriptorFactory $handlerDescFactory,
        ObservableTransactionManager $transactionManager,
        $identifier = self::DEFAULT_NAME
    ) {
        parent::__construct($handlerDescFactory, $identifier);
        $this->setInterceptors(
            new ArrayIterator(
                array(
                    new TransactionSynchronizedBuffererInterceptor($transactionManager)
                )
            )
        );
    }
}
