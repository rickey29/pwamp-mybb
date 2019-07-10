<?php
if ( !defined('IN_MYBB') )
{
	exit;
}

class M_Detection
{
	private $device_list = array(
		// Apple iOS
		'iPad' => 'tablet',
		'iPhone' => 'smartphone',
		'iPod' => 'smartphone',

		// Kindle Fire
		'Kindle Fire' => 'tablet',
		'Kindle/' => 'tablet',
		'KFAPWI' => 'tablet',

		// Nexus
		'Nexus 4' => 'smartphone',
		'Nexus 5' => 'smartphone',
		'Nexus 7' => 'tablet',
		'Nexus 10' => 'tablet',

		// Android
		'Android*Mobile' => 'smartphone',
		'Android' => 'tablet',

		// Chrome
		'Chrome/' => 'desktop',

		// Macintosh
		'Macintosh' => 'desktop',

		// Firefox
		'Firefox/' => 'desktop',

		// Windows Phone
		'Windows Phone' => 'smartphone',

		// Windows Mobile
		'Windows CE' => 'feature-phone',

		// Internet Explorer
		'MSIE ' => 'desktop',
		'Windows NT' => 'desktop',

		// Opera Mobile
		'Opera Mobi*Version/' => 'smartphone',

		// Opera Mini
		'Opera Mini/' => 'smartphone',

		// Opera
		'Opera*Version/' => 'desktop',

		// Palm WebOS
		'webOS/*AppleWebKit' => 'smartphone',
		'TouchPad/' => 'tablet',

		// Meego
		'MeeGo' => 'smartphone',

		// BlackBerry
		'BlackBerry*AppleWebKit*Version/' => 'smartphone',
		'BB*AppleWebKit*Version' => 'smartphone',
		'PlayBook*AppleWebKit' => 'tablet',
		'BlackBerry*/*MIDP' => 'feature-phone',

		// Safari
		'Safari' => 'desktop',

		// Nokia Symbian
		'Symbian/' => 'smartphone',

		// Google
		'googlebot-mobile' => 'mobile-bot',
		'googlebot' => 'bot',

		// Microsoft
		'bingbot' => 'bot',

		// Yahoo!
		'Yahoo! Slurp' => 'bot'
	);

	private $accept_list = array(
		// application/vnd.wap.xhtml+xml
		'application/vnd.wap.xhtml+xml' => 'feature-phone'
	);


	public function __construct()
	{
	}

	public function __destruct()
	{
	}


	public function get_device($user_agent, $accept, $profile)
	{
		if ( !empty($user_agent) )
		{
			foreach ( $this->device_list as $key => $value )
			{
				if ( preg_match('#' . str_replace('\*', '.*?', preg_quote($key, '#')) . '#i', $user_agent) )
				{
					return $value;
				}
			}
		}

		if ( !empty($accept) )
		{
			foreach ( $this->accept_list as $key => $value )
			{
				if ( preg_match('#' . str_replace('\*', '.*?', preg_quote($key, '#')) . '#i', $accept) )
				{
					return $value;
				}
			}
		}

		if ( !empty($profile) )
		{
			return 'feature-phone';
		}

		if ( !empty($user_agent) )
		{
			return 'feature-phone';
		}

		return 'mobile';
	}
}
