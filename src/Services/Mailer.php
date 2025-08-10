<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = Env::get('MAIL_HOST', 'localhost');
        $this->mailer->Port = (int)Env::get('MAIL_PORT', '25');
        $username = Env::get('MAIL_USERNAME');
        $password = Env::get('MAIL_PASSWORD');
        if ($username && $password) {
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $username;
            $this->mailer->Password = $password;
        }
        $encryption = Env::get('MAIL_ENCRYPTION');
        if ($encryption) {
            $this->mailer->SMTPSecure = $encryption; // tls or ssl
        }
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->isHTML(true);
        $from = Env::get('MAIL_FROM_ADDRESS', 'no-reply@example.com');
        $fromName = Env::get('MAIL_FROM_NAME', 'Investment Platform');
        $this->mailer->setFrom($from, $fromName);
    }

    public function send(string $to, string $subject, string $html): bool
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($to);
        $this->mailer->Subject = $subject;
        $this->mailer->Body = $html;
        return $this->mailer->send();
    }
}