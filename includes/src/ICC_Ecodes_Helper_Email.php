<?php

class ICC_Ecodes_Helper_Email
{
    public function sendEmail($to_email, $message_content, $to_name = null,  $subject = null, $from = null)
    {
        if( trim($message_content) == '' )
        {
            throw new Exception('There must be content in this email message.');
            return false;
        }
        if( trim($to_email) == '' )
        {
            throw new Exception('An email address to send this message to was not set. Please set a "to" email address.');
            return false;
        }
        if( is_null($from) )
        {
            $from = 'no-reply@' . $_SERVER['SERVER_NAME'];
        }
        if( is_null($subject) )
        {
            $subject = 'News about your PremiumACCESS Subscription';
        }
        
        
        $email = Mage::getModel('core/email');
        $email->setBody($this->getMessage($message_content, $to_name));
        
        echo $this->getMessage($message_content, $to_name); die;
        
        $email->setSubject($subject);
        $email->setToEmail($to_email);
        $email->setFromName('Website Queue');            
        if( ! is_null($to_name))
        {
            $email->setToName($to_name);;
        }
        
        $email->setFromEmail($from);

        if (!is_null($format)) {
            $email->setType($format);
        }
        return $email->send();
    }
    
    public function getMessage($main_content, $name = '' )
    {
        $greeting = trim('Hi ' . $name);
        $message = "
$greeting,
This message is being sent to inform you that there is a queue item that has met its maximum number of failed attempts. This item will no longer be attempted to be processed automatically. Please login to the admin area of your site and manually process the item or reset it to be automatically processed.

$main_content
";
        return $message;
    }
    }