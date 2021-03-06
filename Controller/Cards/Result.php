<?php

namespace Paytpv\Payment\Controller\Cards;

use Paytpv\Payment\Block\Process;

class Result extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Paytpv\Payment\Helper\Data
     */
    private $_helper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry\Registry
     */
    private $coreRegistry;

    /**
     * @var \Paytpv\Payment\Logger\Logger
     */
    private $_logger;

    /**
     * Result constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paytpv\Payment\Helper\Data       $helper
     * @param \Magento\Framework\Registry           $coreRegistry
     * @param \Paytpv\Payment\Logger\Logger     $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paytpv\Payment\Helper\Data $helper,
        \Magento\Framework\Registry $coreRegistry,
        \Paytpv\Payment\Logger\Logger $logger
    ) {
        $this->_helper = $helper;
        $this->_url = $context->getUrl();
        $this->coreRegistry = $coreRegistry;
        $this->_logger = $logger;

        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $response = $this->getRequest()->getParams();
            //the default
            $params['returnUrl'] = $this->_url->getUrl('/');

            if ($response) {
                $result = $this->_handleResponse($response);
                $params['returnUrl'] = $this->_url->getUrl('paytpv_payment/cards/success');
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $this->coreRegistry->register(Process\Result::REGISTRY_KEY, $params);

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    private function _handleResponse($response)
    {
        if (empty($response)) {
            $this->_logger->critical(__('Empty response received from gateway'));

            return false;
        }

        $this->_helper->logDebug(__('Gateway response:').print_r($this->_helper->stripTrimFields($response), true));

        // validate response
        $authStatus = $this->_validateResponse($response);
        if (!$authStatus) {
            $this->_logger->critical(__('Invalid response received from gateway.'));

            return false;
        }
        // happy with the response
        return true;
    }

    /**
     * Validate response using sha1 signature.
     *
     * @param array $response
     *
     * @return bool
     */
    private function _validateResponse($response)
    {
        $timestamp = $response['TIMESTAMP'];
        $result = $response['RESULT'];
        $orderid = $response['ORDER_ID'];
        $message = $response['MESSAGE'];
        $authcode = $response['AUTHCODE'];
        $pasref = $response['PASREF'];
        $paytpvsha1 = $response['SHA1HASH'];

        $merchantid = $this->_helper->getConfigData('merchant_id');

        $sha1hash = $this->_helper->signFields("$timestamp.$merchantid.$orderid.$result.$message.$pasref.$authcode");

        //Check to see if hashes match or not
        if (strcmp($sha1hash, $paytpvsha1) != 0) {
            return false;
        }

        return true;
    }
}
