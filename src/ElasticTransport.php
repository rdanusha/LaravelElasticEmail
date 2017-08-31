<?php

namespace Rdanusha\LaravelElasticEmail;

use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Storage;
use Swift_Mime_Message;

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
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
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
        $count = count($attachments);
        if (is_array($attachments) && $count > 0) {
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

        if ($count > 0) {
            $this->deleteTempAttachmentFiles($data, $count);
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
                $attachedFile = $attachment->getBody();
                $fileName = $attachment->getFilename();
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $tempName = uniqid().'.' . $ext;
                Storage::put($tempName, $attachedFile);
                $type = $attachment->getContentType();
                $attachedFilePath = storage_path('app\\' . $tempName);
                $data['file_' . $i] = new \CurlFile($attachedFilePath, $type, $fileName);
                $i++;
            }
        }

        return $data;
    }


    /**
     * Upload attachment to elastic mail
     * @param $filepath
     * @param $filename
     * @return array
     */
    function uploadAttachment($filepath, $filename)
    {

        $data = http_build_query(array('username' => env('ELASTIC_ACCOUNT'), 'api_key' => env('ELASTIC_KEY'), 'file' => $filename));
        $file = file_get_contents($filepath);
        $result = '';

        $fp = fsockopen('ssl://api.elasticemail.com', 443, $errno, $errstr, 30);

        if ($fp) {
            fputs($fp, "PUT /attachments/upload?" . $data . " HTTP/1.1\r\n");
            fputs($fp, "Host: api.elasticemail.com\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: " . strlen($file) . "\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $file);
            while (!feof($fp)) {
                $result .= fgets($fp, 128);
            }
        } else {
            return array(
                'status' => false,
                'error' => $errstr . '(' . $errno . ')',
                'result' => $result);
        }
        fclose($fp);
        $result = explode("\r\n\r\n", $result, 2);
        return array(
            'status' => true,
            'attachId' => isset($result[1]) ? $result[1] : ''
        );
    }


    /**
     * Get the plain text part.
     *
     * @param  \Swift_Mime_Message $message
     * @return text|null
     */
    protected function getText(Swift_Mime_Message $message)
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
    protected function getFromAddress(Swift_Mime_Message $message)
    {
        return [
            'email' => array_keys($message->getFrom())[0],
            'name' => array_values($message->getFrom())[0],
        ];
    }

    protected function getEmailAddresses(Swift_Mime_Message $message, $method = 'getTo')
    {
        $data = call_user_func([$message, $method]);

        if (is_array($data)) {
            return implode(',', array_keys($data));
        }
        return '';
    }

    protected function deleteTempAttachmentFiles($data, $count)
    {
        for ($i = 1; $i <= $count; $i++) {
            $file = $data['file_' . $i]->name;
            unlink($file);
        }
    }
}
