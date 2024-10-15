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
			'amount' => $amount , // Le montant est déjà en centimes
			'currency' => $currency,
			'payment_method_types' => ['card'],
			'payment_method' => 'pm_card_visa',
            'confirm' => true,
		]);
	}
}
