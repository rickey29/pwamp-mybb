<?php
if ( !defined('IN_MYBB') )
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

class PWAMPThemes extends PWAMPTranscoding
{
	public function __construct()
	{
		parent::__construct();
	}

	public function __destruct()
	{
	}


	public function pretranscode_theme($page, $theme)
	{
		$page = preg_replace('/<a\b[^>]* href="javascript:void\(0\)"[^>]*\s*?>(.*)<\/a>/iU', '${1}', $page);
		$page = preg_replace('/<img\b([^>]*) src="((' . $this->home_url_pattern . '\/)?captcha\.php\?.*imagehash=\w{32})"([^>]*)\s*?\/?>/iU', '<img${1} src="${2}"${4} width="200" height="60" />', $page);

		return $page;
	}

	public function transcode_theme($page, $theme)
	{
		return $page;
	}

	public function posttranscode_theme($page, $theme)
	{
		return $page;
	}
}
