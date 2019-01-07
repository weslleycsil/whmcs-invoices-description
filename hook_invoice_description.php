<?php
/**
 * Hook Function for Change Item Invoice Description
 *
 *
 * @package    TWCreativs
 * @author     Weslley Silva
 * @version    $Id$
 * @link       http://twcreativs.com.br/
 */

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");
/**
 * 
 */
function updateInvoice($arr1,$arr2,$idInvoice){
    
    $command = 'UpdateInvoice';
    $postData = array(
        'invoiceid' => $idInvoice,
        'status' => 'Unpaid',
        'itemdescription' => $arr1,
        'itemamount' => $arr2
    );
    $adminUsername = 'weslleycsil'; // Optional for WHMCS 7.2 and later

    $results = localAPI($command, $postData, $adminUsername);
    if ($results['result'] == 'success') {
        logActivity("Log Hook Invoice Description - Invoice ".$idInvoice." Updated!");
    } else {
        logActivity("Log Hook Invoice Description - An Error Occurred: " . $results['result']);
    }

}

/**
 * Function getInvoice
 * @param string $id ID of invoice
 * @param string $user User of invoice
 * 
 * @return array
 */
function getInvoice($id, $user){
    // local api whmcs
    $command = 'GetInvoice';
    $postData = array(
        'invoiceid' => $id,
    );
    $adminUsername = 'weslleycsil'; // Optional for WHMCS 7.2 and later

    $results = localAPI($command, $postData, $adminUsername);
    if ($results['result'] == 'success') {
        if($results['userid'] == $user){
            return $results['items']['item'];
        }
    } else {
        logActivity("Log Hook Invoice Description - An Error Occurred: " . $results['result']);
    }

    
} // ok

/** 
 * Function getClientsProducts
 * @param int $id ID of User
 * 
 * @return array of products
 */

function getClientsProducts($id){

    $command = 'GetClientsProducts';
    $postData = array(
        'clientid' => $id,
    );
    $adminUsername = 'weslleycsil'; // Optional for WHMCS 7.2 and later

    $results = localAPI($command, $postData, $adminUsername);
    if ($results['result'] != 'success') {
        logActivity("Log Hook Invoice Description - An Error Occurred: " . $results['result']);
        return 0;
    }
    return $results['products']['product'];
} // ok
/**
 *  Function getNameItem
 */

function getNameItem($description){
    $var = explode(" (", $description);
    if(strpos( $var[0],"-") != false){
        $var = explode(" -", $var[0]);
    }
    return $var[0];
} // ok

/**
 *  Function getDescription
 * @param int $pid PID of Product
 * 
 * @return array of product
 */

function getDescription($pid){

    $command = 'GetProducts';
    $postData = array(
        'pid' => $pid,
    );
    $adminUsername = 'weslleycsil'; // Optional for WHMCS 7.2 and later

    $results = localAPI($command, $postData, $adminUsername);
    if ($results['result'] != 'success') {
        logActivity("Log Hook Invoice Description - An Error Occurred: " . $results['result']);
        return 0;
    }

    $result = $results['products']['product'];
    $description;
    foreach ($result as $productItem) {
        $description = $productItem['description'];
    }
    return $description;

} // ok

/**
 *  Function getInfoItem
 */

function getInfoItem($item,$products){

    $nameItem = getNameItem($item['description']);
    $relid = $item['relid'];
    $description = "";
    foreach ($products as $produto) {
        if($produto['id'] == $relid && $produto['name'] == $nameItem){
            $idProduto = $produto['pid'];
            $description = getDescription($idProduto);
        }
    }
    return $description;
} // ok

/** function updateDescription
 * 
 */

function updateDescription($newDesc,$oldDesc){
    $var = explode(")", $oldDesc);
    $result = $var[0].") \n--- Base ---\n".$newDesc."\n--- ---".$var[1];
    return $result;
} // ok
/**
 * Register hook function call.
 *
 * @param string $hookPoint The hook point to call
 * @param integer $priority The priority for the given hook function
 * @param string|function Function name to call or anonymous function.
 *
 * @return 
 */
add_hook('InvoiceCreated', 1, function($vars) {

    /** 
     * @var source	string
     * @var user	string	int
     * @var invoiceid	int	The invoice ID that was created
     * @var status	string	The status of the new invoice
     * */ 	


    $user = $vars['user'];
    $idInvoice = $vars['invoiceid'];
    $statusInvoice = $vars['status'];
    
    //Called hook Log
    logActivity("Log Hook Invoice Description - Invoice Created Number: ".$idInvoice.". Call Function to modify description item on invoice"); 

    if($statusInvoice == "Unpaid"){
        // call function to get invoice
       $itens = getInvoice($idInvoice,$user);
       $products = getClientsProducts($user);
       $newItens = array();
       $newAmounts = array();
       foreach ($itens as $item) {
           $newDesc = getInfoItem($item,$products);
           $newItens[$item['id']] = updateDescription($newDesc,$item['description']);
           $newAmounts[$item['id']] = $item['amount'];
       }
       updateInvoice($newItens,$newAmounts,$idInvoice);
    }
    
});