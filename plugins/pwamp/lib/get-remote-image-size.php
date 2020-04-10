<?php
function get_image_size($url)
{
	$headers = array('Range: bytes=0-65536');

	$curl = curl_init();

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($curl, CURLOPT_REFERER, $url);
	curl_setopt($curl, CURLOPT_TIMEOUT, 5);
	curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');

	$raw = curl_exec($curl);
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	curl_close($curl);

	if ( $status != 206 && $status != 200 )
	{
		return [0, 0];
	}


	$image = imagecreatefromstring($raw);

	$width = imagesx($image);
	$height = imagesy($image);

	imagedestroy($image);

	return [$width, $height];
}
