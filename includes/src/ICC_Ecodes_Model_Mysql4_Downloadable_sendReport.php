<?php


echo 'created at: ' . date('H:i:s');
echo str_repeat('<br>', 4);
include( 'app/Mage.php');
Mage::app();




$down = Mage::getModel('ecodes/downloadable');

$down->remainingSerialsReport();


echo 'done sending';
