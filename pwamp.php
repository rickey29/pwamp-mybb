<?php
if ( !defined('IN_MYBB') )
{
	exit;
}

require_once MYBB_ROOT . 'inc/plugins/pwamp/lib/detection.php';
require_once MYBB_ROOT . 'inc/plugins/pwamp/theme/transcoding.php';


function pwamp_info()
{
	return array(
		'name'          => 'PWAMP MyBB 1.8',
		'description'   => 'Transcodes MyBB 1.8 into both first load cache-enabled of PWA and lightning fast load time of AMP style.',
		'website'       => 'https://flexplat.com/pwamp-mybb18/',
		'author'        => 'Rickey Gu',
		'authorsite'    => 'https://flexplat.com',
		'version'       => '0.1.0',
		'guid'          => str_replace('.php', '', basename(__FILE__)),
		'codename'      => str_replace('.php', '', basename(__FILE__)),
		'compatibility' => '18*'
	);
}

function pwamp_install()
{
}

function pwamp_is_installed()
{
	return true;
}

function pwamp_activate()
{
}

function pwamp_deactivate()
{
}

function pwamp_uninstall()
{
}


class PWAMP
{
	private $home_url = '';
	private $page_url = '';
	private $viewport_width = '';
	private $permalink = '';
	private $basename = '';


	public function __construct()
	{
		global $mybb;

		$this->home_url = $mybb->settings['bburl'];
		$this->page_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$this->page_url = preg_replace('/&__amp_source_origin=.+$/im', '', $this->page_url);
		$this->viewport_width = !empty($mybb->cookies['pwamp-viewport-width']) ? $mybb->cookies['pwamp-viewport-width'] : '';
		$this->permalink = '';
		$this->basename = basename($_SERVER['PHP_SELF']);
	}

	public function __destruct()
	{
	}


	private function init()
	{
		global $mybb;

		$pattern = str_replace(array('/', '.'), array('\/', '\.'), $this->home_url);
		if ( preg_match('/^' . $pattern . '\/\??manifest\.webmanifest$/im', $this->page_url) )
		{
			header('Content-Type: application/x-web-app-manifest+json', true);
			echo '{
	"name": "' . $mybb->settings['bbname'] . '",
	"short_name": "' . $mybb->settings['bbname'] . '",
	"start_url": "' . $this->home_url . '",
	"display": "standalone",
	"theme_color": "#ffffff",
	"background_color": "#ffffff",
	"icons": [{
		"src": "./inc/plugins/pwamp/lib/manifest/pwamp-logo-512.png",
		"sizes": "512x512",
		"type": "image/png"
	}]
}';

			exit();
		}
		elseif ( preg_match('/^' . $pattern . '\/\??pwamp-sw-html$/im', $this->page_url) )
		{
			header('Content-Type: text/html; charset=utf-8', true);
			echo '<!doctype html>
<html>
<head>
<title>Installing service worker...</title>
<script type=\'text/javascript\'>
	var swsource = \'' . $this->home_url . '/' . ( !empty($this->permalink) ? 'pwamp-sw-js' : '?pwamp-sw-js' ) . '\';
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
		elseif ( preg_match('/^' . $pattern . '\/\??pwamp-sw-js$/im', $this->page_url) )
		{
			header('Content-Type: application/javascript', true);
			echo 'importScripts(\'./inc/plugins/pwamp/lib/sw-toolbox/sw-toolbox.js\');
toolbox.router.default = toolbox.fastest;
self.addEventListener(\'install\', function(event) {
	console.log(\'SW: Installing service worker\');
});';

			exit();
		}
		elseif ( preg_match('/^' . $pattern . '\/\?pwamp-viewport-width=(\d+)$/im', $this->page_url, $matches) )
		{
			$viewport_width = $matches[1];

			my_setcookie('pwamp-viewport-width', $viewport_width);

			exit();
		}
	}


	public function get_amphtml()
	{
		global $headerinclude;

		$amphtml = preg_replace('/((\?)|(&(amp;)?))(amp|desktop)$/im', '', $this->page_url);
		$amphtml .= ( strpos($amphtml, '?') !== false ) ? '&amp' : '?amp';
		$amphtml = htmlspecialchars($amphtml);

		$headerinclude = '<link rel="amphtml" href="' . $amphtml . '" />' . "\n" . $headerinclude;
	}

	private function get_canonical()
	{
		$canonical = preg_replace('/((\?)|(&(amp;)?))(amp|desktop)$/im', '', $this->page_url);
		$canonical .= ( strpos($canonical, '?') !== false ) ? '&desktop' : '?desktop';
		$canonical = htmlspecialchars($canonical);

		return $canonical;
	}


	private function get_device()
	{
		$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$accept = !empty($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
		$profile = !empty($_SERVER['HTTP_PROFILE']) ? $_SERVER['HTTP_PROFILE'] : '';

		$detection = new M_Detection();

		$device = $detection->get_device($user_agent, $accept, $profile);

		return $device;
	}

	private function transcode_page(&$page)
	{
		global $mybb;

		$page = preg_replace('/^[\s\t]*<style type="[^"]+" id="[^"]+"><\/style>$/im', '', $page);

		$data = array(
			'themes_url' => $this->home_url . '/cache/themes/',
			'viewport_width' => $this->viewport_width,
			'permalink' => $this->permalink,
			'page_url' => $this->page_url,
			'canonical' => $this->get_canonical()
		);

		$transcoding = new PWAMP_Transcoding($this->home_url, $data);

		$transcoding->transcode($page);
	}


	private function json_redirect($redirection)
	{
		global $mybb;

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


	public function error($error)
	{
		global $mybb;

		if ( $mybb->request_method != 'post' || $this->basename != 'member.php' )
		{
			return $error;
		}

		my_setcookie('pwamp-error', $error, 5);

		$this->json_redirect($this->home_url . '/member.php');
	}

	public function forumdisplay_start()
	{
		global $mybb;

		if ( empty($mybb->input['pwverify']) && !empty($mybb->cookies['pwamp-pwverify']) )
		{
			$mybb->input['pwverify'] = $mybb->cookies['pwamp-pwverify'];
		}
	}

	public function global_end()
	{
		global $mybb;

		if ( $mybb->request_method != 'get' || $this->basename != 'member.php' )
		{
			return;
		}

		if ( !empty($mybb->cookies['pwamp-url']) )
		{
			$url = $mybb->cookies['pwamp-url'];
			$message = $mybb->cookies['pwamp-message'];
			$title = $mybb->cookies['pwamp-title'];
			$force_redirect = !empty($mybb->cookies['pwamp-force-redirect']) ? TRUE : FALSE;

			redirect($url, $message, $title, $force_redirect);
		}
		elseif ( !empty($mybb->cookies['pwamp-error']) )
		{
			$error = $mybb->cookies['pwamp-error'];

			error($error);
		}
	}

	public function global_intermediate()
	{
		global $mybb;

		if ( $mybb->request_method != 'get' )
		{
			return;
		}

		if ( $this->basename == 'member.php' )
		{
			if ( empty($mybb->cookies['pwamp-register']) )
			{
				return;
			}

			$mybb->request_method = 'post';
		}
		elseif ( $this->basename == 'newreply.php' || $this->basename == 'newthread.php' )
		{
			if ( empty($mybb->cookies['pwamp-post']) )
			{
				return;
			}

			$_POST = json_decode($mybb->cookies['pwamp-post'], TRUE);

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
		global $mybb, $plugins, $db;

		if ( defined('IN_ADMINCP') || defined('IN_ARCHIVE') )
		{
			return;
		}

		if ( file_exists(MYBB_ROOT . 'inc/plugins/pwampp.php') || file_exists(MYBB_ROOT . 'inc/plugins/pwampo.php') )
		{
			return;
		}


		$this->init();


		if ( isset($mybb->input['amp']) || isset($mybb->input['desktop']) )
		{
			$device = !isset($mybb->input['amp']) ? 'desktop' : 'mobile';
		}
		elseif ( !empty($mybb->cookies['pwamp-style']) )
		{
			$device = $mybb->cookies['pwamp-style'] != 'mobile' ? 'desktop' : 'mobile';
		}
		else
		{
			$device = $this->get_device();
			if ( empty($device) )
			{
				return;
			}

			$device = ( $device == 'desktop' || $device == 'bot' ) ? 'desktop' : 'mobile';
		}

		my_setcookie('pwamp-style', $device);


		if ( $device != 'mobile' )
		{
			$plugins->add_hook('global_end', array($this, 'get_amphtml'));

			return;
		}


		$name = 'Default';
		$query = $db->simple_select('themes', 'tid', 'name="' . $db->escape_string($name) . '"', array('limit' => 1));
		$theme = $db->fetch_array($query);
		if ( empty($theme) )
		{
			return;
		}
		$default_tid = $theme['tid'];

		if ( isset($mybb->input['theme']) )
		{
			$mybb->input['theme'] = $default_tid;
		}
		elseif ( $mybb->user['uid'] )
		{
			$mybb->user['style'] = $default_tid;
		}
		else
		{
			$mybb->cookies['mybbtheme'] = $default_tid;
		}


		$plugins->add_hook('error', array($this, 'error'));

		$plugins->add_hook('forumdisplay_start', array($this, 'forumdisplay_start'));

		$plugins->add_hook('global_end', array($this, 'global_end'));
		$plugins->add_hook('global_intermediate', array($this, 'global_intermediate'));

		$plugins->add_hook('member_do_login_end', array($this, 'member_do_login_end'));

		$plugins->add_hook('member_login', array($this, 'member_login'));
		$plugins->add_hook('member_login_end', array($this, 'member_login_end'));

		$plugins->add_hook('member_register_end', array($this, 'member_register_end'));
		$plugins->add_hook('member_register_start', array($this, 'member_register_start'));

		$plugins->add_hook('newreply_end', array($this, 'newreply_end'));
		$plugins->add_hook('newreply_start', array($this, 'newreply_start'));

		$plugins->add_hook('newthread_end', array($this, 'newthread_end'));
		$plugins->add_hook('newthread_start', array($this, 'newthread_start'));

		$plugins->add_hook('pre_output_page', array($this, 'pre_output_page'));

		$plugins->add_hook('redirect', array($this, 'redirect'));
	}

	public function member_do_login_end()
	{
		global $validated;

		if ( !$validated || empty($mybb->cookies['pwamp-errors']) )
		{
			return;
		}

		my_setcookie('pwamp-errors', '', -1);
		my_setcookie('pwamp-do-captcha', '', -1);
	}

	public function member_login()
	{
		global $mybb, $validated, $errors, $do_captcha;

		if ( isset($validated) || empty($mybb->cookies['pwamp-errors']) )
		{
			return;
		}

		$errors = json_decode($mybb->cookies['pwamp-errors']);
		$do_captcha = !empty($mybb->cookies['pwamp-do-captcha']) ? TRUE : FALSE;
	}

	public function member_login_end()
	{
		global $validated, $errors, $do_captcha;

		if ( !isset($validated) || empty($errors) )
		{
			return;
		}

		my_setcookie('pwamp-errors', json_encode($errors), 5);

		if ( $do_captcha )
		{
			my_setcookie('pwamp-do-captcha', '1', 6);
		}

		$this->json_redirect($this->home_url . '/member.php?action=login');
	}

	public function member_register_end()
	{
		global $errors, $regerrors;

		if ( !empty($errors) )
		{
			$regerrors = inline_error($errors);
		}
	}

	public function member_register_start()
	{
		global $mybb, $errors;

		if ( empty($mybb->cookies['pwamp-register']) )
		{
			my_setcookie('pwamp-register', '1', 5);

			if ( !empty($errors) )
			{
				my_setcookie('pwamp-errors', json_encode($errors), 6);
			}

			$this->json_redirect($this->home_url . '/member.php?action=register&agree=1');
		}
		else
		{
			if ( !empty($mybb->cookies['pwamp-errors']) )
			{
				$errors = json_decode($mybb->cookies['pwamp-errors']);
			}
		}
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
			my_setcookie('pwamp-post-errors', json_encode($post_errors), 5);
		}

		my_setcookie('pwamp-post', json_encode($_POST), 6);

		$this->json_redirect($this->home_url . '/newreply.php?tid=' . $tid . '&processed=1');
	}

	public function newreply_start()
	{
		global $mybb, $post_errors, $tid, $reply_errors;

		if ( $mybb->request_method == 'post' )
		{
			if ( !empty($post_errors) )
			{
				my_setcookie('pwamp-post-errors', json_encode($post_errors), 5);

				$this->json_redirect($this->home_url . '/newreply.php?tid=' . $tid . '&amp;processed=1');
			}
		}
		else
		{
			if ( empty($post_errors) && !empty($mybb->cookies['pwamp-post-errors']) )
			{
				$post_errors = json_decode($mybb->cookies['pwamp-post-errors']);
				$reply_errors = inline_error($post_errors);
			}
		}
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
			my_setcookie('pwamp-post-errors', json_encode($post_errors), 5);
		}

		my_setcookie('pwamp-post', json_encode($_POST), 6);

		$this->json_redirect($this->home_url . '/newthread.php?fid=' . $fid . '&processed=1');
	}

	public function newthread_start()
	{
		global $mybb, $post_errors, $thread_errors;

		if ( $mybb->request_method == 'post' )
		{
			return;
		}

		if ( empty($mybb->cookies['pwamp-post-errors']) )
		{
			return;
		}

		$post_errors = json_decode($mybb->cookies['pwamp-post-errors']);
		$thread_errors = inline_error($post_errors);
	}

	public function pre_output_page($page)
	{
		global $mybb;

		if ( $mybb->request_method == 'post' && $this->basename == 'forumdisplay.php' )
		{
			if ( !empty($mybb->input['pwverify']) )
			{
				my_setcookie('pwamp-pwverify', $mybb->input['pwverify'], 5);

				$this->json_redirect($this->page_url);
			}
		}

		$this->transcode_page($page);
		if ( empty($page) )
		{
			return;
		}

		return $page;
	}

	public function redirect($redirect_args)
	{
		global $mybb, $force_redirect;

		if ( $mybb->request_method != 'post' || $this->basename != 'member.php' )
		{
			return $redirect_args;
		}

		my_setcookie('pwamp-url', $redirect_args['url'], 5);

		if ( !empty($redirect_args['message']) )
		{
			my_setcookie('pwamp-message', $redirect_args['message'], 6);
		}

		if ( !empty($redirect_args['title']) )
		{
			my_setcookie('pwamp-title', $redirect_args['title'], 6);
		}

		if ( !empty($force_redirect) )
		{
			my_setcookie('pwamp-force-redirect', '1', 6);
		}

		$this->json_redirect($this->home_url . '/member.php');
	}
}


$pwamp = new PWAMP();

$plugins->add_hook('global_start', array($pwamp, 'global_start'));
