<?php
	namespace App\Libs;

	class Config {
		
		const SERVER_DOMAIN = 'http://localhost:1001';
		const LEDGER_DOMAIN = 'http://localhost:7001';
		const SETTING_DOMAIN = 'http://localhost:7002';
		const CLIENT_DOMAIN = 'http://localhost:1002';

		const VNP_HASH_SECRET = 'KOFZGTAJINHMPKBVEBHETDUAKWNAXNJM';
		const VNP_TMN_CODE = 'PYBLWAIN';

		const VNPAY_TYPE = 1;
		const MOMO_TYPE = 2;

		const VNPAY_CHECKOUT_URL = 'http://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

		const RECHARGE_TYPE = 2;
		const TRANSFER_TYPE = 1;

		const PARTNER_CODE_MOMO = 'MOMO0HGO20180417';
		const ACCESS_KEY_MOMO = 'E8HZuQRy2RsjVtZp';
		const SERECTKEY_MOMO = 'fj00YKnJhmYqahaFWUgkg75saNTzMrbO';
		const ENDPOINT_PAYMENT_MOMO = 'https://test-payment.momo.vn/gw_payment/transactionProcessor';
	}