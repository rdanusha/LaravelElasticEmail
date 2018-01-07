<?php

namespace Rdanusha\LaravelElasticEmail;

use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Swift_Mime_SimpleMessage;

class ElasticTransport extends Transport
{

    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The Elastic Email API key.
     *
     * @var string
     */
    protected $key;

    /**
     * The Elastic Email username.
     *
     * @var string
     */
    protected $account;

    /**
     * THe Elastic Email API end-point.
     *
     * @var string
     */
    protected $url = 'https://api.elasticemail.com/v2/email/send';

    /**
     * Create a new Elastic Email transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface $client
     * @param  string $key
     * @param  string $username
     *
     * @return void
     */
    public function __construct(ClientInterface $client, $key, $account)
    {
        $this->client = $client;
        $this->key = $key;
        $this->account = $account;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {

        $this->beforeSendPerformed($message);

        $data = [
            'api_key' => $this->key,
            'account' => $this->account,
            'msgTo' => $this->getEmailAddresses($message),
            'msgCC' => $this->getEmailAddresses($message, 'getCc'),
            'msgBcc' => $this->getEmailAddresses($message, 'getBcc'),
            'msgFrom' => $this->getFromAddress($message)['email'],
            'msgFromName' => $this->getFromAddress($message)['name'],
            'from' => $this->getFromAddress($message)['email'],
            'fromName' => $this->getFromAddress($message)['name'],
            'to' => $this->getEmailAddresses($message),
            'subject' => $message->getSubject(),
            'body_html' => $message->getBody(),
            'body_text' => $this->getText($message),

        ];


        $attachments = $message->getChildren();
        $attachmentCount = $this->checkAttachmentCount($attachments);
        if ($attachmentCount > 0) {
            $data = $this->attach($attachments, $data);
        }
        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));

        $result = curl_exec($ch);
        curl_close($ch);

        if ($attachmentCount > 0) {
            $this->deleteTempAttachmentFiles($data, $attachmentCount);
        }

        return $result;
    }


    /**
     * Add attachments to post data array
     * @param $attachments
     * @param $data
     * @return mixed
     */
    public function attach($attachments, $data)
    {
        if (is_array($attachments) && count($attachments) > 0) {
            $i = 1;
            foreach ($attachments AS $attachment) {
                if ($attachment instanceof \Swift_Attachment) {
                    $attachedFile = $attachment->getBody();
                    $fileName = $attachment->getFilename();
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    $tempName = uniqid() . '.' . $ext;
                    Storage::put($tempName, $attachedFile);
                    $type = $attachment->getContentType();
                    $attachedFilePath = storage_path('app\\' . $tempName);
                    $data['file_' . $i] = new \CurlFile($attachedFilePath, $type, $fileName);
                    $i++;
                }
            }
        }

        return $data;
    }


    /**
     * Check Swift_Attachment count
     * @param $attachments
     * @return bool
     */
    public function checkAttachmentCount($attachments)
    {
        $count = 0;
        foreach ($attachments AS $attachment) {
            if ($attachment instanceof \Swift_Attachment) {
                $count++;
            }
        }
        return $count;
    }


    /**
     * Get the plain text part.
     *
     * @param  \Swift_Mime_Message $message
     * @return text|null
     */
    protected function getText(Swift_Mime_SimpleMessage $message)
    {
        $text = null;

        foreach ($message->getChildren() as $child) {
            if ($child->getContentType() == 'text/plain') {
                $text = $child->getBody();
            }
        }

        return $text;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return array
     */
    protected function getFromAddress(Swift_Mime_SimpleMessage $message)
    {
        return [
            'email' => array_keys($message->getFrom())[0],
            'name' => array_values($message->getFrom())[0],
        ];
    }

    protected function getEmailAddresses(Swift_Mime_SimpleMessage $message, $method = 'getTo')
    {
        $data = call_user_func([$message, $method]);

        if (is_array($data)) {
            return implode(',', array_keys($data));
        }
        return '';
    }

    /**
     * delete temp attachment files
     * @param $data
     * @param $count
     */
    protected function deleteTempAttachmentFiles($data, $count)
    {
        for ($i = 1; $i <= $count; $i++) {
            $file = $data['file_' . $i]->name;
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}
