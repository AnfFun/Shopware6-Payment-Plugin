<?php declare(strict_types=1);

namespace Anf\PaymentPlugin\Service;

use Ginger\ApiClient;
use GingerPluginSdk\Client;
use GingerPluginSdk\Properties\ClientOptions;
use http\Env\Response;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\Cart;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class AnfPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    private OrderTransactionStateHandler $transactionStateHandler;
    private SystemConfigService $systemConfigService;

    public function __construct(OrderTransactionStateHandler $transactionStateHandler, SystemConfigService $systemConfigService)
    {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->systemConfigService = $systemConfigService;
    }

    private function createGingerClient(): Client
    {
        $apiKey = $this->getApiKey();;
        $gingerEndpoint = 'https://api.dev.gingerpayments.com';

        $clientOptions = new ClientOptions($gingerEndpoint, true, $apiKey);
        return new Client($clientOptions);
    }

    private function createApiClient(): ApiClient
    {
        return $this->createGingerClient()->getApiClient();
    }

    private function getApiKey(): string
    {
        return $this->systemConfigService->get('AnfPaymentPlugin.config.clientApiKey');
    }

    public function getIssuers(): array
    {
        $apiClient = $this->createApiClient();
        return $apiClient->getIdealIssuers();
    }


    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {

        try {
            // Method that sends the return URL to the external gateway and gets a redirect URL back

            $currency = $transaction->getOrder()->getCurrency()->getIsoCode();
            $returnUrl = $transaction->getReturnUrl();
            $amountTotal = round($transaction->getOrder()->getAmountTotal() * 100);
            $issuerId = $dataBag->get('selectedIssuerId');
            $description = $transaction->getOrder()->getLineItems()->first()->getLabel();

            $orderDetails = [
                'amount' => $amountTotal,
                'description' => $description,
                'currency' => $currency,
                'return_url' => $returnUrl,
                'transactions' => [
                    [
                        'payment_method' => 'ideal',
                        'payment_method_details' => [
                            'issuer_id' => $issuerId
                        ]
                    ]
                ]
            ];

            $order = $this->createApiClient()->createOrder($orderDetails);

            $redirectUrl = $order['transactions'][0]['payment_url'];

        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
        return new RedirectResponse($redirectUrl);
    }

    /**
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $orderId = $request->query->get('order_id');
        $order = $this->createApiClient()->getOrder($orderId);
        $paymentState = $order['status'];

        // Example check if the user cancelled. Might differ for each payment provider
        if ($paymentState == 'cancelled') {
            throw PaymentException::CustomerCanceled(
                $transactionId,
                'Customer canceled the payment on the PayPal page'
            );
        }

        // Example check for the actual status of the payment. Might differ for each payment provider


        $context = $salesChannelContext->getContext();
        if ($paymentState == 'completed') {
            // Payment completed, set transaction status to "paid"
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
        } else {
            // Payment not completed, set transaction status to "open"
            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
        }
    }


}

