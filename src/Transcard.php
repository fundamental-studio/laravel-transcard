<?php

namespace Fundamental\Transcard;

use Carbon\Carbon;
use Fundamental\Transcard\Exceptions\InvalidAmountException;
use Fundamental\Transcard\Exceptions\InvalidInvoiceException;
use Fundamental\Transcard\Exceptions\InvalidChecksumException;
use Fundamental\Transcard\Exceptions\InvalidCurrencyException;
use Fundamental\Transcard\Exceptions\InvalidExpirationException;

class Transcard
{
    private $merchantId;
    private $isProduction;

    private $language = 'BG';

    private $privateKey;
    private $publicKey;

    private $privateKeyPath;
    private $publicKeyPath;

    private $privateKeyPass;

    private $returnUrl;

    private $type;
    private $data;

    private $encoded;
    private $checksum;

    const AVAILABLE_LANGUAGES = ['BG', 'EN'];
    const AVAILABLE_TYPES = ['paylogin'];
    const AVAILABLE_CURRENCIES = ['BGN', 'EUR'];

    public function __construct(String $type = 'paylogin', array $data = [], String $language = 'BG')
    {
        $this->merchantId = config('TRANSCARD_MERCHANT_ID');
        $this->isProduction = config('TRANSCARD_PRODUCTION');

        $this->privateKeyPath = config('TRANSCARD_PRIVATE_KEY');
        $this->publicKeyPath = config('TRANSCARD_PUBLIC_KEY');

        $this->privateKey = 'file://' . $this->privateKeyPath;
        $this->publicKey = 'file://' . $this->publicKeyPath;

        $this->privateKeyPass = config('TRANSCARD_PRIVATE_KEY_PASS');

        $this->returnUrl = config('TRANSCARD_RETURN_URL');

        if (in_array($type, $this::AVAILABLE_TYPES)) {
            $this->type = $type;
        }

        if (in_array(strtoupper($language), $this::AVAILABLE_LANGUAGES)) {
            $this->language = strtoupper($language);
        }

        if (isset($data['amount'])) {
            $this->setData($data['invoice'], $data['amount'], $data['description']);
        }
    }

    /**
     * Setting main data for creating and sending the request.
     *
     * @param String $invoice
     * @param double|float|String $amount The amount
     * @param String $expiration
     * @param String $description Invoice description content in less than 100 symbols.
     * @param string $currency
     * @param String $encoding
     * @return void
     */
    public function setData($invoice = false, $amount, String $description = '')
    {
        $this->validateInvoice($invoice);
        $this->validateAmount($amount);
        $this->validateDescription($description);

        $this->data = [
            'MIN'           => $this->merchantId,
            'INVOICE'       => ($invoice == false and config('TRANSCARD_GENERATE_INVOICE')) ? (sprintf("%.0f", rand() * 100000)) : $invoice,
            'AMOUNT'        => $amount,
            'DESCR'         => $description,
            'ENCODING'      => 'UTF-8',
            'CURRENCY'      => 'BGN',
            'STOCKINFO'     => '',
            'VERSION'       => '2.0'
        ];

        $this->encodeRequestData();
    }

    public function verifyResponse($response)
    {
        if (empty($response)) {
            throw new Exception('Invalid response.', 1);
        }

        $signPath = explode('#', $response);

        if (count($signPath) !== 2) {
            throw new Exception('Invalid response.', 1);
        }

        $certificate = $this->publicKeyPath;
        $publicKey = null;

        if (false === ($publicKey = openssl_pkey_get_public($certificate))) {
            throw new Exception('Invalid certificate.', 1);
        }

        $data = base64_decode($signPath[0]);
        $data = openssl_verify($data, base64_decode($signPath[1]), $publicKey);

        if ($data)
        {
            return $data;
        }
        else
        {
            throw new Exception('Corrupted signature.', 1);
        }

        return false;
    }

    public function verifyNotifyResponse($encoded, $response)
    {
        if (empty($encoded) or empty($response)) {
            throw new Exception('Invalid request.', 1);
        }

        $certificate = $this->publicKeyPath;
        $publicKey = null;

        if (false === ($publicKey = openssl_pkey_get_public($certificate))) {
            throw new Exception('Invalid certificate.', 1);
        }

        $data = openssl_verify($encoded, base64_decode($response), $publicKey);

        if ($data)
        {
            $data = $this->parseSignedData(base64_decode($encoded));
        }
        else
        {
            throw new Exception('Corrupted signature.', 1);
        }
    }

    public function generateChecksum(array $data = []): String
    {
        $data = (count($data) == 0) ? $this->encoded : $data;

        $privateKey = null;

        if (false === ($privateKey = openssl_pkey_get_private($this->privateKey, $this->privateKeyPass)))
        {
            throw new Exception('Error during private key load.', 1);
        }

        $signature = '';

        if (!openssl_sign($data, $signature, $privateKey))
        {
            throw new Exception('Error during private key load.', 1);
        }

        openssl_free_key($privateKey);

        return base64_encode($signature);
    }

    public function parseSignedData(String $data): array
    {
        $response = array();

        $list = explode(":", $data);

        if (!empty($list))
        {
            foreach ($list as $line)
            {
                if (!empty($line))
                {
                    $tmp = explode("=", $line);
                    $response[trim($tmp[0])] = trim($tmp[1]);
                }
            }
        }

        return $response;
    }

    public function generateTranscardPaymentFields(): String
    {

        return '
            <input type="hidden">';
    }

    public function getPaymentParameters(): array
    {
        return [
            'PAGE' => $this->type,
            'ENCODED' => $this->encoded,
            'CHECKSUM' => $this->generateChecksum(),
            'LANG' => $this->language,

            'URL_OK' => $this->returnUrl,
            'URL_CANCEL' => $this->returnUrl
        ];
    }

    public static function parseResult($pbKey, array $data): array
    {
        $publicKey = null;

        if (false === ($publicKey = openssl_pkey_get_public($pbKey)))
        {
            throw new Exception('Error during public key load.', 1);
        }

        $signature = openssl_verify($data['encoded'], base64_decode($data['checksum']), $publicKey);

        if ($signature)
        {
            return base64_decode($data['encoded']);
        }
        else
        {
            throw new Exception('Corrupted data.', 1);
        }
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @return array
     */
    private function formatDataArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[] = sprintf("%s=%s", $key, $value);
        }

        return $result;
    }

    /**
     * Encode the needed and formatted data.
     *
     * @return void
     */
    private function encodeRequestData()
    {
        $this->encoded = base64_encode(implode('\n', $this->formatDataArray($this->data)));
    }

    private function validateInvoice($invoice)
    {
        if (!preg_match('/^\d+$/', (String) $invoice)) {
            throw new InvalidInvoiceException();
        }
    }

    private function validateAmount($amount)
    {
        if (!preg_match('/^\d+(\.(\d+){1,2})?$/', (String) $amount)) {
            throw new InvalidAmountException();
        }
    }

    private function validateDescription($description)
    {
        if (strlen($description) > 100) {
            throw new InvalidDescriptionException();
        }
    }

    private function validateCurrency($currency)
    {
        if (!in_array($currency, $this::AVAILABLE_CURRENCIES)) {
            throw new InvalidCurrencyException();
        }
    }
}