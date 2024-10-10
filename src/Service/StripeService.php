<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeService
{
	public function __construct(string $stripeSecretKey)
	{
		Stripe::setApiKey($stripeSecretKey);
	}

	public function createPaymentIntent(float $amount, string $currency = 'eur'): PaymentIntent
	{
		return PaymentIntent::create([
			'amount' => $amount * 100, // Stripe utilise des centimes pour les montants
			'currency' => $currency,
			'payment_method_types' => ['card'],
		]);
	}
}
