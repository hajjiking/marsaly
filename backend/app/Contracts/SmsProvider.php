<?php

namespace App\Contracts;

interface SmsProvider
{
    /** Send a raw text message to an E.164 phone number. Returns delivery id. */
    public function send(string $to, string $message): string;
}
