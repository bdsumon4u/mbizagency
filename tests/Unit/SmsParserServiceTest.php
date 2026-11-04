<?php

use App\Services\SmsParserService;
use Carbon\Carbon;

test('parses bkash personal received money sms correctly', function () {
    $parser = new SmsParserService;
    $raw = 'You have received Tk 1,500.00 from 01712345678. Fee Tk 0.00. Balance Tk 15,250.00. TrxID 9K83XJ8KL2 at 28/10/2026 15:42';

    $result = $parser->parse($raw);

    expect($result['provider'])->toBe('bkash')
        ->and($result['trx_id'])->toBe('9K83XJ8KL2')
        ->and($result['amount'])->toBe(1500.00)
        ->and($result['fee'])->toBe(0.0)
        ->and($result['sender_account'])->toBe('01712345678')
        ->and($result['transaction_time'])->not->toBeNull();
});

test('uses the date printed in an sms and the server clock only when the sms time is absent', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 15:30:00'));

    try {
        $parser = new SmsParserService;
        $dateOnly = $parser->extractTransactionTime('Credited on 03-11-26');
        $dateAndTime = $parser->extractTransactionTime('Transaction at 03/11/2026 14:15');

        expect($dateOnly?->format('Y-m-d H:i:s'))->toBe('2026-11-03 15:30:00')
            ->and($dateAndTime?->format('Y-m-d H:i:s'))->toBe('2026-11-03 14:15:00');
    } finally {
        Carbon::setTestNow();
    }
});

test('parses nagad money received sms correctly', function () {
    $parser = new SmsParserService;
    $raw = 'Money Received: Tk 2,500.00 from 01812345678 is successful. TxnID: 71BKLMN8. Date: 28/10/2026 17:05. Balance: Tk 12,000.00';

    $result = $parser->parse($raw);

    expect($result['provider'])->toBe('nagad')
        ->and($result['trx_id'])->toBe('71BKLMN8')
        ->and($result['amount'])->toBe(2500.00)
        ->and($result['sender_account'])->toBe('01812345678');
});

test('parses rocket received money sms correctly', function () {
    $parser = new SmsParserService;
    $raw = 'You have received Tk 500.00 from 01912345678-9. A/C: 01712345678-0. TxnId: 3829102482. Date: 28-OCT-26 14:10:00. Balance: Tk 10,000.00';

    $result = $parser->parse($raw);

    expect($result['provider'])->toBe('rocket')
        ->and($result['trx_id'])->toBe('3829102482')
        ->and($result['amount'])->toBe(500.00);
});

test('parses eastern bank ebl sms correctly', function () {
    $parser = new SmsParserService;
    $raw = 'AC 107***337 is credited with BDT 38100 as EBL Account Transfer on 29-OCT-26 05:20:41 PM Balance is BDT 38207.96 Thanks. EBL Helpline 16230';

    $result = $parser->parse($raw);

    expect($result['provider'])->toBe('ebl')
        ->and($result['amount'])->toBe(38100.00)
        ->and($result['receiver_account'])->toBe('107***337')
        ->and($result['balance'])->toBe(38207.96)
        ->and($result['txn_type'])->toBe('EBL Account Transfer')
        ->and($result['transaction_time'])->not->toBeNull();
});

test('parses brac bank astha sms correctly', function () {
    $parser = new SmsParserService;
    $raw = 'TK 19,500.00 has been credited to your A/C# 20539**0001 on 29-10-26. Your A/C balance is TK 6,11,937.85. For Enquiry call: 16221';

    $result = $parser->parse($raw);

    expect($result['provider'])->toBe('brac_bank')
        ->and($result['amount'])->toBe(19500.00)
        ->and($result['receiver_account'])->toBe('20539**0001')
        ->and($result['balance'])->toBe(611937.85)
        ->and($result['transaction_time'])->not->toBeNull();
});

test('parses city bank citytouch sms correctly', function () {
    $parser = new SmsParserService;
    $raw = "29-Oct-2026\nCITYTOUCH TXN\nTk. 6,400 Deposit\nTk. 1,35,318 Balance\nA/C: 1503***4001";

    $result = $parser->parse($raw);

    expect($result['provider'])->toBe('city_bank')
        ->and($result['amount'])->toBe(6400.00)
        ->and($result['receiver_account'])->toBe('1503***4001')
        ->and($result['balance'])->toBe(135318.00)
        ->and($result['txn_type'])->toBe('CITYTOUCH TXN')
        ->and($result['transaction_time'])->not->toBeNull();
});

test('parses dbbl fund transfer nexuspay sms correctly', function () {
    $parser = new SmsParserService;
    $raw = 'Dear Sir, your A/C ***2219 credited (Fund Transfer) by Tk11,875.00 on 29-10-2026 12:20:10 PM C/B Tk29,886.61. NexusPay';

    $result = $parser->parse($raw);

    expect($result['provider'])->toBe('dbbl')
        ->and($result['amount'])->toBe(11875.00)
        ->and($result['receiver_account'])->toBe('***2219')
        ->and($result['balance'])->toBe(29886.61)
        ->and($result['txn_type'])->toBe('Fund Transfer')
        ->and($result['transaction_time'])->not->toBeNull();
});

test('parses dbbl atm crm cash deposit sms correctly', function () {
    $parser = new SmsParserService;
    $raw = 'Dear Sir, your A/C ***2219 credited CRM/CDM Cash Deposit On-Us by Tk8,000.00 on 28-10-2026 09:54:59 PM C/B Tk9,691.61. NexusPay';

    $result = $parser->parse($raw);

    expect($result['provider'])->toBe('dbbl')
        ->and($result['amount'])->toBe(8000.00)
        ->and($result['receiver_account'])->toBe('***2219')
        ->and($result['balance'])->toBe(9691.61)
        ->and($result['txn_type'])->toBe('CRM/CDM Cash Deposit On-Us')
        ->and($result['transaction_time'])->not->toBeNull();
});

test('matches masked bank account correctly against full account number', function () {
    $parser = new SmsParserService;

    // City Bank: full account vs 1503***4001
    expect($parser->matchesMaskedAccount('1503915274001', '1503***4001'))->toBeTrue();

    // DBBL: full account vs ***2219
    expect($parser->matchesMaskedAccount('1641100042219', '***2219'))->toBeTrue();

    // BRAC Bank: full account vs 20539**0001
    expect($parser->matchesMaskedAccount('2053944080001', '20539**0001'))->toBeTrue();

    // EBL: full account vs 107***337
    expect($parser->matchesMaskedAccount('10712345337', '107***337'))->toBeTrue();

    // Non-matching accounts
    expect($parser->matchesMaskedAccount('1503915274001', '***9999'))->toBeFalse();
    expect($parser->matchesMaskedAccount('1641100042219', '1234***2219'))->toBeFalse();
});
