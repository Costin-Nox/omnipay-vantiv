<?php namespace Omnipay\Vantiv\Message;

use Omnipay\Common\CreditCard;
use Omnipay\Common\Exception\RuntimeException;
use Omnipay\Common\Http\ClientInterface;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    protected $version = '12.1';

    /**
     * Test Endpoint URL
     *
     * @var string URL
     */
    protected $testEndpoint = 'https://www.testvantivcnp.com/sandbox/communicator/online';

    /**
     * Pre-Live Endpoint URL
     *
     * @var string URL
     */
    protected $preLiveEndpoint = 'https://www.testvantivcnp.com/sandbox/communicator/online';

    /**
     * Live Endpoint URL
     *
     * @var string URL
     */
    protected $liveEndpoint = 'https://transact.vantivcnp.com/vap/communicator/online';

    /**
     * Clean up empty tags, this is less annoying than adding a check before each item :)
     * 
     * @param SimpleXMLElement $data 
     * 
     * @return SimpleXMLElement 
     */
    protected function cleanXml(\SimpleXMLElement $xml) : \SimpleXMLElement
    {
        $xmlString = $xml->asXML();

        //nested, so need to keep removing shit and see if it changed, then try again to remove nested group parents
        while(true)
        {
            $xmlStrinRef = $xmlString; //keep a reference to prev, see if it changes or not.
            $xmlString = preg_replace('~<([^\\s>])+>\\s*</\\1>~si', '', $xmlString);
            $xmlString = preg_replace('~<[^\\s>]+\\s*/>~si', '', $xmlString);
            if($xmlStrinRef === $xmlString) break; // If not changed, done.
        }
        
        $xml = simplexml_load_string($xmlString);

        return $xml;
    }

    /**
     * Get merchant id.
     * 
     * @return string 
     */
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    /**
     * Set merchant id.
     * 
     * @param mixed $value 
     * @return $this 
     * @throws RuntimeException 
     */
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
        $httpResponse = $this->httpClient->request(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            [
                'Content-Type'  => 'text/xml; charset=utf-8',
                'User-Agent' => 'Moliza/5.0'
            ],
            $data->asXML()
        );

        $data = simplexml_load_string($httpResponse->getBody()->getContents());


        return $this->createResponse($data);
    }
}
