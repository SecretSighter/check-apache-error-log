<?php
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.__DIR__);
require_once('Zend'.DIRECTORY_SEPARATOR.'Loader'.DIRECTORY_SEPARATOR.'Autoloader.php');
$loader = Zend_Loader_Autoloader::getInstance();

$serverhash = hash_file('sha256', '/var/log/apache2/error.log');
$localhash = hash_file('sha256', dirname(__FILE__).'/error.log');

if($serverhash != $localhash){
	$mail = new \Zend_Mail();
	$mail->setSubject('Added Error to Error Log');
	$mail->setFrom(php_uname('n'));
	$mail->addTo('douglas@americanhcl.com');
	copy('/var/log/apache2/error.log', dirname(__FILE__).'/error.log');
	exec('tail -100 ' . dirname(__FILE__).'/error.log > '.dirname(__FILE__).'/error.diff');
	$mail->createAttachment(file_get_contents(dirname(__FILE__).'/error.diff'), 'text/plain', Zend_Mime::DISPOSITION_INLINE, Zend_Mime::ENCODING_BASE64, 'Error.log');
	$mail->setBodyText('error.log');
	$mail->send(new \Zend_Mail_Transport_Smtp('localhost'));	
}

copy('/var/log/apache2/error.log', dirname(__FILE__).'/error.log');

