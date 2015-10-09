<?php
/**
 * Created by Ariel Allon @ Gorilla
 *    aallon@gorillagroup.com
 * Creation date: 9/20/12 4:28 PM
 */

class ICC_Debugging_Block_Ip extends Mage_Core_Block_Template
{
    const XML_PATH_ECHO_SERVER_IP = 'design/footer/show_server_ip_on_frontend';

    public function allowedToEcho()
    {
        $allowed = Mage::getConfig()->getStoresConfigByPath(self::XML_PATH_ECHO_SERVER_IP);
        $allowed = reset($allowed);
        return ($allowed == 1);
    }

    public function getServerIp($onlyDisplayFinalOctet = true)
    {
        $ip = $_SERVER['SERVER_ADDR'];
        $ipToDisplay = $ip;

        if ($onlyDisplayFinalOctet) {
            $octets = explode('.', $ip);
            $ipToDisplay = end($octets);
        }

        return $ipToDisplay;
    }

    public function getCacheKeyInfo() {
        $info = array(
            'DEBUGGING_IP',
            Mage::app()->getStore()->getCode(),
            $this->getTemplateFile(),
            'template' => $this->getTemplate()
        );
        return $info;
    }
}