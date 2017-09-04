<?php


if(isset($_COOKIE['gatrack']))
	$uid = $_COOKIE['gatrack'];
else
{
	$uid = $_POST['uid'];
	setcookie('gatrack', $uid);
}

$url = 'www.google-analytics.com/collect';
$ch = curl_init(); 
$data = 'v=1&t=pageview&tid='.$_POST['trackid'].'&cid='.$uid.'&dp='.$_POST['page'];
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt( $ch, CURLOPT_HEADER, false );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_POST, true);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $data);
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
curl_exec($ch); 
curl_close($ch); 

