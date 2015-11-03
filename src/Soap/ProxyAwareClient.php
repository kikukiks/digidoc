<?php
namespace Bigbank\DigiDoc\Soap;

use Bigbank\DigiDoc\Exceptions\IdException;

/**
 * A SOAP client that handles HTTP(S) proxy correctly
 */
class ProxyAwareClient extends \SoapClient implements SoapClient
{

    /**
     * @var array
     */
    protected $options = [
        'exceptions' => true,
        'proxy_host' => null,
        'proxy_port' => null
    ];

    /**
     * @param string $url
     * @param string $data
     * @param string $action
     *
     * @return string
     */
    protected function callCurl($url, $data, $action)
    {

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);

        $headers = ["Content-Type: text/xml", 'SOAPAction: "' . $action . '"'];
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_FRESH_CONNECT, true);

        curl_setopt($handle, CURLOPT_PROXY, $this->getProxyString());

        $response = curl_exec($handle);
        curl_close($handle);


        return $response;
    }

    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int    $version
     * @param int    $one_way
     *
     * @return string
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {

        return $this->callCurl($location, $request, $action);
    }

    /**
     * @return bool|string
     */
    protected function getProxyString()
    {

        if (empty($this->_proxy_host) || empty($this->_proxy_port)) {
            return false;
        }
        return sprintf('%s:%d', $this->_proxy_host, $this->_proxy_port);
    }

    public function __soapCall(
        $function_name,
        array $arguments,
        array $options = null,
        $input_headers = null,
        array &$output_headers = null
    ) {

        try {
            return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
        } catch (\SoapFault $fault) {
            throw new IdException(
                $fault->detail->message,
                (int)$fault->faultstring
            );
        }
    }


}
