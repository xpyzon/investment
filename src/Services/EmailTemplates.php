<?php
declare(strict_types=1);

namespace App\Services;

class EmailTemplates
{
    private static function base(string $title, string $bodyHtml): string
    {
        return '<!doctype html><html><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>'.htmlspecialchars($title).'</title>'
            .'</head><body style="margin:0;padding:0;background:#fff;color:#000;font-family:Inter,system-ui,Arial,sans-serif">'
            .'<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#fff"><tr><td align="center">'
            .'<table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;border:1px solid #000;margin:24px;padding:24px">'
            .'<tr><td>'
            .'<h1 style="font-size:20px;margin:0 0 16px 0">'.htmlspecialchars($title).'</h1>'
            .$bodyHtml
            .'<hr style="border:0;border-top:1px solid #000;margin:24px 0" />'
            .'<p style="font-size:12px;line-height:1.5;color:#000">This is an automated message from your investment platform.</p>'
            .'</td></tr></table>'
            .'</td></tr></table>'
            .'</body></html>';
    }

    public static function depositPending(string $currency, string $amount, int $confirmationsRequired, ?string $txid = null): string
    {
        $body = '<p>Your deposit is detected and pending confirmations.</p>'
               .'<p><strong>Amount:</strong> '.htmlspecialchars($amount).' '.htmlspecialchars($currency).'</p>'
               .'<p><strong>Confirmations required:</strong> '.(int)$confirmationsRequired.'</p>'
               .($txid ? '<p><strong>TxID:</strong> '.htmlspecialchars($txid).'</p>' : '');
        return self::base('Deposit received (pending)', $body);
    }

    public static function depositConfirmed(string $currency, string $amount, ?string $txid = null): string
    {
        $body = '<p>Your deposit is confirmed and your balance has been credited.</p>'
               .'<p><strong>Amount:</strong> '.htmlspecialchars($amount).' '.htmlspecialchars($currency).'</p>'
               .($txid ? '<p><strong>TxID:</strong> '.htmlspecialchars($txid).'</p>' : '');
        return self::base('Deposit confirmed', $body);
    }

    public static function withdrawalRequested(string $currency, string $amount, string $address): string
    {
        $body = '<p>Your withdrawal request has been received and is pending manual approval.</p>'
               .'<p><strong>Amount:</strong> '.htmlspecialchars($amount).' '.htmlspecialchars($currency).'</p>'
               .'<p><strong>Address:</strong> '.htmlspecialchars($address).'</p>';
        return self::base('Withdrawal requested', $body);
    }

    public static function withdrawalApproved(string $currency, string $amount): string
    {
        $body = '<p>Your withdrawal has been approved and will be processed shortly.</p>'
               .'<p><strong>Amount:</strong> '.htmlspecialchars($amount).' '.htmlspecialchars($currency).'</p>';
        return self::base('Withdrawal approved', $body);
    }

    public static function withdrawalRejected(string $currency, string $amount): string
    {
        $body = '<p>Your withdrawal request was rejected. Please contact support for details.</p>'
               .'<p><strong>Amount:</strong> '.htmlspecialchars($amount).' '.htmlspecialchars($currency).'</p>';
        return self::base('Withdrawal rejected', $body);
    }

    public static function tradeExecuted(string $productName, string $productType, string $amount, string $currency = 'USD'): string
    {
        $body = '<p>Your investment order was executed.</p>'
               .'<p><strong>Product:</strong> '.htmlspecialchars($productName).' ('.htmlspecialchars($productType).')</p>'
               .'<p><strong>Amount:</strong> '.htmlspecialchars($amount).' '.htmlspecialchars($currency).'</p>';
        return self::base('Trade executed', $body);
    }
}