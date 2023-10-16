<?php

declare(strict_types=1);

namespace Anf\PaymentPlugin\Service;

use Ginger\ApiClient;
use GingerPluginSdk\Client;
use GingerPluginSdk\Collections\AdditionalAddresses;
use GingerPluginSdk\Collections\PhoneNumbers;
use GingerPluginSdk\Collections\Transactions;
use GingerPluginSdk\Entities\Address;
use GingerPluginSdk\Entities\Customer;
use GingerPluginSdk\Entities\PaymentMethodDetails;
use GingerPluginSdk\Entities\Transaction;
use GingerPluginSdk\Properties\ClientOptions;
use GingerPluginSdk\Properties\Country;
use GingerPluginSdk\Properties\Currency;
use GingerPluginSdk\Properties\Email;
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
use GingerPluginSdk\Entities\Order;

class AnfPaymentHandler implements AsynchronousPaymentHandlerInterface
{

    private OrderTransactionStateHandler $transactionStateHandler;

    private SystemConfigService $systemConfigService;

    public function __construct(
      OrderTransactionStateHandler $transactionStateHandler,
      SystemConfigService $systemConfigService
    ) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->systemConfigService = $systemConfigService;
    }

    private function createGingerClient(): Client
    {
        $apiKey = $this->getApiKey();
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
        return $this->systemConfigService->get(
          'AnfPaymentPlugin.config.clientApiKey'
        );
    }

    public function getIssuers(): array
    {
        $apiClient = $this->createApiClient();

        return $apiClient->getIdealIssuers();
    }

    /**
     * @throws AsyncPaymentProcessException
     * @throws \Exception
     */
    public function pay(
      AsyncPaymentTransactionStruct $transaction,
      RequestDataBag $dataBag,
      SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        try {
            // Get necessary data for payment
            $issuerId = $dataBag->get('selectedIssuerId');
            $transactionId = $transaction->getOrderTransaction()->getId();
            $description = $transaction->getOrder()
              ->getLineItems()
              ->first()
              ->getLabel();
            $webhook = 'https://fbf6-193-109-145-96.ngrok-free.app';
            $webhookUrl = $this->createWebhookUrl($webhook, $transactionId);
            $returnUrl = $transaction->getReturnUrl();

            // Get customer data
            $customerData = $salesChannelContext->getCustomer();
            $customerFirstName = $customerData->getFirstName();
            $customerLastName = $customerData->getLastName();
            $customerEmail = $customerData->getEmail();
            $customerBillingAddress = $customerData->getActiveBillingAddress();
            $customerPostalCode = $customerBillingAddress->getZipcode();
            $customerStreet = $customerBillingAddress->getStreet();
            $customerCity = $customerBillingAddress->getCity();
            $customerCountry = $customerBillingAddress->getCountry()->getIso();
            $customerPhoneNumber = $customerData->getCustomerNumber();
            $customerCurrency = $transaction->getOrder()
              ->getCurrency()
              ->getIsoCode();
            $customerAmountTotal = $transaction->getOrder()->getAmountTotal();

            // Construct email, country, address, and phone number objects
            $email = new Email($customerEmail);
            $country = new Country($customerCountry);
            $address = new Address(
              'billing',
              $customerPostalCode,
              $customerStreet,
              $customerCity,
              $country
            );
            $additionalAddress = new AdditionalAddresses($address);
            $phoneNumber = new PhoneNumbers($customerPhoneNumber);

            // Create customer and currency objects
            $customer = new Customer(
              $additionalAddress,
              $customerFirstName,
              $customerLastName,
              $email,
              'male',
              $phoneNumber
            );
            $currency = new Currency($customerCurrency);

            // Create payment method details and transaction
            $methodDetails = new PaymentMethodDetails();
            $methodDetails->setPaymentMethodDetailsIdeal($issuerId);

            $sdkTransact = new Transaction('ideal', $methodDetails);
            $sdkTransaction = new Transactions($sdkTransact);

            // Create order object
            $order = new Order(
              $currency,
              $customerAmountTotal,
              $sdkTransaction,
              $customer,
              null,
              $returnUrl,
              $webhookUrl,
              null,
              $description,
              null
            );

            // Send order to Ginger
            $clientOrder = $this->createGingerClient()->sendOrder($order);
            $redirectUrl = $clientOrder['transactions'][0]['payment_url'];
        } catch (\Exception $e) {
            // Handle exceptions
            throw PaymentException::asyncProcessInterrupted(
              $transaction->getOrderTransaction()->getId(),
              'An error occurred during the communication with external payment gateway.'.PHP_EOL.$e->getMessage(
              )
            );
        }

        return new RedirectResponse($redirectUrl);
    }

    /**
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalize(
      AsyncPaymentTransactionStruct $transaction,
      Request $request,
      SalesChannelContext $salesChannelContext
    ): void {
        // Implementation for finalizing the payment
    }

    private function createWebhookUrl($domainUrl, $transactionId): string
    {
        return "$domainUrl/webhook/data/$transactionId";
    }

}

