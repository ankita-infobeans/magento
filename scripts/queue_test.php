<?php
    require_once '../app/Mage.php';
    umask(0);
    Mage::app('default');

    $id = '75944';

    $queue = Mage::getModel('gorilla_queue/queue');
    $queue->load($id, 'queue_id');

    try
    {
        $modelClass = (string) $queue->getModelClass();
        $method = (string) $queue->getMethod();
        $queueItemData = unserialize($queue->getQueueItemData());

        $function_args =  unserialize( $queue->getQueueItemData() );
        $xml_obj = simplexml_load_string($function_args['xml']);

        echo $queue->getQueueItemData();
        echo '<br>';
        print '<pre>';
        print_r($function_args);
        print '</pre>';

        echo '<br>';
        print '<pre>';
        print_r($xml_obj);
        print '</pre>';
    }
    catch(Exception $e)
    {
        echo 'ERROR: '.$e->getMessage();
        Mage::logException($e);
    }

?>
