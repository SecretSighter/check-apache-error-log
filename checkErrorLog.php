<?php
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.__DIR__);
require_once('Zend'.DIRECTORY_SEPARATOR.'Loader'.DIRECTORY_SEPARATOR.'Autoloader.php');
$loader = Zend_Loader_Autoloader::getInstance();

$serverhash = hash_file('sha256', '/var/log/apache2/error.log');
$localhash = hash_file('sha256', dirname(__FILE__).'/error.log');

$rules = array(
	'grep -v 172.31.17.131',
	'grep -v 172.31.25.188',
	'grep -v notice',
	'grep -v Hostname'
);

if($serverhash != $localhash){
	$mail = new \Zend_Mail();
	$mail->setSubject('Added Error to Error Log');
	$mail->setFrom(php_uname('n'));
	$mail->addTo('douglas@americanhcl.com');
	copy('/var/log/apache2/error.log', dirname(__FILE__).'/error.log');

	$firstDiffHash = hash_file('sha256', dirname(__FILE__) . '/error.diff');

	$executedString = 'cat ' . dirname(__FILE__) . '/error.log';
	foreach($rules as $rule){
		$executedString .= ' | ' . $rule . '';
	}
	$executedString .= ' > ' . dirname(__FILE__) . '/error.diff';

//	echo $executedString . "\n";
//	echo $firstDiffHash . "\n";
	exec($executedString);

//	echo hash_file('sha256', dirname(__FILE__).'/error.diff');
	
	if(hash_file('sha256', dirname(__FILE__).'/error.diff') != $firstDiffHash){
	//	$mail->createAttachment(file_get_contents(dirname(__FILE__).'/error.diff'), 'text/plain', Zend_Mime::DISPOSITION_INLINE, Zend_Mime::ENCODING_BASE64, 'Error.log');

	$lines=array();
	$fp = fopen(dirname(__FILE__).'/error.diff', "r");
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
