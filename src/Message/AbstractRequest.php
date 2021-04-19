<?php namespace Omnipay\Vantiv\Message;

use Omnipay\Common\CreditCard;
use Omnipay\Common\Http\ClientInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    protected $version = '9.4';

    /**
     * Test Endpoint URL
     *
     * @var string URL
     */
    protected $testEndpoint = 'https://transact.vantivprelive.com/vap/communicator/online';

    /**
     * Pre-Live Endpoint URL
     *
     * @var string URL
     */
    protected $preLiveEndpoint = 'https://transact.vantivprelive.com/vap/communicator/online';

    /**
     * Live Endpoint URL
     *
     * @var string URL
     */
    protected $liveEndpoint = 'https://transact.vantivcnp.com/vap/communicator/online';

    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    public function getUsername()
    {
        return $this->getParameter('username');
    }

    public function setUsername($value)
    {
        return $this->setParameter('username', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    public function getReportGroup()
    {
        return $this->getParameter('reportGroup');
    }

    public function setReportGroup($value)
    {
        return $this->setParameter('reportGroup', $value);
    }

    public function getCustomerId()
    {
        return $this->getParameter('customerId');
    }

    public function setCustomerId($value)
    {
        return $this->setParameter('customerId', $value);
    }

    public function getOrderId()
    {
        return $this->getParameter('orderId');
    }

    public function setOrderId($value)
    {
        return $this->setParameter('orderId', $value);
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getPreLiveMode()
    {
        return $this->getParameter('preLiveMode');
    }

    public function setPreLiveMode($value)
    {
        return $this->setParameter('preLiveMode', $value);
    }

    /**
     * Overriding constructor to force guzzle with ssl params.
     * Create a new Request
     *
     * @param ClientInterface $httpClient  A HTTP client to make API calls with
     * @param HttpRequest     $httpRequest A Symfony HTTP request object
     */
    public function __construct(ClientInterface $httpClient, HttpRequest $httpRequest)
    {
        /** Getting this to wokr was a bit of a fun rabbit hole, this is the config to disable ssl issue on test server */
        $config = ['timeout' => 3, 'verify' => false];
        /** build guzzle here rather than allow discovery, so we can specify config, otherwise we get default and theres no api to alter config */
        $guzzle = \Http\Adapter\Guzzle7\Client::createWithConfig($config);
        /** build the omnipay client passing in guzzle with the config */
        $client = new \Omnipay\Common\Http\Client($guzzle);
//dd($client);
//dd(curl_version());
        /** Now it's compatible */
        parent::__construct($client, $httpRequest);
    }

    /**
     * Get API endpoint URL
     *
     * If test mode and pre-live mode are both set, then
     * pre-live mode will take precedence.
     *
     * @return string
     */
    public function getEndpoint()
    {
        if ($this->getPreLiveMode()) {
            return $this->preLiveEndpoint;
        } elseif ($this->getTestMode()) {
            return $this->testEndpoint;
        } else {
            return $this->liveEndpoint;
        }
    }

    /**
     * Get HTTP Method
     *
     * This is nearly always POST but can be over-ridden in sub classes.
     *
     * @return string
     */
    public function getHttpMethod()
    {
        return 'POST';
    }

    /**
     * Get Content Type
     *
     * This is nearly always 'text/xml; charset=utf-8' but can be over-ridden in sub classes.
     *
     * @return string
     */
    public function getContentType()
    {
        return 'text/xml; charset=utf-8';
    }

    /**
     * Get Credit Type
     *
     * Match the brand up to the supported card format, throwd exception on unsupported card.
     *
     * @return string
     */
    public function getCreditType($brand)
    {
        $codes = array(
            CreditCard::BRAND_AMEX        => 'AX',
            CreditCard::BRAND_DINERS_CLUB => 'DC',
            CreditCard::BRAND_DISCOVER    => 'DI',
            CreditCard::BRAND_JCB         => 'JC',
            CreditCard::BRAND_MASTERCARD  => 'MC',
            CreditCard::BRAND_VISA        => 'VI'
        );

        if (isset($codes[$brand])) {
            return $codes[$brand];
        }

        return null;
    }

    /**
     * Send Data
     *
     * @param \SimpleXMLElement $data Data
     *
     * @access public
     * @return RedirectResponse
     */
    public function sendData($data)
    {
        return $this->httpClient->request(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            [
                'Content-Type'  => 'text/xml; charset=utf-8'
            ],
            $data->asXML()
        );
    }
}
