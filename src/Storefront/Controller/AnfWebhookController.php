<?php

namespace Anf\PaymentPlugin\Storefront\Controller;


use Shopware\Storefront\Controller\StorefrontController;
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
    public function handleData(): Response
    {

        return new Response('Webhook received and processed');
    }
}