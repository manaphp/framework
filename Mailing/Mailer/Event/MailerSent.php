<?php

declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Mailing\Mailer\Message;
use ManaPHP\Mailing\MailerInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class MailerSent
{
    public function __construct(
        public MailerInterface $mailer,
        public Message $message,
        public array $failedRecipients,
    ) {

    }
}
