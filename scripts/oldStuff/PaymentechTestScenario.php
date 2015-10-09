<?php

require_once '../app/Mage.php';

$b = array( array("1", "VISA", "L6L2X9", "111", "30.00"), array("2", "VISA", "33333", "", "38.01"), array("7", "MC", "44444", "", "41.00"), array("8", "MC", "L6L2X9", "666", "11.02"), array("13", "AMEX", "L6L2X9", "111", "1055.00"), array("14", "AMEX", "66666", "2222", "75.00"), array("15", "DS", "77777", "", "10.00"), array("16", "DS", "L6L2X9", "444", "63.03"));

$d = array( array("1", "VISA", "12.00"), array("2", "MC", "11.00"), array("3", "AMEX", "1055.00"), array("4", "DS", "29.00"));

$ja = array( array("1", "VISA", array("n", "a", "c", "s", "z", "e")), array("1", "MC", array("n", "z")), array("1", "VISA", array("n", "a", "c", "s", "z")));

$je = array( array("1", "AMEX", "30.00", array("n", "a", "z", "p")), array("1", "MC", "40.00", array("n", "a", "z")));

$jg = array( array("1", "17305278", array()), array("2", "17305280", array("z")), );
$jh = array( array("1", "17305278", array()), array("2", "17305280", array("z")), );

$ji = array("17305278", "17305280");
umask(0);

Mage::app('default');

/*
 $gp = new Gorilla_Paymentech_Model_Profile_Soap ();

 // /////////////////////
 // // Profile get
 // //////////////////

 $req = new Gorilla_Paymentech_Model_Source_ProfileFetchRequestElement ();
 $req->customerRefNum = "17305278";
 $soap_env = new Gorilla_Paymentech_Model_Source_ProfileFetch ();
 $soap_env->profileFetchRequest = $req;

 echo "---------------- " . date ( 'F j, Y, g:i a \E\S\T', time () - 4 * (60 * 60) ) . " ----------------------------\n";

 echo ("--------------Request -----------------\n");
 echo (print_r ( $soap_env, true ));
 echo ("---------------------------------------\n");

 $response = $gp->doCall ( "ProfileFetch", $soap_env );

 echo ("--------------Response -----------------\n");
 echo (print_r ( $response, true ));
 echo ("-----------------------------------------\n");
 echo "\n\n\n\n\n\n\n";

 exit ();

 $req = new Gorilla_Paymentech_Model_Source_ProfileChangeRequestElement ();

 Mage::helper ( 'paymentech' )->Log ( print_r ( $data, true ) );

 $req->customerRefNum = "17305278";

 // $req->customerName = "Ryan Suess";
 // $req->avsName = "Ryan Suess";
 // $req->customerRefNum = "";
 // $req->customerAddress1 = "555 Mockingbird Ln";
 // $req->customerAddress2 = "";
 // $req->customerCity = "Chicago";
 // $req->customerState = "IL";
 // $req->customerZip ="60169";
 // $req->customerPhone = "5555555555";
 // $req->customerCountryCode ="US";
 // $req->customerProfileOrderOverideInd = "NO";
 // $req->customerProfileFromOrderInd = "A";
 // $req->orderDefaultDescription = "";
 // $req->customerAccountType = "CC";

 $req->ccAccountNum = "371449635398431"; // amex
 // $req->ccAccountNum =
 // "4788250000028291"; //visa
 // $req->ccAccountNum =
 // "6011000995500000"; //discover
 // $req->ccAccountNum =
 // "5454545454545454"; //MC

 $req->ccExp = "201212";
 // $req->status = "A";

 Mage::helper ( 'paymentech' )->Log ( print_r ( $req, true ) );

 $soap_env = new Gorilla_Paymentech_Model_Source_ProfileChange ();
 $soap_env->profileChangeRequest = $req;

 echo "---------------- " . date ( 'F j, Y, g:i a \E\S\T', time () - 4 * (60 * 60) ) . " ----------------------------\n";

 echo ("--------------Request -----------------\n");
 echo (print_r ( $soap_env, true ));
 echo ("---------------------------------------\n");

 $response = new Gorilla_Paymentech_Model_Source_ProfileResponseElement ( $gp->doCall ( "ProfileChange", $soap_env )->return );

 echo ("--------------Response -----------------\n");
 echo (print_r ( $response, true ));
 echo ("-----------------------------------------\n");
 echo "\n\n\n\n\n\n\n";

 exit ();

 */

$test = new test();
//$test->Run_CaptureAuth ( $b );

$test -> Run_Return($d);
//est->Run_Delete ( $ji);

exit();

class test {
    const VISA = "4788250000028291";
    // visa
    const DI = "6011000995500000";
    // discover
    const MC = "5454545454545454";
    // MC
    const AMEX = "371449635398431";
    // amex

    public $gp;

    public function __construct() {
        $this -> gp = new Gorilla_Paymentech_Model_Profile_Soap();
    }

    public function Run_CaptureAuth(array $a) {
        foreach ($a as $single) {
            $this -> captureauth($single);
        }

    }

    public function Run_CaptureAuthNewProfile(array $a) {
        foreach ($a as $single) {
            $this -> captureauthnewprofile($single);
        }

    }

    public function Run_CaptureAuthProfile(array $a) {
        foreach ($a as $single) {
            $this -> captureauthprofile($single);
        }

    }

    public function Run_CaptureAuthRefund(array $a) {
        foreach ($a as $single) {
            $this -> captureauthrefund($single);
        }

    }

    public function Run_Delete(array $a) {
        foreach ($a as $single) {
            $this -> delete($single);
        }

    }

    public function Run_Return(array $a) {
        foreach ($a as $single) {
            $this -> _return($single);
        }

    }

    public function Run_CreateProfile(array $a) {
        foreach ($a as $single) {
            $this -> createprofile($single);
        }
    }

    public function delete($a) {
        $req = new Gorilla_Paymentech_Model_Source_ProfileDeleteRequestElement();

        $req -> customerRefNum = $a;
        // mc
        // $req->customerRefNum = "17258948";//ax

        $soap_env = new Gorilla_Paymentech_Model_Source_ProfileDelete();
        $soap_env -> profileDeleteRequest = $req;

        echo "---------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

        echo("--------------Request -----------------\n");
        echo(print_r($soap_env, true));
        echo("---------------------------------------\n");

        $response = new Gorilla_Paymentech_Model_Source_ProfileResponseElement($this -> gp -> doCall("ProfileDelete", $soap_env) -> return);

        echo("--------------Response -----------------\n");
        echo(print_r($response, true));
        echo("-----------------------------------------\n");
        echo "\n\n\n\n\n\n\n";
    }

    public function createprofile($a) {

        $todo = $a[2];

        // print_r($todo);
        // exit;
        $req = new Gorilla_Paymentech_Model_Source_ProfileAddRequestElement();

        Mage::helper('paymentech') -> Log(print_r($data, true));

        // get billing address name

        $cart = Mage::getModel('checkout/cart') -> getQuote();

        $address = $cart -> getBillingAddress();

        if (in_array("n", $todo)) {
            $req -> customerName = "Ryan Suess";
            $req -> avsName = "Ryan Suess";
        }
        $req -> customerRefNum = "";

        if (in_array("a", $todo)) {
            $req -> customerAddress1 = "555 Mockingbird Ln";
            $req -> customerAddress2 = "";
        }
        if (in_array("c", $todo)) {
            $req -> customerCity = "Chicago";
        }
        if (in_array("s", $todo)) {
            $req -> customerState = "IL";
        }
        if (in_array("z", $todo)) {
            $req -> customerZip = "60169";
        }
        if (in_array("z", $todo)) {
            $req -> customerEmail = "rsuess@gorillagroup.com";
        }

        // $req->customerPhone = "5555555555";
        // $req->customerCountryCode ="US";
        $req -> customerProfileOrderOverideInd = "NO";
        $req -> customerProfileFromOrderInd = "A";
        $req -> orderDefaultDescription = "";
        $req -> customerAccountType = "CC";

        // $req->ccAccountNum = "371449635398431"; //amex
        // $req->ccAccountNum = "4788250000028291"; // visa
        // $req->ccAccountNum =
        // "6011000995500000"; //discover
        // $req->ccAccountNum =
        // "5454545454545454"; //MC

        $num = $this -> getCard($a[1]);

        $req -> ccAccountNum = $num;

        $req -> ccExp = "201212";
        $req -> status = "A";

        Mage::helper('paymentech') -> Log(print_r($req, true));

        $soap_env = new Gorilla_Paymentech_Model_Source_ProfileAdd();
        $soap_env -> profileAddRequest = $req;

        echo "---------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

        echo("--------------Request -----------------\n");
        echo(print_r($soap_env, true));
        echo("---------------------------------------\n");

        $response = new Gorilla_Paymentech_Model_Source_ProfileResponseElement($this -> gp -> doCall("ProfileAdd", $soap_env) -> return);

        echo("--------------Response -----------------\n");
        echo(print_r($response, true));
        echo("-----------------------------------------\n");
        echo "\n\n\n\n\n\n\n";

    }

    public function _return($a) {
        $req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement();

        $num = $this -> getCard($a[1]);

        $req -> ccAccountNum = $num;

        $req -> amount = $a[2] * 100;
        // $req->avsZip = $a [2];
        // $req->ccCardVerifyNum = $a [3];
        $req -> transType = "R";

        $req -> ccExp = "201212";

        $req -> customerRefNum = "";

        $req -> ccCardVerifyPresenceInd = "";
        // ccv has been entered

        // $req->avsAddress1 = "555 MockingBird Ln."; // / address and street

        // $req->avsCity = "Chicago";

        if (is_numeric($req -> avsZip)) {
            $req -> avsCountryCode = "US";
        } else {
            if ($req -> avsZip != "")
                $req -> avsCountryCode = "CA";
        }

        $req -> avsName = "Testy Testerson";
        $req -> customerName = "Testy Testerson";

        $req -> avsPhone = "5555555555";

        $req -> comments = "";

        $req -> orderID = $this -> generate(); ;

        echo "-	D-" . $a[0] . "		--------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

        $soap_env = new Gorilla_Paymentech_Model_Source_NewOrder();
        $soap_env -> newOrderRequest = $req;

        echo("--------------Request -----------------\n");
        echo(print_r($soap_env, true));
        echo("---------------------------------------\n");

        $_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement($this -> gp -> doCall("NewOrder", $soap_env) -> return);

        echo("--------------Response -----------------\n");
        echo(print_r($_response, true));
        echo("-----------------------------------------\n");
        echo "\n\n\n\n\n\n\n";
        // exit;
    }

    public function captureauthprofile($a) {

        $todo = $a[2];
        $req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement();

        //$num = $this->getCard ( $a [1] );
        // echo $num;
        // exit ();

        $req -> customerRefNum = $a[1];
        $req -> ccAccountNum = $num;

        $req -> amount = 50 * 100;

        // $req->ccCardVerifyNum = $a [3];
        $req -> transType = "AC";

        $req -> ccExp = "201212";

        if (in_array("z", $todo)) {

            $req -> avsZip = 60616;
        }

        $req -> comments = "";

        $req -> orderID = $this -> generate(); ;

        echo "-	JE-" . $a[0] . "		--------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

        $soap_env = new Gorilla_Paymentech_Model_Source_NewOrder();
        $soap_env -> newOrderRequest = $req;

        echo("--------------Request -----------------\n");
        echo(print_r($soap_env, true));
        echo("---------------------------------------\n");

        $_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement($this -> gp -> doCall("NewOrder", $soap_env) -> return);

        echo("--------------Response -----------------\n");
        echo(print_r($_response, true));
        echo("-----------------------------------------\n");
        echo "\n\n\n\n\n\n\n";
        // exit;
    }

    public function captureauthrefund($a) {

        $req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement();

        //$num = $this->getCard ( $a [1] );
        // echo $num;
        // exit ();

        $req -> customerRefNum = $a[1];
        $req -> ccAccountNum = $num;

        $req -> amount = 50 * 100;

        // $req->ccCardVerifyNum = $a [3];
        $req -> transType = "R";

        $req -> ccExp = "201212";

        $req -> comments = "";

        $req -> orderID = $this -> generate(); ;

        echo "-	JE-" . $a[0] . "		--------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

        $soap_env = new Gorilla_Paymentech_Model_Source_NewOrder();
        $soap_env -> newOrderRequest = $req;

        echo("--------------Request -----------------\n");
        echo(print_r($soap_env, true));
        echo("---------------------------------------\n");

        $_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement($this -> gp -> doCall("NewOrder", $soap_env) -> return);

        echo("--------------Response -----------------\n");
        echo(print_r($_response, true));
        echo("-----------------------------------------\n");
        echo "\n\n\n\n\n\n\n";
        // exit;
    }

    public function captureauthnewprofile($a) {

        $todo = $a[3];
        $req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement();

        $num = $this -> getCard($a[1]);
        // echo $num;
        // exit ();

        $req -> ccAccountNum = $num;

        $req -> amount = $a[2] * 100;

        // $req->ccCardVerifyNum = $a [3];
        $req -> transType = "AC";

        $req -> ccExp = "201212";

        if (in_array("z", $todo)) {

            $req -> avsZip = 60616;
        }
        $req -> customerRefNum = "";

        $req -> addProfileFromOrder = "A";
        $req -> profileOrderOverideInd = "NO";

        if ($a[1] == "VISA" || $a[1] == "DS") {
            if ($req -> ccCardVerifyNum != "" && is_numeric($req -> ccCardVerifyNum)) {
                $req -> ccCardVerifyPresenceInd = 1;
                // ccv has been entered
            } else {
                $req -> ccCardVerifyPresenceInd = "";
                // ccv has been entered
            }
        } else {
            $req -> ccCardVerifyPresenceInd = "";
            // ccv has been entered
        }

        if (in_array("a", $todo)) {
            $req -> avsAddress1 = "555 MockingBird Ln.";
            // / address and street
        }
        if (in_array("c", $todo)) {
            $req -> avsCity = "Chicago";
        }

        if (in_array("z", $todo)) {
            if (is_numeric($req -> avsZip)) {
                $req -> avsCountryCode = "US";
            } else {
                if ($req -> avsZip != "")
                    $req -> avsCountryCode = "CA";
            }
        }

        if (in_array("n", $todo)) {
            $req -> avsName = "Testy Testerson";
            $req -> customerName = "Testy Testerson";
        }

        if (in_array("p", $todo)) {
            $req -> avsPhone = "5555555555";
        }
        $req -> comments = "";

        $req -> orderID = $this -> generate(); ;

        echo "-	JE-" . $a[0] . "		--------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

        $soap_env = new Gorilla_Paymentech_Model_Source_NewOrder();
        $soap_env -> newOrderRequest = $req;

        echo("--------------Request -----------------\n");
        echo(print_r($soap_env, true));
        echo("---------------------------------------\n");

        $_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement($this -> gp -> doCall("NewOrder", $soap_env) -> return);

        echo("--------------Response -----------------\n");
        echo(print_r($_response, true));
        echo("-----------------------------------------\n");
        echo "\n\n\n\n\n\n\n";
        // exit;
    }

    public function captureauth($a) {

        $req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement();

        $num = $this -> getCard($a[1]);
        // echo $num;
        // exit ();

        $req -> ccAccountNum = $num;

        $req -> amount = $a[4] * 100;
        $req -> avsZip = $a[2];
        $req -> ccCardVerifyNum = $a[3];
        $req -> transType = "AC";

        $req -> ccExp = "201212";

        $req -> customerRefNum = "";

        if ($a[1] == "VISA" || $a[1] == "DS") {
            if ($req -> ccCardVerifyNum != "" && is_numeric($req -> ccCardVerifyNum)) {
                $req -> ccCardVerifyPresenceInd = 1;
                // ccv has been entered
            } else {
                $req -> ccCardVerifyPresenceInd = "";
                // ccv has been entered
            }
        } else {
            $req -> ccCardVerifyPresenceInd = "";
            // ccv has been entered
        }

        $req -> avsAddress1 = "555 MockingBird Ln.";
        // / address and street

        $req -> avsCity = "Chicago";

        if (is_numeric($req -> avsZip)) {
            $req -> avsCountryCode = "US";
        } else {
            if ($req -> avsZip != "")
                $req -> avsCountryCode = "CA";
        }

        $req -> avsName = "Testy Testerson";
        $req -> customerName = "Testy Testerson";

        $req -> avsPhone = "5555555555";

        $req -> comments = "";

        $req -> orderID = $this -> generate(); ;

        echo "-	A-" . $a[0] . "		--------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

        $soap_env = new Gorilla_Paymentech_Model_Source_NewOrder();
        $soap_env -> newOrderRequest = $req;

        echo("--------------Request -----------------\n");
        echo(print_r($soap_env, true));
        echo("---------------------------------------\n");

        $_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement($this -> gp -> doCall("NewOrder", $soap_env) -> return);

        echo("--------------Response -----------------\n");
        echo(print_r($_response, true));
        echo("-----------------------------------------\n");
        echo "\n\n\n\n\n\n\n";
        // exit;
    }

    public function getCard($cardtype) {
        // echo $cardtype;
        // exit;

        if ($cardtype == "VISA") {
            echo "visa";
            return "4788250000028291";
        }
        if ($cardtype == "MC") {
            return "5454545454545454";
        }
        if ($cardtype == "AMEX") {
            return "371449635398431";
        }
        if ($cardtype == "DS") {
            return "6011000995500000";
        }
    }

    function generate($length = 8) {

        $password = "";

        $possible = "1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM";

        $maxlength = strlen($possible);

        if ($length > $maxlength) {
            $length = $maxlength;
        }

        $i = 0;

        while ($i < $length) {
            $char = substr($possible, mt_rand(0, $maxlength - 1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }

        // done!
        return $password;
    }

}

exit();
// ////////////////
// /DELETE profile
// //////////////////

$req = new Gorilla_Paymentech_Model_Source_ProfileDeleteRequestElement();

$req -> customerRefNum = "17258446";
// mc
// $req->customerRefNum = "17258948";//ax

$soap_env = new Gorilla_Paymentech_Model_Source_ProfileDelete();
$soap_env -> profileDeleteRequest = $req;

echo "---------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

echo("--------------Request -----------------\n");
echo(print_r($soap_env, true));
echo("---------------------------------------\n");

$response = new Gorilla_Paymentech_Model_Source_ProfileResponseElement($gp -> doCall("ProfileDelete", $soap_env) -> return);

echo("--------------Response -----------------\n");
echo(print_r($response, true));
echo("-----------------------------------------\n");
echo "\n\n\n\n\n\n\n";

exit();

// /////////////////////////////////////
// // New Order with profile Creations//
// /////////////////////////////////////

$req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement();

// $req->ccAccountNum = "4788250000028291"; //visa
// $req->ccAccountNum = "6011000995500000"; //discover
// $req->ccAccountNum = "5454545454545454"; //MC
// $req->ccAccountNum = "371449635398431"; //amex

$req -> customerRefNum = "17258446";
// mc
// $req->customerRefNum = "17258948";//ax

$req -> amount = 15 * 100;
// $req->avsZip = "L6L2X9";
// $req->avsZip = "77777";

// $req->ccCardVerifyNum = "4444";

//

// $req->transType = "AC";

$req -> transType = "R";

// $req->ccExp = "201212";
// $req->avsState = "IL";

// $req->customerRefNum = "";

// if ($req->ccCardVerifyNum != "" && is_numeric($req->ccCardVerifyNum))
// {
// $req->ccCardVerifyPresenceInd = 1; // ccv has been entered
// }else{
$req -> ccCardVerifyPresenceInd = "";
// ccv has been entered
// }

// $req->avsAddress1 = "555 MockingBird Ln."; // / address and street

// $req->avsCity = "Chicago";

// if(is_numeric($req->avsZip))
// {
// $req->avsCountryCode = "US";
// }else{
// if($req->avsZip != "")
// $req->avsCountryCode = "CA";
// }

// $req->avsName = "Testy Testerson";
// $req->customerName = "Testy Testerson";

// $req->avsPhone = "5555555555";

// $req->comments = "";

$req -> orderID = 123456;

// $req->addProfileFromOrder = "A";
// /$req->profileOrderOverideInd = "NO";

echo "---------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

$soap_env = new Gorilla_Paymentech_Model_Source_NewOrder();
$soap_env -> newOrderRequest = $req;

echo("--------------Request -----------------\n");
echo(print_r($soap_env, true));
echo("---------------------------------------\n");

$_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement($gp -> doCall("NewOrder", $soap_env) -> return);

echo("--------------Response -----------------\n");
echo(print_r($_response, true));
echo("-----------------------------------------\n");
echo "\n\n\n\n\n\n\n";

exit();

// /////////////////////
// // Profile get
// //////////////////

$req = new Gorilla_Paymentech_Model_Source_ProfileFetchRequestElement();
$req -> customerRefNum = "17257874";
$soap_env = new Gorilla_Paymentech_Model_Source_ProfileFetch();
$soap_env -> profileFetchRequest = $req;

echo "---------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

echo("--------------Request -----------------\n");
echo(print_r($soap_env, true));
echo("---------------------------------------\n");

$response = $gp -> doCall("ProfileFetch", $soap_env);

echo("--------------Response -----------------\n");
echo(print_r($response, true));
echo("-----------------------------------------\n");
echo "\n\n\n\n\n\n\n";

exit();

// //////////////////////////
// ////// Profile Change
// //////////////////////////

$req = new Gorilla_Paymentech_Model_Source_ProfileChangeRequestElement();

Mage::helper('paymentech') -> Log(print_r($data, true));

$req -> customerRefNum = "17257890";

// $req->customerName = "Ryan Suess";
// $req->avsName = "Ryan Suess";
// $req->customerRefNum = "";
$req -> customerAddress1 = "555 Mockingbird Ln";
// $req->customerAddress2 = "";
// $req->customerCity = "Chicago";
// $req->customerState = "IL";
// $req->customerZip ="60169";
$req -> customerPhone = "5555555555";
// $req->customerCountryCode ="US";
// $req->customerProfileOrderOverideInd = "NO";
// $req->customerProfileFromOrderInd = "A";
// $req->orderDefaultDescription = "";
// $req->customerAccountType = "CC";

// $req->ccAccountNum = "371449635398431"; //amex
// $req->ccAccountNum = "4788250000028291"; //visa
// $req->ccAccountNum = "6011000995500000"; //discover
// $req->ccAccountNum = "5454545454545454"; //MC

// $req->ccExp = "201212";
// $req->status = "A";

Mage::helper('paymentech') -> Log(print_r($req, true));

$soap_env = new Gorilla_Paymentech_Model_Source_ProfileChange();
$soap_env -> profileChangeRequest = $req;

echo "---------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

echo("--------------Request -----------------\n");
echo(print_r($soap_env, true));
echo("---------------------------------------\n");

$response = new Gorilla_Paymentech_Model_Source_ProfileResponseElement($gp -> doCall("ProfileChange", $soap_env) -> return);

echo("--------------Response -----------------\n");
echo(print_r($response, true));
echo("-----------------------------------------\n");
echo "\n\n\n\n\n\n\n";

exit();

// ////////////////////
// / Profile Creation
// /////////////////

$req = new Gorilla_Paymentech_Model_Source_ProfileAddRequestElement();

Mage::helper('paymentech') -> Log(print_r($data, true));

// get billing address name

$cart = Mage::getModel('checkout/cart') -> getQuote();

$address = $cart -> getBillingAddress();

$req -> customerName = "Ryan Suess";
$req -> avsName = "Ryan Suess";
$req -> customerRefNum = "";
$req -> customerAddress1 = "555 Mockingbird Ln";
$req -> customerAddress2 = "";
$req -> customerCity = "Chicago";
$req -> customerState = "IL";
$req -> customerZip = "60169";
// $req->customerPhone = "5555555555";
// $req->customerCountryCode ="US";
$req -> customerProfileOrderOverideInd = "NO";
$req -> customerProfileFromOrderInd = "A";
$req -> orderDefaultDescription = "";
$req -> customerAccountType = "CC";

// $req->ccAccountNum = "371449635398431"; //amex
$req -> ccAccountNum = "4788250000028291";
// visa
// $req->ccAccountNum =
// "6011000995500000"; //discover
// $req->ccAccountNum =
// "5454545454545454"; //MC

$req -> ccExp = "201212";
$req -> status = "A";

Mage::helper('paymentech') -> Log(print_r($req, true));

$soap_env = new Gorilla_Paymentech_Model_Source_ProfileAdd();
$soap_env -> profileAddRequest = $req;

echo "---------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

echo("--------------Request -----------------\n");
echo(print_r($soap_env, true));
echo("---------------------------------------\n");

$response = new Gorilla_Paymentech_Model_Source_ProfileResponseElement($gp -> doCall("ProfileAdd", $soap_env) -> return);

echo("--------------Response -----------------\n");
echo(print_r($response, true));
echo("-----------------------------------------\n");
echo "\n\n\n\n\n\n\n";

exit();

// /////////////////////
// // REFUND////////////
// /////////////////////

$data = new Gorilla_Paymentech_Model_Source_ReversalElement();

$data -> txRefNum = "4F7069F35B4910A4AB6FAFDC180D8133D2AD54C4";
$data -> reversalRetryNumber = null;
$data -> adjustedAmount = $amount * 100;
// need to figure out why
// paymentech does not like
// decimals
$data -> onlineReversalInd = "Y";

$soap_env = new Gorilla_Paymentech_Model_Source_Reversal();
$soap_env -> reversalRequest = $data;

Mage::helper('paymentech') -> Log("------------Request ---------------");
Mage::helper('paymentech') -> Log(print_r($soap_env, true));
Mage::helper('paymentech') -> Log("----------------------------------");

$this -> _response = new Gorilla_Paymentech_Model_Source_ReversalResponseElement($gp -> doCall(self::TRANS_REVERSAL, $soap_env) -> return);

exit();

// ///////////
// Create New
// //////////////

// exit;

$req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement();

$req -> ccAccountNum = "4788250000028291";
// visa
$req -> ccAccountNum = "6011000995500000";
// discover
// $req->ccAccountNum =
// "5454545454545454"; //MC
// $req->ccAccountNum =
// "371449635398431"; //amex

$req -> amount = 10 * 100;
// $req->avsZip = "L6L2X9";
$req -> avsZip = "77777";

// $req->ccCardVerifyNum = "444";

//

// $req->transType = "AC";

$req -> transType = "R";

// $req->ccExp = "201212";
// $req->avsState = "IL";

$req -> customerRefNum = "";

if ($req -> ccCardVerifyNum != "" && is_numeric($req -> ccCardVerifyNum)) {
    $req -> ccCardVerifyPresenceInd = 1;
    // ccv has been entered
} else {
    $req -> ccCardVerifyPresenceInd = "";
    // ccv has been entered
}

$req -> avsAddress1 = "555 MockingBird Ln.";
// / address and street

$req -> avsCity = "Chicago";

if (is_numeric($req -> avsZip)) {
    $req -> avsCountryCode = "US";
} else {
    if ($req -> avsZip != "")
        $req -> avsCountryCode = "CA";
}

$req -> avsName = "Testy Testerson";
$req -> customerName = "Testy Testerson";

$req -> avsPhone = "5555555555";

$req -> comments = "";

$req -> orderID = 123456;

// switch ($type) {
// case "capture" :

// break;
// case "auth" :
// $req->transType = "A";
// break;
// case "refund" :
// $req->transType = "R";
// break;
// }

echo "---------------- " . date('F j, Y, g:i a \E\S\T', time() - 4 * (60 * 60)) . " ----------------------------\n";

$soap_env = new Gorilla_Paymentech_Model_Source_NewOrder();
$soap_env -> newOrderRequest = $req;

echo("--------------Request -----------------\n");
echo(print_r($soap_env, true));
echo("---------------------------------------\n");

$_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement($gp -> doCall("NewOrder", $soap_env) -> return);

echo("--------------Response -----------------\n");
echo(print_r($_response, true));
echo("-----------------------------------------\n");
echo "\n\n\n\n\n\n\n";

// exit;
//$data = ( array ) $_response;

//echo ( "The approvalStatus is " . $_response->approvalStatus );
