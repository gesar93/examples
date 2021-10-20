<?php
/**
 *  Дешифровка AES пароля
 */
function decrypt($aesKey, $password) {
    $method = "AES-256-CBC";
    $encryptBytes = base64_decode($password);
    $iv = substr($encryptBytes, 0, 16);
    $encryptedData = substr($encryptBytes, 16);
    
    return openssl_decrypt($encryptedData, $method, $aesKey, OPENSSL_RAW_DATA, $iv);
}