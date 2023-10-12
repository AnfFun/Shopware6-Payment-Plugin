<?php
declare(strict_types=1);

namespace Anf\PaymentPlugin\Subscriber;

use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Anf\PaymentPlugin\Service\AnfPaymentHandler;

class AnfPaymentMethodSubscriber implements EventSubscriberInterface
{
    private AnfPaymentHandler $paymentHandler;

    public function __construct(AnfPaymentHandler $paymentHandler)
    {
        $this->paymentHandler = $paymentHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
          CheckoutConfirmPageLoadedEvent::class => 'onPageLoaded',
          AccountEditOrderPageLoadedEvent::class => 'onPageLoaded',
        ];
    }

    public function onPageLoaded($event): void
    {
        $paymentMethod = $event->getPage()->getPaymentMethods();
        foreach ($paymentMethod as $method) {
            if ($method->getName() === 'iDeal') {
                $idealIssuers = $this->paymentHandler->getIssuers();
                $event->getPage()->addArrayExtension(
                  'anf_ideal_issuers',
                  $idealIssuers
                );
            }
        }
    }
}
