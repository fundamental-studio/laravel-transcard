<?php

namespace Fundamental\Transcard;

use Carbon\Carbon;
use Fundamental\Transcard\Exceptions\InvalidAmountException;
use Fundamental\Transcard\Exceptions\CorruptedDataException;
use Fundamental\Transcard\Exceptions\PublicKeyLoadException;
use Fundamental\Transcard\Exceptions\InvalidInvoiceException;
use Fundamental\Transcard\Exceptions\InvalidRequestException;
use Fundamental\Transcard\Exceptions\PrivateKeyLoadException;
use Fundamental\Transcard\Exceptions\InvalidResponseException;
use Fundamental\Transcard\Exceptions\InvalidChecksumException;
use Fundamental\Transcard\Exceptions\InvalidCurrencyException;
use Fundamental\Transcard\Exceptions\InvalidExpirationException;
use Fundamental\Transcard\Exceptions\InvalidCertificateException;
use Fundamental\Transcard\Exceptions\CorrputedSignatureException;

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

    private $gatewayUrl = '';
    private $testGatewayUrl = '';

    const AVAILABLE_LANGUAGES = ['BG', 'EN'];
    const AVAILABLE_TYPES = ['paylogin'];
    const AVAILABLE_CURRENCIES = ['BGN', 'EUR'];

    /**
     * Undocumented function
     *
     * @param String $type
     * @param array $data
     * @param String $language
     */
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

    /**
     * Undocumented function
     *
     * @param [type] $response
     * @return void
     */
    public function verifyResponse($response)
    {
        if (empty($response)) {
            throw new InvalidResponseException();
        }

        $signPath = explode('#', $response);

        if (count($signPath) !== 2) {
            throw new InvalidResponseException();
        }

        $certificate = $this->publicKeyPath;
        $publicKey = null;

        if (false === ($publicKey = openssl_pkey_get_public($certificate))) {
            throw new InvalidCertificateException();
        }

        $data = base64_decode($signPath[0]);
        $data = openssl_verify($data, base64_decode($signPath[1]), $publicKey);

        if ($data)
        {
            return $data;
        }
        else
        {
            throw new CorruptedSignatureException();
        }

        return false;
    }

    /**
     * Undocumented function
     *
     * @param [type] $encoded
     * @param [type] $response
     * @return void
     */
    public function verifyNotifyResponse($encoded, $response)
    {
        if (empty($encoded) or empty($response)) {
            throw new InvalidRequestException();
        }

        $certificate = $this->publicKeyPath;
        $publicKey = null;

        if (false === ($publicKey = openssl_pkey_get_public($certificate))) {
            throw new InvalidCertificateException();
        }

        $data = openssl_verify($encoded, base64_decode($response), $publicKey);

        if ($data)
        {
            $data = $this->parseSignedData(base64_decode($encoded));
        }
        else
        {
            throw new CorruptedSignatureException();
        }
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @return String
     */
    public function generateChecksum(array $data = []): String
    {
        $data = (count($data) == 0) ? $this->encoded : $data;

        $privateKey = null;

        if (false === ($privateKey = openssl_pkey_get_private($this->privateKey, $this->privateKeyPass)))
        {
            throw new PrivateKeyLoadException();
        }

        $signature = '';

        if (!openssl_sign($data, $signature, $privateKey))
        {
            throw new PrivateKeyLoadException();
        }

        openssl_free_key($privateKey);

        return base64_encode($signature);
    }

    /**
     * Undocumented function
     *
     * @param String $data
     * @return array
     */
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

    /**
     * Get the target url for the ePay platform, using the english version and the test parameter.
     *
     * @return String
     */
    public function getTargetUrl(): String
    {
        return ($this->isProduction) ? $this->gatewayUrl : $this->testGatewayUrl;
    }

    /**
     * Get all hidden input fields for the needed request.
     *.
     * @return String All needed hidden input fields
     */
    public function generatePaymentFields(): String
    {

        return '
            <input type="hidden" name="PAGE" value="' . $this->type . '">
            <input type="hidden" name="LANG" value="' . $this->language . '">
            <input type="hidden" name="ENCODED" value="' . $this->encoded . '">
            <input type="hidden" name="CHECKSUM" value="' . $this->checksum .'">
            <input type="hidden" name="URL_OK" value="' . $this->urls['ok'] . '">
            <input type="hidden" name="URL_CANCEL" value="' . $this->urls['cancel'] . '">';
    }

    /**
     * Returns a html form with all hidden input fields for the needed request.
     *
     * @param String $id The id element of the generated form.
     * @return String Html form with all hidden fields and set id attribute
     */
    public function generatePaymentForm(String $id = ''): String
    {
        return '
            <form id="' . $id . '" action="' . $this->getTargetUrl() . '" method="post">
                ' . $this->generatePaymentFields() . '
            </form>';
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function getPaymentParameters(): array
    {
        return [
            'URL' => $this->getTargetUrl(),
            'PAGE' => $this->type,
            'ENCODED' => $this->encoded,
            'CHECKSUM' => $this->generateChecksum(),
            'LANG' => $this->language,

            'URL_OK' => $this->returnUrl,
            'URL_CANCEL' => $this->returnUrl
        ];
    }

    /**
     * Undocumented function
     *
     * @param [type] $pbKey
     * @param array $data
     * @return array
     */
    public static function parseResult($pbKey, array $data): array
    {
        $publicKey = null;

        if (false === ($publicKey = openssl_pkey_get_public($pbKey)))
        {
            throw new PublicKeyLoadException();
        }

        $signature = openssl_verify($data['encoded'], base64_decode($data['checksum']), $publicKey);

        if ($signature)
        {
            return base64_decode($data['encoded']);
        }
        else
        {
            throw new InvalidChecksumException();
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