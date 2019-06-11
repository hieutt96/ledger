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
		const ONEPAY_TYPE = 3;


		const VNPAY_CHECKOUT_URL = 'http://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
		// const ONEPAY_CHECKOUT_URL = 'https://mtf.onepay.vn/vpcpay/Vpcdps.op';
		const ONEPAY_CHECKOUT_URL_OUT =	'https://mtf.onepay.vn/vpcpay/vpcpay.op';
		const ONEPAY_ACCESS_CODE = '6BEB2546';
		const ONEPAY_MERCHANT_ID = 'TESTONEPAY';
		const ONEPAY_HASHCODE = '6D0870CDE5F24F34F3915FB0045120DB';

		const RECHARGE_TYPE = 2;
		const TRANSFER_TYPE = 1;
		const WITHDRWAL_TYPE =3;
		const STORE_MINUS = 4;
		const ORDER_TYPE = 5;

		const PARTNER_CODE_MOMO = 'MOMO0HGO20180417';
		const ACCESS_KEY_MOMO = 'E8HZuQRy2RsjVtZp';
		const SERECTKEY_MOMO = 'fj00YKnJhmYqahaFWUgkg75saNTzMrbO';
		const ENDPOINT_PAYMENT_MOMO = 'https://test-payment.momo.vn/gw_payment/transactionProcessor';
	}