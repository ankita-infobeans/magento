<?php
$to = "m.gill@iccsafe.org";
$subject = "Test Mail";
$txt = "This is test mail from mail server by infobeans testing.";
$headers = "From:info@iccsafe.org" . "\r\n" .
"CC: anil.kasar@infobeans.com";
try{
echo mail($to,$subject,$txt,$headers);}
catch(Exception $e){
print_r($e);
}
?>
