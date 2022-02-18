<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Exception;
use Weboccult\EatcardCompanion\Models\MailJob;

/**
 * @author Darshit Hedpara
 */
class EatcardEmail
{
    private string $entityType = '';

    /** @var string|int|null */
    private $entityId;

    private string $mailType = '';

    private string $mailFromName = '';

    private string $subject = '';

    private string $content = '';

    private string $email = '';

    /** @var array<string> */
    private array $cc = [];

    /** @var array<string> */
    private array $bcc = [];

    /**
     * @param string $entityType
     *
     * @return EatcardEmail
     */
    public function entityType(string $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }

    /**
     * @param int|string|null $entityId
     *
     * @return EatcardEmail
     */
    public function entityId($entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @param string $mailType
     *
     * @return EatcardEmail
     */
    public function mailType(string $mailType): self
    {
        $this->mailType = $mailType;

        return $this;
    }

    /**
     * @param string $mailFromName
     *
     * @return EatcardEmail
     */
    public function mailFromName(string $mailFromName): self
    {
        $this->mailFromName = $mailFromName;

        return $this;
    }

    /**
     * @param string $subject
     *
     * @return EatcardEmail
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string $content
     *
     * @return EatcardEmail
     */
    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param $email
     *
     * @return EatcardEmail
     */
    public function email($email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param array $cc
     *
     * @return EatcardEmail
     */
    public function cc(array $cc): self
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * @param array $bcc
     *
     * @return EatcardEmail
     */
    public function bcc(array $bcc): self
    {
        $this->bcc = $bcc;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function dispatch()
    {
        $conditions = [
            'Email can not be empty.!'      => empty($this->email),
            'Entity type can not be empty.!'      => empty($this->entityType),
            'Entity Id can not be empty.!'      => empty($this->entityId),
            'Mail type can not be empty.!'      => empty($this->mailType),
            'From name can not be empty.!'      => empty($this->mailFromName),
            'Subject can not be empty.!'      => empty($this->subject),
            'Content can not be empty.!'      => empty($this->content),
        ];
        foreach ($conditions as $ex => $condition) {
            if ($condition) {
                throw new Exception($ex);
            }
        }
        $mailData['entity_type'] = $this->entityType;
        $mailData['entity_id'] = $this->entityId;
        $mailData['recipients'] = $this->email;
        $mailData['mail_type'] = $this->mailType;
        $mailData['from_name'] = $this->mailFromName;
        $mailData['subject'] = $this->subject;
        $mailData['message'] = $this->content;
        if (! empty($this->cc) && count($this->cc) > 0) {
            $mailData['cc'] = implode(',', $this->cc);
        }
        if (! empty($this->bcc) && count($this->bcc) > 0) {
            $mailData['bcc'] = implode(',', $this->bcc);
        }
        dd($mailData);
        MailJob::create($mailData);
    }
}
