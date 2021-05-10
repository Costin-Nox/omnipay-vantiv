<?php namespace Omnipay\Vantiv\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use SimpleXMLElement;

/**
 * Vantiv Authorize Request
 */
class AuthorizeRequest extends AbstractRequest
{
    protected $transactionType = 'authorization';

    protected $transactionXml;

    /**
     * To string.
     * 
     * @return string 
     */
    public function __toString()
    {
        if (empty($this->transactionXml)) { return ''; }

        return $this->transactionXml->asXML();
    }

    /**
     * Return XML payload as object.
     * @return SimpleXMLElement 
     */
    public function getXmlPayload() : \SimpleXMLElement
    {
        return $this->transactionXml;
    }

    public function generateCurlString() : string
    {
        $string = "curl '{$this->getEndpoint()}' -A 'Moliza/5.0' \ 'Content-Type: text/xml; charset=utf-8' -X POST \ -d '" . (string)$this . "'";

        return $string;
    }

    /**
     * Run validation
     * @return bool 
     * @throws InvalidRequestException 
     */
    private function validateRequest() : bool
    {
        $this->validate('amount');

        if (empty($this->getCard()) && empty($this->getToken())) {
            throw new InvalidRequestException("Please specify a payment method as either card or token.");
        }

        $this->validate('merchantId', 'username', 'password','orderId');

        return true;
    }

    /**
     * Compose XML request
     * @return mixed 
     * @throws InvalidRequestException 
     */
    public function getData()
    {
        $this->validateRequest();

        $card = $this->getCard();
        $token = $this->getToken();

        $data = new \SimpleXMLElement('<cnpOnlineRequest xmlns="http://www.vantivcnp.com/schema"/>');
        $data->addAttribute('version', $this->getVersion());
        $data->addAttribute('merchantId', $this->getMerchantId());

        $authentication = $data->addChild('authentication');
        $authentication->addChild('user', $this->getUsername());
        $authentication->addChild('password', $this->getPassword());

        $transaction = $data->addChild($this->transactionType);
        $transaction->addAttribute('id', /*$this->getTransactionId()*/ "ididid");
        $transaction->addAttribute('customerId', $this->getCustomerId());
        $transaction->addAttribute('reportGroup', $this->getReportGroup());
        $transaction->addChild('orderId', $this->getOrderId());

        // The amount is sent as cents
        $transaction->addChild('amount', (string) $this->getAmountInteger());
        $transaction->addChild('orderSource', 'ecommerce');

        if ($card) {
            $billToAddress = $transaction->addChild('billToAddress');
            $billToAddress->addChild('name', $card->getBillingName());
            $billToAddress->addChild('addressLine1', $card->getBillingAddress1());
            $billToAddress->addChild('city', $card->getBillingCity());
            $billToAddress->addChild('state', $card->getBillingState());
            $billToAddress->addChild('zip', $card->getBillingPostcode());
            $billToAddress->addChild('country', $card->getBillingCountry());
            $billToAddress->addChild('email', $card->getEmail());
            $billToAddress->addChild('phone', $card->getBillingPhone());

            $cc = $transaction->addChild('card');
            $cc->addChild('type', $this->getCreditType($card->getBrand()));
            $cc->addChild('number', $card->getNumber());
            $cc->addChild('expDate', $card->getExpiryDate('m') . $card->getExpiryDate('y'));
            $cc->addChild('cardValidationNum', $card->getCvv());
        }

        if ($token) {
            $tokenElement = $transaction->addChild('token');
            $tokenElement->addChild('litleToken', $token);
        }

        $data = $this->cleanXml(($data));

        $this->transactionXml = $data;

        return $data;
    }

    protected function createResponse($response)
    {
        return $this->response = new AuthorizeResponse($this, $response);
    }

    public function getRequest()
    {
        return $this->transactionXml;
    }
}
