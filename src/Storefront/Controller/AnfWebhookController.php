<?php

namespace Anf\PaymentPlugin\Storefront\Controller;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class AnfWebhookController extends StorefrontController
{
    private OrderTransactionStateHandler $transactionStateHandler;

    public function __construct(OrderTransactionStateHandler $transactionStateHandler)
    {
        $this->transactionStateHandler = $transactionStateHandler;
    }

    /**
     * @Route("/webhook/data/{transaction_id}", name="webhook.data", methods={"POST"})
     */
    public function handleData(Request $request, SalesChannelContext $salesChannelContext, string $transaction_id): Response
    {
        $requestData = json_decode($request->getContent(), true);

        if (isset($requestData['transaction_status'])) {
            $transactionStatus = $requestData['transaction_status'];
            $context = $salesChannelContext->getContext();

            if ($transactionStatus === 'cancelled') {
                throw PaymentException::CustomerCanceled($transaction_id, 'Customer canceled the payment on the PayPal page');
            }

            if ($transactionStatus === 'completed') {
                $this->transactionStateHandler->paid($transaction_id, $context);
            } else {
                $this->transactionStateHandler->reopen($transaction_id, $context);
            }

            return new Response();
        }

        return new Response();
    }
}
