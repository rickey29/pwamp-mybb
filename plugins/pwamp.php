<?php
if ( !defined('IN_MYBB') )
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


function pwamp_info()
{
	return array(
		'name'          => 'PWA+AMP MyBB 1.8',
		'description'   => 'Converts MyBB 1.8 into Progressive Web Apps and Accelerated Mobile Pages styles.',
		'website'       => 'https://flexplat.com/pwamp-mybb18/',
		'author'        => 'Rickey Gu',
		'authorsite'    => 'https://flexplat.com',
		'version'       => '2.3.0',
		'guid'          => str_replace('.php', '', basename(__FILE__)),
		'codename'      => str_replace('.php', '', basename(__FILE__)),
		'compatibility' => '18*'
	);
}

function pwamp_is_installed()
{
	return TRUE;
}

function pwamp_activate()
{
}

function pwamp_deactivate()
{
}


class PWAMP
{
	private $home_url = '';
	private $home_url_pattern = '';
	private $theme = '';

	private $page_url = '';
	private $permalink = '';
	private $viewport_width = '';
	private $plugin_dir_url = '';

	private $page_base = '';

	private $amphtml = '';
	private $canonical = '';


	public function __construct()
	{
	}

	public function __destruct()
	{
	}


	private function init()
	{
		global $mybb;

		$this->home_url = $mybb->settings['bburl'];
		$this->home_url_pattern = preg_replace('/^https?:\/\//im', 'https?://', $this->home_url);
		$this->home_url_pattern = str_replace(array('/', '.'), array('\/', '\.'), $this->home_url_pattern);
		$this->theme = '';

		$this->page_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$this->page_url = preg_replace('/&__amp_source_origin=.+$/im', '', $this->page_url);
		$this->permalink = '';
		$this->viewport_width = !empty($mybb->cookies['pwamp_viewport_width']) ? $mybb->cookies['pwamp_viewport_width'] : '';
		$this->plugin_dir_url = $this->home_url . '/inc/plugins/';

		$this->page_base = basename($_SERVER['PHP_SELF']);
	}

	private function divert()
	{
		global $mybb;

		if ( preg_match('/^' . $this->home_url_pattern . '\/\??manifest\.webmanifest$/im', $this->page_url) )
		{
			header('Content-Type: application/x-web-app-manifest+json', true);
			echo '{
	"name": "' . $mybb->settings['bbname'] . '",
	"short_name": "' . $mybb->settings['bbname'] . '",
	"start_url": "' . $this->home_url . '",
	"icons": [{
		"src": "./inc/plugins/pwamp/mf/mf-logo-192.png",
		"sizes": "192x192",
		"type": "image/png"
	}, {
		"src": "./inc/plugins/pwamp/mf/mf-logo-512.png",
		"sizes": "512x512",
		"type": "image/png"
	}],
	"theme_color": "#ffffff",
	"background_color": "#ffffff",
	"display": "standalone"
}';

			exit();
		}
		elseif ( preg_match('/^' . $this->home_url_pattern . '\/\??pwamp-sw\.html$/im', $this->page_url) )
		{
			header('Content-Type: text/html; charset=utf-8', true);
			echo '<!doctype html>
<html>
<head>
<title>Installing service worker...</title>
<script type=\'text/javascript\'>
	var swsource = \'' . $this->home_url . '/' . ( empty($this->permalink) ? '?' : '' ) . 'pwamp-sw.js\';
	if ( \'serviceWorker\' in navigator ) {
		navigator.serviceWorker.register(swsource).then(function(reg) {
			console.log(\'ServiceWorker scope: \', reg.scope);
		}).catch(function(err) {
			console.log(\'ServiceWorker registration failed: \', err);
		});
	};
</script>
</head>
<body>
</body>
</html>';

			exit();
		}
		elseif ( preg_match('/^' . $this->home_url_pattern . '\/\??pwamp-sw\.js$/im', $this->page_url) )
		{
			header('Content-Type: application/javascript', true);
			echo 'importScripts(\'./inc/plugins/pwamp/sw/sw-toolbox.js\');
toolbox.router.default = toolbox.fastest;';

			exit();
		}
		elseif ( preg_match('/^' . $this->home_url_pattern . '\/\?pwamp-viewport-width=(\d+)$/im', $this->page_url, $match) )
		{
			$this->viewport_width = $match[1];

			my_setcookie('pwamp_viewport_width', $this->viewport_width);

			exit();
		}
	}


	private function get_amphtml()
	{
		$amphtml = htmlspecialchars_decode($this->page_url);
		$amphtml = preg_replace('/^(.*)(((\?)|(&(amp;)?))((amp)|(desktop))(=1)?)?(#[^#]*)?$/imU', '${1}${11}', $amphtml);
		$amphtml = preg_replace('/^(.*)(#[^#]*)?$/imU', '${1}' . ( ( strpos($amphtml, '?') !== false ) ? '&amp=1' : '?amp=1' ) . '${2}', $amphtml);
		$amphtml = htmlspecialchars($amphtml);

		return $amphtml;
	}

	private function get_canonical()
	{
		$canonical = htmlspecialchars_decode($this->page_url);
		$canonical = preg_replace('/^(.*)(((\?)|(&(amp;)?))((amp)|(desktop))(=1)?)?(#[^#]*)?$/imU', '${1}${11}', $canonical);
		$canonical = preg_replace('/^(.*)(#[^#]*)?$/imU', '${1}' . ( ( strpos($canonical, '?') !== false ) ? '&desktop=1' : '?desktop=1' ) . '${2}', $canonical);
		$canonical = htmlspecialchars($canonical);

		return $canonical;
	}


	public function add_amphtml()
	{
		global $headerinclude;

		$headerinclude = '<link rel="amphtml" href="' . $this->amphtml . '" />' . "\n" . $headerinclude;
	}

	public function add_notification_bar()
	{
		global $lang, $footer;

		$footer .= "\n" . '<script>
	var pwamp_notification_toggle = function() {
		var e = document.getElementById(\'pwamp-notification\');
		if ( e.style.display === \'flex\' || e.style.display === \'\' ) {
			e.style.display = \'none\';
		} else {
			e.style.display = \'flex\'
		}
	}
</script>
<div style="position:fixed!important;bottom:0;left:0;overflow:hidden!important;background:hsla(0,0%,100%,0.7);z-index:1000;width:100%">
	<div id="pwamp-notification" style="display:flex;align-items:center;justify-content:center">' . ( !empty($lang->pwamp_switch_to) ? $lang->pwamp_switch_to : 'Switch to' ) . '&nbsp;<a href="' . $this->amphtml . '">' . ( !empty($lang->pwamp_mobile_version) ? $lang->pwamp_mobile_version : 'mobile version' ) . '</a>&nbsp;&nbsp;<input type="button" value="' . ( !empty($lang->pwamp_continue) ? $lang->pwamp_continue : 'Continue' ) . '" style="min-width:80px" onclick="pwamp_notification_toggle();" /></div>
</div>';
	}


	private function get_page_type()
	{
		$type = preg_replace('/^.*\//im', '', $this->page_url);
		$type = preg_replace('/#.*$/im', '', $type);
		$type = preg_replace('/\?.*$/im', '', $type);
		$type = preg_replace('/\..*$/im', '', $type);

		if ( empty($type) )
		{
			$type = 'index';
		}

		return $type;
	}

	private function get_device()
	{
		$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$accept = !empty($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
		$profile = !empty($_SERVER['HTTP_PROFILE']) ? $_SERVER['HTTP_PROFILE'] : '';

		$detection = new PWAMPDetection();

		$device = $detection->get_device($user_agent, $accept, $profile);

		return $device;
	}

	private function transcode_page($page)
	{
		global $mybb, $lang;

		$page = preg_replace('/^[\s\t]*<style type="[^"]+" id="[^"]+"><\/style>$/im', '', $page);

		$language = array(
			'continue' => !empty($lang->pwamp_continue) ? $lang->pwamp_continue : 'Continue',
			'desktop_version' => !empty($lang->pwamp_desktop_version) ? $lang->pwamp_desktop_version : 'desktop version',
			'switch_to' =>  !empty($lang->pwamp_switch_to) ? $lang->pwamp_switch_to : 'Switch to'
		);

		$data = array(
			'page_url' => $this->page_url,
			'canonical' => $this->canonical,
			'permalink' => $this->permalink,
			'page_type' => $this->get_page_type(),
			'viewport_width' => $this->viewport_width,
			'plugin_dir_url' => $this->plugin_dir_url,
			'language' => $language
		);

		$conversion = new PWAMPConversion();

		$page = $conversion->convert($page, $this->home_url, $data, $this->theme);

		return $page;
	}


	private function json_redirect($redirection)
	{
		global $mybb;

		$redirection = preg_replace('/^' . $this->home_url_pattern . '\//im', '', $redirection);
		if ( !preg_match('/^https?:\/\//im', $redirection) )
		{
			$redirection = $this->home_url . '/' . $redirection;
		}

		$host_url = preg_replace('/\/$/im', '', $mybb->settings['homeurl']);

		header('Content-type: application/json');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Origin: *.ampproject.org');
		header('Access-Control-Expose-Headers: AMP-Redirect-To, AMP-Access-Control-Allow-Source-Origin');
		header('AMP-Access-Control-Allow-Source-Origin: ' . $host_url);
		header('AMP-Redirect-To: ' . $redirection);

		$output = [];
		echo json_encode($output);

		exit();
	}


	public function calendar_end()
	{
		global $mybb;

		if ( $mybb->request_method != 'post' )
		{
			return;
		}

		my_setcookie('pwamp_month', strval($mybb->input['month']), 5);
		my_setcookie('pwamp_year', strval($mybb->input['year']), 5);
		my_setcookie('pwamp_calendar', strval($mybb->input['calendar']), 5);
		my_setcookie('pwamp_action', $mybb->input['action'], 5);

		$this->json_redirect('calendar.php');
	}

	public function calendar_main_view()
	{
		global $mybb;

		if ( $mybb->request_method != 'get' )
		{
			return;
		}

		$mybb->input['month'] = (int)$mybb->cookies['pwamp_month'];
		$mybb->input['year'] = (int)$mybb->cookies['pwamp_year'];
		$mybb->input['calendar'] = (int)$mybb->cookies['pwamp_calendar'];
		$mybb->input['action'] = $mybb->cookies['pwamp_action'];
	}

	public function contact_end()
	{
		global $mybb, $errors, $contact_subject, $contact_message, $user_email;

		if ( $mybb->request_method == 'post' )
		{
			if ( !empty($errors) )
			{
				my_setcookie('pwamp_errors', json_encode($errors), 5);
			}

			my_setcookie('pwamp_contact_subject', $contact_subject, 5);
			my_setcookie('pwamp_contact_message', $contact_message, 5);
			my_setcookie('pwamp_user_email', $user_email, 5);

			$this->json_redirect('contact.php');
		}
		else
		{
			if ( !empty($mybb->cookies['pwamp_errors']) )
			{
				$errors = json_decode($mybb->cookies['pwamp_errors']);
			}

			$contact_subject = $mybb->cookies['pwamp_contact_subject'];
			$contact_message = $mybb->cookies['pwamp_contact_message'];
			$user_email = $mybb->cookies['pwamp_user_email'];
		}
	}

	public function error($error)
	{
		global $mybb;

		if ( $mybb->request_method != 'post' )
		{
			return $error;
		}

		if ( $this->page_base == 'member.php' )
		{
			if ( $mybb->input['action'] != 'do_login' && $mybb->input['action'] != 'do_lostpw' && $mybb->input['action'] != 'do_register' && $mybb->input['action'] != '' )
			{
				return $error;
			}

			my_setcookie('pwamp_error', $error, 5);

			$this->json_redirect('member.php');
		}
		elseif ( $this->page_base == 'misc.php' )
		{
			if ( $mybb->input['action'] != 'do_helpsearch' )
			{
				return $error;
			}

			my_setcookie('pwamp_error', $error, 5);

			$this->json_redirect('misc.php');
		}
		else
		{
			return $error;
		}
	}

	public function forumdisplay_start()
	{
		global $mybb;

		if ( !empty($mybb->input['pwverify']) || empty($mybb->cookies['pwamp_pwverify']) )
		{
			return;
		}

		$mybb->input['pwverify'] = $mybb->cookies['pwamp_pwverify'];
	}

	public function global_end()
	{
		global $mybb;

		if ( $mybb->request_method != 'get' )
		{
			return;
		}

		if ( $this->page_base == 'contact.php' )
		{
			if ( $mybb->input['action'] != '' )
			{
				return;
			}

			if ( !empty($mybb->cookies['pwamp_url']) )
			{
				$url = $mybb->cookies['pwamp_url'];
				$message = $mybb->cookies['pwamp_message'];
				$title = $mybb->cookies['pwamp_title'];
				$force_redirect = !empty($mybb->cookies['pwamp_force_redirect']) ? TRUE : FALSE;

				redirect($url, $message, $title, $force_redirect);
			}
		}
		elseif ( $this->page_base == 'member.php' )
		{
			if ( $mybb->input['action'] != '' )
			{
				return;
			}

			if ( !empty($mybb->cookies['pwamp_url']) )
			{
				$url = $mybb->cookies['pwamp_url'];
				$message = $mybb->cookies['pwamp_message'];
				$title = $mybb->cookies['pwamp_title'];
				$force_redirect = !empty($mybb->cookies['pwamp_force_redirect']) ? TRUE : FALSE;

				redirect($url, $message, $title, $force_redirect);
			}
			elseif ( !empty($mybb->cookies['pwamp_error']) )
			{
				$error = $mybb->cookies['pwamp_error'];

				error($error);
			}
		}
	}

	public function global_intermediate()
	{
		global $mybb, $theme;

		$this->theme = $theme['name'];

		if ( $mybb->request_method != 'get' )
		{
			return;
		}

		if ( $this->page_base == 'member.php' )
		{
			if ( $mybb->input['action'] != 'register' )
			{
				return;
			}

			$mybb->request_method = 'post';
		}
		elseif ( $this->page_base == 'misc.php' )
		{
			if ( $mybb->input['action'] != 'do_helpsearch' )
			{
				return;
			}

			$mybb->request_method = 'post';
		}
		elseif ( $this->page_base == 'newreply.php' )
		{
			if ( $mybb->input['action'] != '' )
			{
				return;
			}

			if ( empty($mybb->cookies['pwamp_post']) )
			{
				return;
			}

			$_POST = json_decode($mybb->cookies['pwamp_post'], TRUE);

			if ( !empty($_POST['previewpost']) )
			{
				$mybb->input['previewpost'] = $_POST['previewpost'];
			}

			$mybb->input['message'] = $_POST['message'];
			$mybb->input['subject'] = $_POST['subject'];
		}
		elseif ( $this->page_base == 'newthread.php' )
		{
			if ( $mybb->input['action'] != '' )
			{
				return;
			}

			if ( empty($mybb->cookies['pwamp_post']) )
			{
				return;
			}

			$_POST = json_decode($mybb->cookies['pwamp_post'], TRUE);

			if ( !empty($_POST['previewpost']) )
			{
				$mybb->input['previewpost'] = $_POST['previewpost'];
			}

			$mybb->input['message'] = $_POST['message'];
			$mybb->input['subject'] = $_POST['subject'];
		}
	}

	public function global_start()
	{
		global $mybb, $lang, $plugins;

		if ( defined('IN_ADMINCP') || defined('IN_ARCHIVE') )
		{
			return;
		}


		$this->init();

		$this->divert();


		if ( isset($mybb->input['amp']) || isset($mybb->input['desktop']) )
		{
			$device = !isset($mybb->input['desktop']) ? 'mobile' : 'desktop';
		}
		elseif ( !empty($mybb->cookies['pwamp_style']) )
		{
			$device = $mybb->cookies['pwamp_style'] != 'desktop' ? 'mobile' : 'desktop';
		}
		else
		{
			require_once MYBB_ROOT . 'inc/plugins/pwamp/detection.php';

			$device = $this->get_device();

			$device = ( $device != 'desktop' && $device != 'desktop-bot' ) ? 'mobile' : 'desktop';
		}

		my_setcookie('pwamp_style', $device);

		$lang->load('pwamp', FALSE, TRUE);


		if ( $device == 'desktop' )
		{
			$this->amphtml = $this->get_amphtml();

			$plugins->add_hook('global_end', array($this, 'add_amphtml'));
			$plugins->add_hook('global_end', array($this, 'add_notification_bar'));

			return;
		}


		$this->canonical = $this->get_canonical();


		if ( file_exists(MYBB_ROOT . 'inc/plugins/pwamp_online.php') )
		{
			require_once MYBB_ROOT . 'inc/plugins/pwamp_online/conversion.php';
		}
		else
		{
			require_once MYBB_ROOT . 'inc/plugins/pwamp/conversion.php';
		}


		$plugins->add_hook('calendar_end', array($this, 'calendar_end'));
		$plugins->add_hook('calendar_main_view', array($this, 'calendar_main_view'));

		$plugins->add_hook('contact_end', array($this, 'contact_end'));

		$plugins->add_hook('error', array($this, 'error'));

		$plugins->add_hook('forumdisplay_start', array($this, 'forumdisplay_start'));

		$plugins->add_hook('global_end', array($this, 'global_end'));
		$plugins->add_hook('global_intermediate', array($this, 'global_intermediate'));

		$plugins->add_hook('member_do_login_end', array($this, 'member_do_login_end'));

		$plugins->add_hook('member_login', array($this, 'member_login'));
		$plugins->add_hook('member_login_end', array($this, 'member_login_end'));

		$plugins->add_hook('member_lostpw', array($this, 'member_lostpw'));

		$plugins->add_hook('member_register_end', array($this, 'member_register_end'));
		$plugins->add_hook('member_register_start', array($this, 'member_register_start'));

		$plugins->add_hook('memberlist_end', array($this, 'memberlist_end'));
		$plugins->add_hook('memberlist_start', array($this, 'memberlist_start'));

		$plugins->add_hook('misc_do_helpsearch_end', array($this, 'misc_do_helpsearch_end'));
		$plugins->add_hook('misc_do_helpsearch_start', array($this, 'misc_do_helpsearch_start'));

		$plugins->add_hook('misc_syndication_end', array($this, 'misc_syndication_end'));
		$plugins->add_hook('misc_syndication_start', array($this, 'misc_syndication_start'));

		$plugins->add_hook('newreply_end', array($this, 'newreply_end'));
		$plugins->add_hook('newreply_start', array($this, 'newreply_start'));

		$plugins->add_hook('newthread_end', array($this, 'newthread_end'));
		$plugins->add_hook('newthread_start', array($this, 'newthread_start'));

		$plugins->add_hook('pre_output_page', array($this, 'pre_output_page'));

		$plugins->add_hook('redirect', array($this, 'redirect'));
	}

	public function member_do_login_end()
	{
		global $mybb, $validated;

		if ( !$validated || empty($mybb->cookies['pwamp_errors']) )
		{
			return;
		}

		my_setcookie('pwamp_errors', '', -1);
		my_setcookie('pwamp_do_captcha', '', -1);
	}

	public function member_login()
	{
		global $mybb, $validated, $errors, $do_captcha;

		if ( isset($validated) || empty($mybb->cookies['pwamp_errors']) )
		{
			return;
		}

		$errors = json_decode($mybb->cookies['pwamp_errors']);
		$do_captcha = !empty($mybb->cookies['pwamp_do_captcha']) ? TRUE : FALSE;
	}

	public function member_login_end()
	{
		global $validated, $errors, $do_captcha;

		if ( !isset($validated) || empty($errors) )
		{
			return;
		}

		my_setcookie('pwamp_errors', json_encode($errors), 5);

		if ( $do_captcha )
		{
			my_setcookie('pwamp_do_captcha', '1', 6);
		}

		$this->json_redirect('member.php?action=login');
	}

	public function member_lostpw()
	{
		global $mybb, $errors;

		if ( $mybb->request_method == 'post' )
		{
			if ( empty($errors) )
			{
				return;
			}

			my_setcookie('pwamp_errors', json_encode($errors), 5);

			$this->json_redirect('member.php?action=lostpw');
		}
		else
		{
			if ( empty($mybb->cookies['pwamp_errors']) )
			{
				return;
			}

			$errors = json_decode($mybb->cookies['pwamp_errors']);
		}
	}

	public function member_register_end()
	{
		global $errors, $regerrors;

		if ( empty($errors) )
		{
			return;
		}

		$regerrors = inline_error($errors);
	}

	public function member_register_start()
	{
		global $mybb, $errors;

		if ( empty($mybb->cookies['pwamp_register']) )
		{
			my_setcookie('pwamp_register', '1', 5);

			if ( !empty($errors) )
			{
				my_setcookie('pwamp_errors', json_encode($errors), 6);
			}

			$this->json_redirect('member.php?action=register&agree=1');
		}
		else
		{
			if ( empty($mybb->cookies['pwamp_errors']) )
			{
				return;
			}

			$errors = json_decode($mybb->cookies['pwamp_errors']);
		}
	}

	public function memberlist_end()
	{
		global $mybb;

		if ( $mybb->request_method != 'post' )
		{
			return;
		}

		my_setcookie('pwamp_username', $mybb->input['username'], 5);
		my_setcookie('pwamp_website', $mybb->input['website'], 5);
		my_setcookie('pwamp_order', $mybb->input['order'], 5);
		my_setcookie('pwamp_sort', $mybb->input['sort'], 5);
		my_setcookie('pwamp_submit', $mybb->input['submit'], 5);

		$this->json_redirect('memberlist.php');
	}

	public function memberlist_start()
	{
		global $mybb;

		if ( $mybb->request_method != 'get' )
		{
			return;
		}

		$mybb->input['username'] = $mybb->cookies['pwamp_username'];
		$mybb->input['website'] = $mybb->cookies['pwamp_website'];
		$mybb->input['order'] = $mybb->cookies['pwamp_order'];
		$mybb->input['sort'] = $mybb->cookies['pwamp_sort'];
		$mybb->input['submit'] = $mybb->cookies['pwamp_submit'];
	}

	public function misc_do_helpsearch_end()
	{
		global $mybb, $search_data;

		if ( $mybb->request_method != 'post' )
		{
			return;
		}

		my_setcookie('pwamp_keywords', $$search_data['keywords'], 5);
		my_setcookie('pwamp_name', strval($$search_data['name']), 5);
		my_setcookie('pwamp_document', strval($$search_data['document']), 5);

		$this->json_redirect('misc.php');
	}

	public function misc_do_helpsearch_start()
	{
		global $mybb;

		if ( $mybb->request_method != 'post' )
		{
			return;
		}

		$mybb->input['keywords'] = $mybb->cookies['pwamp_keywords'];
		$mybb->input['name'] = (int)$mybb->cookies['pwamp_name'];
		$mybb->input['document'] = (int)$mybb->cookies['pwamp_document'];
	}

	public function misc_syndication_end()
	{
		global $mybb, $fid, $version, $forums, $limit;

		if ( $mybb->request_method != 'post' )
		{
			return;
		}

		my_setcookie('pwamp_fid', strval($fid), 5);
		my_setcookie('pwamp_version', $version, 5);
		my_setcookie('pwamp_forums', json_encode($forums), 5);
		my_setcookie('pwamp_limit', strval($limit), 5);

		$this->json_redirect('misc.php?action=syndication');
	}

	public function misc_syndication_start()
	{
		global $mybb;

		if ( $mybb->request_method != 'get' )
		{
			return;
		}

		$mybb->input['fid'] = (int)$mybb->cookies['pwamp_fid'];
		$mybb->input['version'] = $mybb->cookies['pwamp_version'];
		$mybb->input['forums'] = json_decode($mybb->cookies['pwamp_forums']);
		$mybb->input['limit'] = (int)$mybb->cookies['pwamp_limit'];
	}

	public function newreply_end()
	{
		global $mybb, $post_errors, $message, $subject, $tid;

		if ( $mybb->request_method != 'post' )
		{
			return;
		}

		if ( empty($post_errors) && empty($mybb->input['previewpost']) )
		{
			return;
		}

		if ( !empty($post_errors) )
		{
			my_setcookie('pwamp_post_errors', json_encode($post_errors), 5);
		}

		my_setcookie('pwamp_post', json_encode($_POST), 6);

		$this->json_redirect('newreply.php?tid=' . $tid . '&processed=1');
	}

	public function newreply_start()
	{
		global $mybb, $post_errors, $reply_errors, $tid;

		if ( $mybb->request_method == 'post' )
		{
			if ( empty($post_errors) )
			{
				return;
			}

			my_setcookie('pwamp_post_errors', json_encode($post_errors), 5);

			$this->json_redirect('newreply.php?tid=' . $tid . '&amp;processed=1');
		}

		if ( !empty($post_errors) || empty($mybb->cookies['pwamp_post_errors']) )
		{
			return;
		}

		$post_errors = json_decode($mybb->cookies['pwamp_post_errors']);
		$reply_errors = inline_error($post_errors);
	}

	public function newthread_end()
	{
		global $mybb, $post_errors, $message, $subject, $fid;

		if ( $mybb->request_method != 'post' )
		{
			return;
		}

		if ( empty($post_errors) && empty($mybb->input['previewpost']) )
		{
			return;
		}

		if ( !empty($post_errors) )
		{
			my_setcookie('pwamp_post_errors', json_encode($post_errors), 5);
		}

		my_setcookie('pwamp_post', json_encode($_POST), 6);

		$this->json_redirect('newthread.php?fid=' . $fid . '&processed=1');
	}

	public function newthread_start()
	{
		global $mybb, $post_errors, $thread_errors;

		if ( $mybb->request_method != 'get' )
		{
			return;
		}

		if ( !empty($post_errors) || empty($mybb->cookies['pwamp_post_errors']) )
		{
			return;
		}

		$post_errors = json_decode($mybb->cookies['pwamp_post_errors']);
		$thread_errors = inline_error($post_errors);
	}

	public function pre_output_page($page)
	{
		global $mybb;

		if ( $mybb->request_method == 'post' )
		{
			if ( $this->page_base == 'forumdisplay.php' )
			{
				if ( !empty($mybb->input['pwverify']) )
				{
					my_setcookie('pwamp_pwverify', $mybb->input['pwverify'], 5);

					$this->json_redirect($this->page_url);
				}
			}
		}

		$page = $this->transcode_page($page);
		if ( empty($page) )
		{
			return;
		}

		return $page;
	}

	public function redirect($redirect_args)
	{
		global $mybb, $force_redirect;

		if ( $mybb->request_method != 'post' )
		{
			return $redirect_args;
		}

		if ( $this->page_base == 'contact.php' )
		{
			if ( $mybb->input['action'] != '' )
			{
				return $redirect_args;
			}

			my_setcookie('pwamp_url', $redirect_args['url'], 5);

			if ( !empty($redirect_args['message']) )
			{
				my_setcookie('pwamp_message', $redirect_args['message'], 6);
			}

			if ( !empty($redirect_args['title']) )
			{
				my_setcookie('pwamp_title', $redirect_args['title'], 6);
			}

			if ( !empty($force_redirect) )
			{
				my_setcookie('pwamp_force_redirect', '1', 6);
			}

			$this->json_redirect($redirect_args['url']);
		}
		elseif ( $this->page_base == 'member.php' )
		{
			if ( $mybb->input['action'] != 'do_login' && $mybb->input['action'] != 'do_lostpw' )
			{
				return $redirect_args;
			}

			my_setcookie('pwamp_url', $redirect_args['url'], 5);

			if ( !empty($redirect_args['message']) )
			{
				my_setcookie('pwamp_message', $redirect_args['message'], 6);
			}

			if ( !empty($redirect_args['title']) )
			{
				my_setcookie('pwamp_title', $redirect_args['title'], 6);
			}

			if ( !empty($force_redirect) )
			{
				my_setcookie('pwamp_force_redirect', '1', 6);
			}

			$this->json_redirect($redirect_args['url']);
		}
		elseif ( $this->page_base == 'newreply.php' )
		{
			if ( $mybb->input['action'] != 'do_newreply' )
			{
				return $redirect_args;
			}

			my_setcookie('pwamp_url', $redirect_args['url'], 5);

			if ( !empty($redirect_args['message']) )
			{
				my_setcookie('pwamp_message', $redirect_args['message'], 6);
			}

			if ( !empty($redirect_args['title']) )
			{
				my_setcookie('pwamp_title', $redirect_args['title'], 6);
			}

			if ( !empty($force_redirect) )
			{
				my_setcookie('pwamp_force_redirect', '1', 6);
			}

			$this->json_redirect($redirect_args['url']);
		}
		elseif ( $this->page_base == 'newthread.php' )
		{
			if ( $mybb->input['action'] != 'do_newthread' )
			{
				return $redirect_args;
			}

			my_setcookie('pwamp_url', $redirect_args['url'], 5);

			if ( !empty($redirect_args['message']) )
			{
				my_setcookie('pwamp_message', $redirect_args['message'], 6);
			}

			if ( !empty($redirect_args['title']) )
			{
				my_setcookie('pwamp_title', $redirect_args['title'], 6);
			}

			if ( !empty($force_redirect) )
			{
				my_setcookie('pwamp_force_redirect', '1', 6);
			}

			$this->json_redirect($redirect_args['url']);
		}
		else
		{
			return $redirect_args;
		}
	}
}


$pwamp = new PWAMP();

$plugins->add_hook('global_start', array($pwamp, 'global_start'));
