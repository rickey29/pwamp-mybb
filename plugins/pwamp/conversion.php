<?php
if ( !defined('IN_MYBB') )
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

class PWAMPConversion
{
	public function __construct()
	{
	}

	public function __destruct()
	{
	}


	public function convert($page, $home_url, $data, $theme)
	{
		require_once MYBB_ROOT . 'inc/plugins/pwamp/transcoding.php';
		require_once MYBB_ROOT . 'inc/plugins/pwamp/themes.php';

		if ( file_exists(MYBB_ROOT . 'inc/plugins/pwamp-extension.php') )
		{
			require_once MYBB_ROOT . 'inc/plugins/pwamp-extension/extension.php';

			$transcoding = new PWAMPExtension();
		}
		else
		{
			$transcoding = new PWAMPThemes();
		}


		$transcoding->init($home_url, $data);


		$page = $transcoding->pretranscode_theme($page, $theme);

		if ( method_exists($transcoding, 'pretranscode_extension') )
		{
			$page = $transcoding->pretranscode_extension($page);
		}


		$page = $transcoding->transcode_html($page);


		$page = $transcoding->transcode_theme($page, $theme);

		if ( method_exists($transcoding, 'transcode_extension') )
		{
			$page = $transcoding->transcode_extension($page);
		}


		$page = $transcoding->transcode_head($page);


		$page = $transcoding->posttranscode_theme($page, $theme);

		if ( method_exists($transcoding, 'posttranscode_extension') )
		{
			$page = $transcoding->posttranscode_extension($page);
		}

		return $page;
	}
}
