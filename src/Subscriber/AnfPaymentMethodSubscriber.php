<?php declare(strict_types=1);

namespace Anf\PaymentPlugin\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
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
            CheckoutConfirmPageLoadedEvent::class => 'onConfirmPageLoaded',
            AccountEditOrderPageLoadedEvent::class => 'onEditOrderPageLoaded'
        ];
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $paymentMethod = $event->getPage()->getPaymentMethods();
        foreach ($paymentMethod as $method) {
            if ($method->getName() === 'iDeal') {

                $idealIssuers = $this->paymentHandler->getIssuers();
                $event->getPage()->addArrayExtension('anf_ideal_issuers', $idealIssuers);
            }
        }
    }
    public function onEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        $paymentMethod = $event->getPage()->getPaymentMethods();
        foreach ($paymentMethod as $method) {
            if ($method->getName() === 'iDeal') {

                $idealIssuers = $this->paymentHandler->getIssuers();
                $event->getPage()->addArrayExtension('anf_ideal_issuers', $idealIssuers);
            }
        }
    }
}
