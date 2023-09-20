<?php declare(strict_types=1);

namespace Anf\PaymentPlugin\Service;

use GingerPluginSdk\Client;
use GingerPluginSdk\Properties\ClientOptions;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class AnfPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    private OrderTransactionStateHandler $transactionStateHandler;
    private SystemConfigService $systemConfigService;

    public function __construct(OrderTransactionStateHandler $transactionStateHandler, SystemConfigService $systemConfigService) {
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

    private function getApiKey(): string
    {
        return $this->systemConfigService->get('AnfPaymentPlugin.config.clientApiKey');
    }


    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        $newClient = $this->createGingerClient();
        dd($newClient);
        try {
            $redirectUrl = $this->sendReturnUrlToExternalGateway($transaction->getReturnUrl());
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        // Redirect to external gateway
        return new RedirectResponse($redirectUrl);
    }

    /**
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();

        // Example check if the user cancelled. Might differ for each payment provider
        if ($request->query->getBoolean('cancel')) {
            throw PaymentException::asyncCustomerCanceled(
                $transactionId,
                'Customer canceled the payment on the PayPal page'
            );
        }

        // Example check for the actual status of the payment. Might differ for each payment provider
        $paymentState = $request->query->getAlpha('status');

        $context = $salesChannelContext->getContext();
        if ($paymentState === 'completed') {
            // Payment completed, set transaction status to "paid"
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
        } else {
            // Payment not completed, set transaction status to "open"
            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
        }
    }


    private function sendReturnUrlToExternalGateway(string $getReturnUrl): string
    {
        $paymentProviderUrl = 'https://api.dev.gingerpayments.com/';

        // Do some API Call to your payment provider

        return $paymentProviderUrl;
    }
}
