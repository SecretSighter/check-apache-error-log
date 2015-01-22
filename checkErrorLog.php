<?php
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.__DIR__);
require_once('Zend'.DIRECTORY_SEPARATOR.'Loader'.DIRECTORY_SEPARATOR.'Autoloader.php');
$loader = Zend_Loader_Autoloader::getInstance();

$rules = array(
	'grep -v 172.31.17.131',
	'grep -v 172.31.25.188',
	'grep -v notice',
	'grep -v Hostname'
);

$configEmail = 'douglas@americanhcl.com';
$configError = '/var/log/apache2/error.log';
$configLocalError = dirname(__FILE__).'/error.log';
$configErrorDiff = dirname(__FILE__).'/error.diff';
$hashAlgo = 'sha256';

$serverhash = hash_file($hashAlgo, $configError);
$localhash = hash_file($hashAlgo, $configLocalError);

if($serverhash != $localhash){
	$mail = new \Zend_Mail();
	$mail->setSubject('Added Error to Error Log');
	$mail->setFrom(php_uname('n'));
	$mail->addTo($configEmail);
	copy($configError, $configLocalError);

	$firstDiffHash = hash_file($hashAlgo, $configErrorDiff);

	$executedString = 'cat ' . $configLocalError;
	foreach($rules as $rule){
		$executedString .= ' | ' . $rule . '';
	}
	$executedString .= ' > ' . $configErrorDiff;

	exec($executedString);

	if(hash_file($hashAlgo, $configErrorDiff) != $firstDiffHash){
		$lines=array();
		$fp = fopen($configErrorDiff, "r");
		while(!feof($fp))
		{
			$line = fgets($fp, 4096);
			array_push($lines, $line);
			if (count($lines)>5)
				array_shift($lines);
		}
	fclose($fp);

	$mail->setBodyText(implode("\n", $lines));
		$mail->send(new \Zend_Mail_Transport_Smtp('localhost'));
	}
}
