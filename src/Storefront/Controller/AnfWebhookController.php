<?php

namespace Anf\PaymentPlugin\Storefront\Controller;


use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class AnfWebhookController extends StorefrontController
{

    /**
     * @Route("/webhook/data", name="webhook.data", methods={"POST"})
     *
     */
    public function handleData(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $a = $request->getContent();
        $data = json_decode($a, true);

        if (isset($data['transaction_status'])) {
            $transaction_status = $data['transaction_status'];
            $context = $salesChannelContext->getContext();
            if ($transaction_status == 'completed') {
//                $this->transactionStateHandler->paid($data['transaction_id'], $context);
            }
            return new Response("$transaction_status");
        }

        return new Response();
    }
}