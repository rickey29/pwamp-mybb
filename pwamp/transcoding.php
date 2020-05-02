<?php
if ( !defined('IN_MYBB') )
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT . 'inc/plugins/pwamp/lib/get-remote-file-content.php';
require_once MYBB_ROOT . 'inc/plugins/pwamp/lib/get-remote-image-size.php';

class PWAMPTranscoding
{
	private $selectors = array(
		'.has-drop-cap' => TRUE
	);

	private $img_style = 'div.pwamp-fixed-height-container>amp-img{position:relative;width:100%;height:300px}amp-img.pwamp-contain>img{object-fit:contain}';
	private $notification_style = 'amp-user-notification.pwamp-notification{display:flex;align-items:center;justify-content:center}amp-user-notification.pwamp-notification>button{min-width:80px}';
	private $sidebar_style = 'amp-sidebar>nav{width:auto;margin:0;padding:0;font-family:Lato,Arial,sans-serif;font-size:16px;line-height:1.6}amp-sidebar,amp-sidebar .submenu{width:100%;height:100%}amp-sidebar .main-menu,amp-sidebar .submenu{overflow:auto}amp-sidebar .submenu{top:0;left:0;position:fixed}amp-sidebar .hide-submenu{visibility:hidden;transform:translateX(-100%)}amp-sidebar .show-submenu{visibility:visible;transform:translateX(0)}amp-sidebar .hide-parent{visibility:hidden}amp-sidebar .truncate{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}amp-sidebar .link-container{display:block;height:44px;line-height:44px;border-bottom:1px solid #f0f0f0;padding:0 1rem}amp-sidebar a{min-width:44px;min-height:44px;text-decoration:none;cursor:pointer}amp-sidebar .submenu-icon{padding-right:44px}amp-sidebar .submenu-icon::after{position:absolute;right:0;height:44px;width:44px;content:\'\';background-size:1rem;background-image:url(\'data:image/svg+xml;utf8, <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M5 3l3.057-3 11.943 12-11.943 12-3.057-3 9-9z"/></svg>\');background-repeat:no-repeat;background-position:center}amp-sidebar .controls{display:flex;height:50px;background:#f0f0f0}amp-sidebar .controls a{display:flex;justify-content:center;align-items:center}amp-sidebar .controls span{line-height:50px;margin:0 auto}amp-sidebar nav>.controls>a:first-of-type{visibility:hidden}amp-sidebar .controls a svg{height:1rem;width:1rem}amp-sidebar .link-icon{float:left;height:44px;margin-right:.75rem}amp-sidebar .link-icon>svg{height:44px}amp-sidebar{background:#fff;color:#232323;fill:#232323;text-transform:uppercase;letter-spacing:.18rem;font-size:.875rem}amp-sidebar a{color:#232323;text-transform:none;letter-spacing:normal}div[class*="-sidebar-mask"]{opacity:.8}amp-sidebar a:hover{text-decoration:underline;fill:#232323}amp-sidebar .view-all{font-style:italic;font-weight:700}';

	private $font_server_list = array(
		'https://cloud.typography.com',
		'https://fast.fonts.net',
		'https://fonts.googleapis.com',
		'https://use.typekit.net',
		'https://maxcdn.bootstrapcdn.com',
		'https://use.fontawesome.com'
	);

	private $home_url = '';

	private $page_url = '';
	private $canonical = '';
	private $permalink = '';
	private $page_type = '';
	private $viewport_width = 0;
	private $plugin_dir_url = '';

	protected $home_url_pattern = '';
	private $host_url = '';

	private $style = '';
	private $extened_style = FALSE;

	private $head = '';
	private $body = '';
	private $url = '';


	public function __construct()
	{
	}

	public function __destruct()
	{
	}


	private function update_url($url)
	{
		if ( preg_match('/^https?:\/\//im', $url) )
		{
			$url = preg_replace('/^http:\/\//im', 'https://', $url);
		}
		elseif ( preg_match('/^\/\//im', $url) )
		{
			$url = 'https:' . $url;
		}
		elseif ( preg_match('/^\//im', $url) )
		{
			$url = $this->host_url . $url;
		}
		elseif ( preg_match('/^\.\//im', $url) )
		{
			$url = preg_replace('/^\.\//im', '', $url);
			$url = $this->home_url . '/' . $url;
		}
		else
		{
			$url = $this->home_url . '/' . $url;
		}

		$url = htmlspecialchars_decode($url);

		return $url;
	}

	private function get_extened_style()
	{
		if ( empty($this->style_list) )
		{
			return;
		}

		$url = preg_replace('/^' . $this->home_url_pattern . '\//im', '', $this->page_url);
		$url = md5($url);

		if ( array_key_exists($url, $this->style_list) )
		{
			$this->extened_style = TRUE;
			return $this->style_list[$url];
		}
		elseif ( array_key_exists($this->page_type, $this->style_list) )
		{
			$this->extened_style = TRUE;
			return $this->style_list[$this->page_type];
		}
	}

	private function minicss($css, $id = '')
	{
		$css = !empty($id) ? $id . '{' . $css . '}' : $css;

		$css = preg_replace('/\/\*[^*]*\*+([^\/][^*]*\*+)*\//', '', $css);
		$css = preg_replace('/[\r\n\s\t]+/', ' ', $css);
		$css = preg_replace('/\s*([{}\[\]\(\):;,>\+~])\s*/i', '${1}', $css);
		$css = str_replace(';}', '}', $css);
		$css = trim($css);

		$css = preg_replace('/::?((after)|(before)|(first-letter)|(first-line)|(placeholder)|(selection))/i', '::${1}', $css);
		$css = preg_replace('/([\s:])0\./i', '${1}.', $css);
		$css = preg_replace('/\b((background)|(border)|(border-top)):0([;}])/i', '${1}:none${5}', $css);
		$css = preg_replace('/\b((color)):#000([;}])/i', '${1}:#000000${3}', $css);

		$css = preg_replace('/\s*!important\b\s*/i', '', $css);
		$css = preg_replace('/\s*@charset (("utf-8")|(\'utf-8\'));\s*/i', '', $css);
		$css = preg_replace('/\s*@((-ms-viewport)|(viewport)){[^}]+}\s*/i', '', $css);
		$css = preg_replace('/\s*text-rendering:\s*optimizeLegibility;??\s*/iU', '', $css);
		$css = preg_replace('/\s*\*display:\s*/i', 'display:', $css);

		if ( !empty($id) && preg_match('/{}$/im', $css) )
		{
			return;
		}

		return $css;
	}

	private function collect_selector($page)
	{
		preg_match_all('/<[a-z][^>]*\s+class=(("([^"]*)")|(\'([^\']*)\'))[^>]*>/i', $page, $matches);
		foreach ( $matches[1] as $key => $value )
		{
			$value = !empty($matches[2][$key]) ? $matches[3][$key] : $matches[5][$key];

			$matches2 = preg_split('/\s+/', $value, 0, PREG_SPLIT_NO_EMPTY);
			foreach ( $matches2 as $value )
			{
				$this->selectors['.' . $value] = TRUE;
			}
		}

		preg_match_all('/<[a-z][^>]*\s+id=(("([^"]*)")|(\'([^\']*)\'))[^>]*>/i', $page, $matches);
		foreach ( $matches[1] as $key => $value )
		{
			$value = !empty($matches[2][$key]) ? $matches[3][$key] : $matches[5][$key];

			$this->selectors['#' . $value] = TRUE;
		}

		preg_match_all('/<([a-z][_a-z0-9-]*)(\s+[^>]*)?>/i', $page, $matches);
		foreach ( $matches[1] as $value )
		{
			$this->selectors[$value] = TRUE;
		}
	}

	public function init($home_url, $data)
	{
		$this->home_url = $home_url;


		if ( !empty($data['page_url']) && is_string($data['page_url']) )
		{
			$this->page_url = $data['page_url'];
		}
		else
		{
			$this->page_url = $home_url . '/';
		}

		if ( !empty($data['canonical']) && is_string($data['canonical']) )
		{
			$this->canonical = $data['canonical'];
		}
		else
		{
			$canonical = htmlspecialchars_decode($this->page_url);
			$canonical = preg_replace('/^(.*)(((\?)|(&(amp;)?))((amp)|(desktop))(=1)?)?(#[^#]*)?$/imU', '${1}${11}', $canonical);
			$canonical = preg_replace('/^(.*)(#[^#]*)?$/imU', '${1}' . ( ( strpos($canonical, '?') !== false ? '&desktop=1' : '?desktop=1' ) ) . '${2}', $canonical);
			$this->canonical = htmlspecialchars($canonical);
		}

		if ( !empty($data['permalink']) && is_string($data['permalink']) )
		{
			$this->permalink = $data['permalink'];
		}

		if ( !empty($data['page_type']) && is_string($data['page_type']) )
		{
			$this->page_type = $data['page_type'];
		}

		if ( !empty($data['viewport_width']) && is_string($data['viewport_width']) )
		{
			$this->viewport_width = (int)$data['viewport_width'];
		}

		if ( !empty($data['plugin_dir_url']) && is_string($data['plugin_dir_url']) )
		{
			$this->plugin_dir_url = $data['plugin_dir_url'];
		}


		$home_url_pattern = preg_replace('/^https?:\/\//im', 'https?://', $this->home_url);
		$this->home_url_pattern = str_replace(array('/', '.'), array('\/', '\.'), $home_url_pattern);

		$this->host_url = preg_replace('/^https?:\/\/([^\/]*?)\/??.*$/imU', 'https://${1}', $this->home_url);


		$this->style .= $this->get_extened_style();
	}


	private function action_callback($matches)
	{
		if ( empty($matches[1]) ) $matches[1] = '';
		if ( empty($matches[4]) ) $matches[4] = '';
		if ( empty($matches[6]) ) $matches[6] = '';
		if ( empty($matches[8]) ) $matches[8] = '';
		if ( empty($matches[10]) ) $matches[10] = '';
		if ( empty($matches[11]) ) $matches[11] = '';

		$match = !empty($matches[3]) ? $matches[5] : $matches[9];

		$match = $this->update_url($match);

		return '<form' . $matches[1] . ' action=' . $matches[4] . $matches[8] . $match . $matches[6] . $matches[10] . $matches[11] . '>';
	}

	private function external_css_callback($matches)
	{
		$match = $matches[1];

		if ( !preg_match('/ rel=(("stylesheet")|(\'stylesheet\'))/i', $match) )
		{
			return '<link' . $match . ' />';
		}

		if ( !preg_match('/ href=(("([^"]*)")|(\'([^\']*)\'))/i', $match, $match2) )
		{
			return '<link' . $match . ' />';
		}

		$url = !empty($match2[2]) ? $match2[3] : $match2[5];
		$host = preg_replace('/^https?:\/\/([^\/]+)\/.*$/im', 'https://${1}', $url);
		if ( in_array($host, $this->font_server_list) )
		{
			return '<link' . $match . ' />';
		}

		if ( $this->extened_style )
		{
			return '';
		}

		$url = $this->update_url($url);
		$css = get_remote_data($url);

		$this->url = $url;
		$css = preg_replace_callback('/url\(((("??)([^"\'\)]*?)("??))|((\'??)([^"\'\)]*?)(\'??)))\)/iU', array($this, 'url_callback'), $css);

		$this->style .= $css;

		return '';
	}

	private function form_callback($matches)
	{
		$match = $matches[1];

		if ( preg_match('/ method=(("post")|(\'post\'))/i', $match) )
		{
			if ( !preg_match('/ action=(("[^"]*")|(\'[^\']*\'))/i', $match) )
			{
				// The mandatory attribute 'action-xhr' is missing in tag 'FORM [method=POST]'.
				$match .= ' action-xhr="' . $this->page_url . '"';
			}
			else
			{
				// The attribute 'action' may not appear in tag 'FORM [method=POST]'.
				$match = preg_replace('/ action=(("[^"]*")|(\'[^\']*\'))/i', ' action-xhr=${1}', $match);
			}

			// Invalid URL protocol 'http:' for attribute 'action-xhr' in tag 'FORM [method=POST]'.
			$match = preg_replace('/ action-xhr=(((")http:\/\/([^"]*)("))|((\')http:\/\/([^\']*)(\')))/i', ' action-xhr=${3}${7}https://${4}${8}${5}${9}', $match);
		}
		else
		{
			// The mandatory attribute 'action' is missing in tag 'FORM [method=GET]'.
			if ( !preg_match('/ action=(("[^"]*")|(\'[^\']*\'))/i', $match) )
			{
				$match .= ' action="' . $this->page_url . '"';
			}

			// The mandatory attribute 'target' is missing in tag 'FORM [method=GET]'.
			if ( !preg_match('/ target=(("[^"]*")|(\'[^\']*\'))/i', $match) )
			{
				$match .= ' target="_top"';
			}
		}

		return '<form' . $match . '>';
	}

	private function href_callback($matches)
	{
		if ( empty($matches[1]) ) $matches[1] = '';
		if ( empty($matches[4]) ) $matches[4] = '';
		if ( empty($matches[6]) ) $matches[6] = '';
		if ( empty($matches[8]) ) $matches[8] = '';
		if ( empty($matches[10]) ) $matches[10] = '';
		if ( empty($matches[11]) ) $matches[11] = '';

		$match = !empty($matches[3]) ? $matches[5] : $matches[9];

		$match = $this->update_url($match);

		return '<a' . $matches[1] . ' href=' . $matches[4] . $matches[8] . $match . $matches[6] . $matches[10] . $matches[11] . '>';
	}

	private function iframe_callback($matches)
	{
		$match = $matches[1];

		$match = preg_replace('/ sizes=(("[^"]*")|(\'[^\']*\'))/i', '', $match);

		if ( preg_match('/ class=(("[^"]*")|(\'[^\']*\'))/i', $match) )
		{
			$match = preg_replace('/ class=(((")([^"]*)("))|((\')([^\']*)(\')))/i', ' class=${3}${7}${4}${8} pwamp-contain${5}${9}', $match);
		}
		else
		{
			$match .= ' class="pwamp-contain"';
		}


		$width = '';
		if ( preg_match('/ width=(("([^"]*)")|(\'([^\']*)\'))/i', $match, $match2) )
		{
			$width = !empty($match2[2]) ? $match2[3] : $match2[5];
		}

		$height = '';
		if ( preg_match('/ height=(("([^"]*)")|(\'([^\']*)\'))/i', $match, $match2) )
		{
			$height = !empty($match2[2]) ? $match2[3] : $match2[5];
		}

		if ( empty($width) && empty($height) )
		{
			return '';
		}


		$match = preg_replace('/ layout=(("[^"]*")|(\'[^\']*\'))/i', '', $match);
		if ( !empty($width) && !empty($height) )
		{
			$match .= ' layout="intrinsic"';

			return '<amp-iframe' . $match . '><noscript><iframe' . $matches[1] . ' /></noscript></amp-iframe>';
		}
		else
		{
			$match .= ' layout="fill"';

			return '<div class="pwamp-fixed-height-container"><amp-iframe' . $match . '><noscript><iframe' . $matches[1] . ' /></noscript></amp-iframe></div>';
		}
	}

	private function img_callback($matches)
	{
		$match = $matches[1];

		$match = preg_replace('/ sizes=(("[^"]*")|(\'[^\']*\'))/i', '', $match);

		if ( preg_match('/ class=(("[^"]*")|(\'[^\']*\'))/i', $match) )
		{
			$match = preg_replace('/ class=(((")([^"]*)("))|((\')([^\']*)(\')))/i', ' class=${3}${7}${4}${8} pwamp-contain${5}${9}', $match);
		}
		else
		{
			$match .= ' class="pwamp-contain"';
		}


		$width = '';
		if ( preg_match('/ width=(("([^"]*)")|(\'([^\']*)\'))/i', $match, $match2) )
		{
			$width = !empty($match2[2]) ? $match2[3] : $match2[5];
		}

		$height = '';
		if ( preg_match('/ height=(("([^"]*)")|(\'([^\']*)\'))/i', $match, $match2) )
		{
			$height = !empty($match2[2]) ? $match2[3] : $match2[5];
		}

		if ( empty($width) && empty($height) )
		{
			if ( !preg_match('/ src=(("([^"]*)")|(\'([^\']*)\'))/i', $match, $match2) )
			{
				return '';
			}
			$src = !empty($match2[2]) ? $match2[3] : $match2[5];

			list($width, $height) = get_image_size($src);
			if ( empty($width) && empty($height) )
			{
				return '';
			}

			$match .= ' width="' . $width . '" height="' . $height . '"';
		}


		if ( empty($matches[2]) )
		{
			$matches[2] = '<noscript><img' . $matches[1] . ' /></noscript>';
		}

		$match = preg_replace('/ layout=(("[^"]*")|(\'[^\']*\'))/i', '', $match);
		if ( !empty($width) && !empty($height) )
		{
			$match .= ' layout="intrinsic"';

			return '<amp-img' . $match . '>' . $matches[2] . '</amp-img>';
		}
		else
		{
			$match .= ' layout="fill"';

			return '<div class="pwamp-fixed-height-container"><amp-img' . $match . '>' . $matches[2] . '</amp-img></div>';
		}
	}

	private function inline_css_callback($matches)
	{
		if ( empty($matches[4]) ) $matches[4] = '';
		if ( empty($matches[6]) ) $matches[6] = '';
		if ( empty($matches[8]) ) $matches[8] = '';
		if ( empty($matches[10]) ) $matches[10] = '';

		$match = !empty($matches[3]) ? $matches[5] : $matches[9];

		$match = $this->minicss($match);

		return '<' . $matches[1] . ' style=' . $matches[4] . $matches[8] . $match . $matches[6] . $matches[10] . $matches[11] . $matches[12] . $matches[13] . '>';
	}

	private function internal_css_callback($matches)
	{
		$match = $matches[1];

		if ( !$this->extened_style )
		{
			$this->style .= $match;
		}

		return '';
	}

	private function pixel_callback($matches)
	{
		$this->body .= "\n" . '<amp-pixel src="' . $matches[1] . '" layout="nodisplay"></amp-pixel>';

		return '';
	}

	private function src_callback($matches)
	{
		if ( empty($matches[1]) ) $matches[1] = '';
		if ( empty($matches[4]) ) $matches[4] = '';
		if ( empty($matches[6]) ) $matches[6] = '';
		if ( empty($matches[8]) ) $matches[8] = '';
		if ( empty($matches[10]) ) $matches[10] = '';
		if ( empty($matches[11]) ) $matches[11] = '';

		$match = !empty($matches[3]) ? $matches[5] : $matches[9];

		if ( preg_match('/data:image\/gif;base64,/i', $match) )
		{
			$match = preg_replace('/^.+(data:image\/gif;base64,.+)$/im', '${1}', $match);

			return '<img' . $matches[1] . ' src=' . $matches[4] . $matches[8] . $match . $matches[6] . $matches[10] . $matches[11] . '>';
		}

		$match = $this->update_url($match);

		return '<img' . $matches[1] . ' src=' . $matches[4] . $matches[8] . $match . $matches[6] . $matches[10] . $matches[11] . '>';
	}

	private function textarea_callback($matches)
	{
		$match = $matches[2];

		$match = str_replace(array("\r\n", "\r", "\n"), '<amp-br />', $match);

		return '<textarea' . $matches[1] . '>' . $match . '</textarea>';
	}

	private function textarea2_callback($matches)
	{
		$match = $matches[2];

		$match = str_replace('<amp-br />', "\n", $match);

		return '<textarea' . $matches[1] . '>' . $match . '</textarea>';
	}

	private function url_callback($matches)
	{
		if ( empty($matches[3]) ) $matches[3] = '';
		if ( empty($matches[5]) ) $matches[5] = '';
		if ( empty($matches[7]) ) $matches[7] = '';
		if ( empty($matches[9]) ) $matches[9] = '';

		$match = !empty($matches[2]) ? $matches[4] : $matches[8];

		if ( preg_match('/^data\:((application)|(image))\//im', $match) )
		{
			return 'url(' . $matches[3] . $matches[7] . $match . $matches[5] . $matches[9] . ')';
		}

		if ( preg_match('/^https?:\/\//im', $match) )
		{
			$match = preg_replace('/^http:\/\//im', 'https://', $match);
		}
		elseif ( preg_match('/^\/\//im', $match) )
		{
			$match = 'https:' . $match;
		}
		elseif ( preg_match('/^\//im', $match) )
		{
			$match = $this->host_url . $match;
		}
		elseif ( preg_match('/^\.\.\/\.\.\/\.\.\/\.\.\//im', $match) )
		{
			$url = preg_replace('/[^\/]+\/[^\/]+\/[^\/]+\/[^\/]+\/[^\/]*$/im', '', $this->url);

			$match = preg_replace('/^\.\.\/\.\.\/\.\.\/\.\.\//im', '', $match);
			$match = $url . $match;
		}
		elseif ( preg_match('/^\.\.\/\.\.\/\.\.\//im', $match) )
		{
			$url = preg_replace('/[^\/]+\/[^\/]+\/[^\/]+\/[^\/]*$/im', '', $this->url);

			$match = preg_replace('/^\.\.\/\.\.\/\.\.\//im', '', $match);
			$match = $url . $match;
		}
		elseif ( preg_match('/^\.\.\/\.\.\//im', $match) )
		{
			$url = preg_replace('/[^\/]+\/[^\/]+\/[^\/]*$/im', '', $this->url);

			$match = preg_replace('/^\.\.\/\.\.\//im', '', $match);
			$match = $url . $match;
		}
		elseif ( preg_match('/^\.\.\//im', $match) )
		{
			$url = preg_replace('/[^\/]+\/[^\/]*$/im', '', $this->url);

			$match = preg_replace('/^\.\.\//im', '', $match);
			$match = $url . $match;
		}
		elseif ( preg_match('/^\.\//im', $match) )
		{
			$url = preg_replace('/[^\/]*$/im', '', $this->url);

			$match = preg_replace('/^\.\//im', '', $match);
			$match = $url . $match;
		}
		else
		{
			$url = preg_replace('/[^\/]*$/im', '', $this->url);

			$match = $url . $match;
		}

		$match = preg_replace('/^' . $this->home_url_pattern . '\//im', '', $match);

		return 'url(' . $matches[3] . $matches[7] . $match . $matches[5] . $matches[9] . ')';
	}

	private function video_callback($matches)
	{
		$match = $matches[1];

		$match = preg_replace('/ sizes=(("[^"]*")|(\'[^\']*\'))/i', '', $match);

		if ( preg_match('/ class=(("[^"]*")|(\'[^\']*\'))/i', $match) )
		{
			$match = preg_replace('/ class=(((")([^"]*)("))|((\')([^\']*)(\')))/i', ' class=${3}${7}${4}${8} pwamp-contain${5}${9}', $match);
		}
		else
		{
			$match .= ' class="pwamp-contain"';
		}


		$width = '';
		if ( preg_match('/ width=(("([^"]*)")|(\'([^\']*)\'))/i', $match, $match2) )
		{
			$width = !empty($match2[2]) ? $match2[3] : $match2[5];
		}

		$height = '';
		if ( preg_match('/ height=(("([^"]*)")|(\'([^\']*)\'))/i', $match, $match2) )
		{
			$height = !empty($match2[2]) ? $match2[3] : $match2[5];
		}

		if ( empty($width) && empty($height) )
		{
			return '';
		}


		$match = preg_replace('/ layout=(("[^"]*")|(\'[^\']*\'))/i', '', $match);
		if ( !empty($width) && !empty($height) )
		{
			$match .= ' layout="intrinsic"';

			return '<amp-video' . $match . '><noscript><video' . $matches[1] . ' /></noscript></amp-video>';
		}
		else
		{
			$match .= ' layout="fill"';

			return '<div class="pwamp-fixed-height-container"><amp-video' . $match . '><noscript><video' . $matches[1] . ' /></noscript></amp-video></div>';
		}
	}

	public function transcode_html($page)
	{
		$page = preg_replace('/<!--.*-->/isU', '', $page);


		/*
			<!doctype>
		*/
		// The attribute '"-//w3c//dtd' may not appear in tag 'html doctype'.
		// The attribute '"http://www.w3.org/tr/xhtml1/dtd/xhtml1-transitional.dtd"' may not appear in tag 'html doctype'.
		// The attribute '1.0' may not appear in tag 'html doctype'.
		// The attribute 'public' may not appear in tag 'html doctype'.
		// The attribute 'transitional//en"' may not appear in tag 'html doctype'.
		// The attribute 'xhtml' may not appear in tag 'html doctype'.
		$page = preg_replace('/<!DOCTYPE\b[^>]*>/i', '<!doctype html>', $page, 1);


		/*
			<a></a>
		*/
		$page = preg_replace_callback('/<a([^>]*) href=(((")([^"]*)("))|((\')([^\']*)(\')))([^>]*)\s*?>/iU', array($this, 'href_callback'), $page);

		// The attribute 'alt' may not appear in tag 'a'.
		$page = preg_replace('/<a\b([^>]*) alt=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<a${1}${5}>', $page);


		/*
			<amp-install-serviceworker></amp-install-serviceworker>
		*/
		$page = preg_replace('/<amp-install-serviceworker.+>.*<\/amp-install-serviceworker>/isU', '', $page);


		/*
			<area/>
		*/
		// The tag 'area' is disallowed.
		$page = preg_replace('/<area\b([^>]*)\s*?\/?>/iU', '', $page);


		/*
			<audio></audio>
		*/
		// The tag 'audio' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-audio'?
		$page = preg_replace('/<audio\b([^>]*)\s*?>/iU', '<amp-audio${1}>', $page);


		/*
			<button></button>
		*/
		// The attribute 'href' may not appear in tag 'button'.
		$page = preg_replace('/<button\b([^>]*) href=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<button${1}${5}>', $page);


		/*
			<canvas></canvas>
		*/
		// The tag 'canvas' is disallowed.
		$page = preg_replace('/<canvas\b[^>]*>.*<\/canvas>/isU', '', $page);


		/*
			<col/>
		*/
		// The attribute 'width' may not appear in tag 'col'.
		$page = preg_replace('/<col\b([^>]*) width=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<col${1}${5} />', $page);


		/*
			<div></div>
		*/
		// The attribute 'name' may not appear in tag 'div'.
		$page = preg_replace('/<div\b([^>]*) name=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<div${1}${5}>', $page);

		// The attribute 'target' may not appear in tag 'div'.
		$page = preg_replace('/<div\b([^>]*) target=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<div${1}${5}>', $page);


		/*
			<embed/>
		*/
		// The tag 'embed' is disallowed.
		$page = preg_replace('/<embed\b([^>]*)\s*?\/?>/iU', '', $page);


		/*
			<font></font>
		*/
		// The tag 'font' is disallowed.
		$page = preg_replace('/<font[^>]*>(.*)<\/font>/isU', '${1}', $page);


		/*
			<form></form>
		*/
		$page = preg_replace_callback('/<form([^>]*) action=(((")([^"]*)("))|((\')([^\']*)(\')))([^>]*)\s*?>/iU', array($this, 'action_callback'), $page);

		$page = preg_replace_callback('/<form\b([^>]*)\s*?>/iU', array($this, 'form_callback'), $page);


		/*
			<head></head>
		*/
		$page = preg_replace('/^[\s\t]*<head>/im', '<head>', $page, 1);
		$page = preg_replace('/^[\s\t]*<\/head>/im', '</head>', $page, 1);


		/*
			<hr/>
		*/
		// The attribute 'size' may not appear in tag 'hr'.
		$page = preg_replace('/<hr\b([^>]*) size=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<hr${1}${5} />', $page);


		/*
			<html>
		*/
		// The attribute 'xml:lang' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xml:lang=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<html${1}${5}>', $page);

		// The attribute 'xmlns' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xmlns=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<html${1}${5}>', $page);

		// The attribute 'xmlns:fb' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xmlns:fb=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<html${1}${5}>', $page);

		// The attribute 'xmlns:og' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xmlns:og=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<html${1}${5}>', $page);

		$page = preg_replace('/<html\b([^>]*) ((amp)|(⚡))\b([^>]*)\s*?>/iU', '<html${1}${5}>', $page, 1);
		$page = preg_replace('/<html\b([^>]*)\s*?>/iU', '<html amp${1}>', $page, 1);


		/*
			<icon></icon>
		*/
		$page = preg_replace('/<icon\b([^>]*)\s*?><\/icon>/i', '<div${1}></div>', $page);


		/*
			<iframe></iframe>
		*/
		$page = preg_replace('/<iframe\b([^>]*) src=(("[^"]*")|(\'[^\']*\'))([^>]*) data-lazy-src=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<iframe${1} src=${6}${5}${9} />', $page);

		// The tag 'iframe' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-iframe'?
		$page = preg_replace_callback('/<iframe\b([^>]*)\s*?\/?>/iU', array($this, 'iframe_callback'), $page);

		// The attribute 'align' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) align=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);

		// The attribute 'allowtransparency' in tag 'amp-iframe' is set to the invalid value 'true'.
		$page = preg_replace('/<amp-iframe\b([^>]*) allowtransparency=(("true")|(\'true\'))([^>]*)\s*?>/iU', '<amp-iframe${1} allowtransparency="allowtransparency"${5}>', $page);

		// The attribute 'frameborder' in tag 'amp-iframe' is set to the invalid value 'no'.
		$page = preg_replace('/<amp-iframe\b([^>]*) frameborder=(("no")|(\'no\'))([^>]*)\s*?>/iU', '<amp-iframe${1} frameborder="0"${5}>', $page);

		// The attribute 'marginheight' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) marginheight=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);

		// The attribute 'marginwidth' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) marginwidth=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);

		// The attribute 'mozallowfullscreen' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) mozallowfullscreen=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);
		$page = preg_replace('/<amp-iframe\b([^>]*) mozallowfullscreen\b([^>]*)\s*?>/iU', '<amp-iframe${1}${2}>', $page);

		// The attribute 'name' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) name=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);

		// Invalid URL protocol 'http:' for attribute 'src' in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) src=(((")http:\/\/([^"]*)("))|((\')http:\/\/([^\']*)(\')))([^>]*)\s*?>/iU', '<amp-iframe${1} src=${4}${8}https://${5}${9}${6}${10}${11}>', $page);

		// The attribute 'webkitallowfullscreen' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) webkitallowfullscreen=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);
		$page = preg_replace('/<amp-iframe\b([^>]*) webkitallowfullscreen\b([^>]*)\s*?>/iU', '<amp-iframe${1}${2}>', $page);

		// The attribute 'allow' may not appear in tag 'iframe'.
		$page = preg_replace('/<iframe\b([^>]*) allow=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<iframe${1}${5} />', $page);

		// The attribute 'allowfullscreen' may not appear in tag 'iframe'.
		$page = preg_replace('/<iframe\b([^>]*) allowfullscreen([^>]*)\s*?\/?>/iU', '<iframe${1}${2} />', $page);


		/*
			Pixel
		*/
		$page = preg_replace_callback('/<noscript>\s*<img height="1" width="1"[^>]* src="([^"]+)"[^>]*\s*?\/?>\s*<\/noscript>/isU', array($this, 'pixel_callback'), $page);


		/*
			<img/>
		*/
		$page = preg_replace('/<img\b([^>]*) src=(("[^"]*")|(\'[^\']*\'))([^>]*) data-lazy-src=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<img${1} src=${6}${5}${9} />', $page);

		$page = preg_replace_callback('/<img([^>]*) src=(((")([^"]*)("))|((\')([^\']*)(\')))([^>]*)\s*?>/iU', array($this, 'src_callback'), $page);

		// The tag 'img' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-img'?
		$page = preg_replace_callback('/<img\b([^>]*)\s*?\/?>(<noscript><img\b[^>]*\s*?\/?><\/noscript>)??/iU', array($this, 'img_callback'), $page);

		// The attribute 'align' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) align=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<amp-img${1}${5} />', $page);

		// The attribute 'async' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) async=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<amp-img${1}${5} />', $page);

		// The attribute 'border' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) border=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<amp-img${1}${5} />', $page);

		// The attribute 'usemap' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) usemap=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<amp-img${1}${5} />', $page);


		/*
			<input>
		*/
		// The attribute 'onblur' may not appear in tag 'input'.
		$page = preg_replace('/<input\b([^>]*) onblur=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<input${1}${5}>', $page);

		// The attribute 'tooltip' may not appear in tag 'input'.
		$page = preg_replace('/<input\b([^>]*) tooltip=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<input${1}${5}>', $page);


		/*
			<link/>
		*/
		$page = preg_replace('/<link\b[^>]* rel=(("apple-touch-icon")|(\'apple-touch-icon\'))[^>]* href=(("[^"]*")|(\'[^\']*\'))[^>]*\s*?\/?>/iU', '', $page);
		$page = preg_replace('/<link\b[^>]* rel=(("canonical")|(\'canonical\'))[^>]*\s*?\/?>/iU', '', $page);
		$page = preg_replace('/<link\b[^>]* rel=(("manifest")|(\'manifest\'))[^>]* href=(("[^"]*")|(\'[^\']*\'))[^>]*\s*?\/?>/iU', '', $page);

		$page = preg_replace('/<link\b([^>]*)\s? href=(((")\/\/([^"]*)("))|((\')\/\/([^\']*)(\')))([^>]*)\s*?\/?>/iU', '<link${1} href=${4}${8}https://${5}${9}${6}${10}${11} />', $page);

		// The attribute 'href' in tag 'link rel=stylesheet for fonts' is set to the invalid value...
		$page = preg_replace_callback('/<link\b([^>]*)\s*?\/?>/iU', array($this, 'external_css_callback'), $page);

		$page = preg_replace('/^[\s\t]*<link\b([^>]*)\s*?>/imU', '<link${1}>', $page);


		/*
			<map></map>
		*/
		// The tag 'map' is disallowed.
		$page = preg_replace('/<map\b[^>]*>.*<\/map>/isU', '', $page);


		/*
			<meta>
		*/
		$page = preg_replace('/<meta\b[^>]* charset=(("utf-8")|(\'utf-8\'))[^>]*\s*?\/?>/iU', '', $page);

		// The attribute 'content' in tag 'meta http-equiv=Content-Type' is set to the invalid value 'text/html;charset=utf-8'.
		$page = preg_replace('/<meta http-equiv="Content-Type" content="text\/html;\s?charset=[^"]*"\s*?\/?>/iU', '', $page);

		// The attribute 'http-equiv' may not appear in tag 'meta name= and content='.
		$page = preg_replace('/<meta\b[^>]* http-equiv=(("refresh")|(\'refresh\'))[^>]*\s*?\/?>/iU', '', $page);

		$page = preg_replace('/<meta name="pwamp-page-type" content="[^"]+"\s*?\/?>/iU', '', $page);

		// The attribute 'name' in tag 'meta name= and content=' is set to the invalid value 'revisit-after'.
		$page = preg_replace('/<meta\b[^>]* name=(("revisit-after")|(\'revisit-after\'))[^>]*\s*?\/?>/iU', '', $page);

		$page = preg_replace('/<meta\b[^>]* name=(("theme-color")|(\'theme-color\'))[^>]* content=(("[^"]*")|(\'[^\']*\'))[^>]*\s*?\/?>/iU', '', $page);
		$page = preg_replace('/<meta\b[^>]* name=(("viewport")|(\'viewport\'))[^>]*\s*?\/?>/iU', '', $page);

		$page = preg_replace('/^[\s\t]*<meta\b([^>]*)\s*?>/imU', '<meta${1}>', $page);


		/*
			<object></object>
		*/
		// The tag 'object' is disallowed.
		$page = preg_replace('/<object\b[^>]*>.*<\/object>/isU', '', $page);


		/*
			<script></script>
		*/
		// Custom JavaScript is not allowed.
		$page = preg_replace('/<script\b[^>]*>.*<\/script>/isU', '', $page);


		/*
			<select></select>
		*/
		// The attribute 'value' may not appear in tag 'select'.
		$page = preg_replace('/<select\b([^>]*) value=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<select${1}${5}>', $page);


		/*
			<span></span>
		*/
		// The attribute 'active' may not appear in tag 'span'.
		$page = preg_replace('/<span\b([^>]*) active=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<span${1}${5}>', $page);

		// The attribute 'amount' may not appear in tag 'span'.
		$page = preg_replace('/<span\b([^>]*) amount=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<span${1}${5}>', $page);

		// The attribute 'override' may not appear in tag 'span'.
		$page = preg_replace('/<span\b([^>]*) override=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<span${1}${5}>', $page);

		// The attribute 'temscope' may not appear in tag 'span'.
		$page = preg_replace('/<span\b([^>]*) temscope=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<span${1}${5}>', $page);
		$page = preg_replace('/<span\b([^>]*) temscope\b([^>]*)\s*?>/iU', '<span${1}${2}>', $page);


		/*
			<style></style>
		*/
		// The mandatory attribute 'amp-custom' is missing in tag 'style amp-custom'.
		$page = preg_replace_callback('/<style\b[^>]*>(.*)<\/style>/isU', array($this, 'internal_css_callback'), $page);

		$page = preg_replace('/<noscript>[\s\t\r\n]*<\/noscript>/i', '', $page);


		/*
			<svg></svg>
		*/
		// The attribute 'xmlns:serif' may not appear in tag 'svg'.
		$page = preg_replace('/<svg\b([^>]*) xmlns:serif=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<svg${1}${5}>', $page);


		/*
			<table></table>
		*/
		// The attribute 'frame' may not appear in tag 'table'.
		$page = preg_replace('/<table\b([^>]*) frame=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<table${1}${5}>', $page);

		// The attribute 'rules' may not appear in tag 'table'.
		$page = preg_replace('/<table\b([^>]*) rules=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<table${1}${5}>', $page);


		/*
			<textarea></textarea>
		*/
		// The attribute 'tooltip' may not appear in tag 'textarea'.
		$page = preg_replace('/<textarea\b([^>]*) tooltip=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<textarea${1}${5}>', $page);

		// The attribute 'value' may not appear in tag 'textarea'.
		$page = preg_replace('/<textarea\b([^>]*) value=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<textarea${1}${5}>', $page);


		/*
			<time></time>
		*/
		// The attribute 'pubdate' may not appear in tag 'time'.
		$page = preg_replace('/<time\b([^>]*) pubdate=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<time${1}${5}>', $page);
		$page = preg_replace('/<time\b([^>]*) pubdate\b([^>]*)\s*?>/iU', '<time${1}${2}>', $page);


		/*
			<title></title>
		*/
		$page = preg_replace('/^[\s\t]*<title>(.*)<\/title>/im', '<title>${1}</title>', $page, 1);


		/*
			<ul></ul>
		*/
		// The attribute 'featured_post_id' may not appear in tag 'ul'.
		$page = preg_replace('/<ul\b([^>]*) featured_post_id=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<ul${1}${5}>', $page);


		/*
			<video></video>
		*/
		// The tag 'video' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-video'?
		$page = preg_replace_callback('/<video\b([^>]*)\s*?\/?>/iU', array($this, 'video_callback'), $page);


		/*
			Any Tag
		*/
		// The attribute 'onclick' may not appear in tag 'a'.
		$page = preg_replace('/<(\w+\b[^>]*) on\w+=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*(\s?)(\/?)>/iU', '<${1}${5}${6}${7}>', $page);

		$page = preg_replace_callback('/<(\w+\b[^>]*) style=(((")([^"]*)("))|((\')([^\']*)(\')))([^>]*)\s*(\s?)(\/?)>/iU', array($this, 'inline_css_callback'), $page);

		return $page;
	}


	private function link_callback($matches)
	{
		if ( preg_match('/ href=(("https:\/\/s\.w\.org\/?")|(\'https:\/\/s\.w\.org\/?\'))/i', $matches[1]) )
		{
			return '';
		}
		elseif ( preg_match('/ href=(("https:\/\/s\.w\.org\/?")|(\'https:\/\/s\.w\.org\/?\'))/i', $matches[17]) )
		{
			return '';
		}

		$this->head .= "\n" . '<link' . $matches[1] . ' rel=' . $matches[2] . $matches[17] . ' />';

		return '';
	}

	private function link2_callback($matches)
	{
		$this->head .= '<link' . $matches[1] . ' rel=' . $matches[2] . $matches[5] . ' />'. "\n";

		return '';
	}

	private function meta_callback($matches)
	{
		$this->head .= "\n" . '<meta' . $matches[1] . ' />';

		return '';
	}

	private function title_callback($matches)
	{
		$this->head .= "\n" . '<title>' . $matches[1] . '</title>';

		return '';
	}

	public function transcode_head($page)
	{
		// Service Workers
		$this->body = '<amp-install-serviceworker src="' . $this->home_url . '/' . ( empty($this->permalink) ? '?' : '' ) . 'pwamp-sw.js" data-iframe-src="' . $this->home_url . '/' . ( empty($this->permalink) ? '?' : '' ) . 'pwamp-sw.html" layout="nodisplay"></amp-install-serviceworker>';

		// Viewport Width
		$this->body .= "\n" . '<amp-pixel src="' . $this->home_url . '/?pwamp-viewport-width=VIEWPORT_WIDTH" layout="nodisplay"></amp-pixel>';

		// User Notification
		$this->body .= "\n" . '<amp-user-notification id="pwamp-notification" class="pwamp-notification" data-persist-dismissal="false" layout="nodisplay">Switch to&nbsp;<a href="' . $this->canonical . '">desktop version</a>&nbsp;&nbsp;<button on="tap:pwamp-notification.dismiss">Continue</button></amp-user-notification>';

		$page = preg_replace('/<body\b([^>]*)\s*?>/iU', '<body${1}>' . "\n" . $this->body, $page, 1);


		$this->style = $this->minicss($this->style);

		$this->style = preg_replace('/@keyframes\b[^{]*({((?:[^{}]+|(?1))*)})/i', '', $this->style);
		$this->style = preg_replace_callback('/@media\s([^{]*)({((?:[^{}]+|(?2))*)})/i', array($this, 'media_callback'), $this->style);
		$this->style = preg_replace('/@supports\b[^{]*({((?:[^{}]+|(?1))*)})/i', '', $this->style);

		if ( !$this->extened_style )
		{
			$this->collect_selector($page);

			$this->style = preg_replace_callback('/([^{]+)({((?:[^{}]+|(?2))*)})/i', array($this, 'css_callback'), $this->style);
		}

		if ( preg_match('/<amp-img\b[^>]*>/i', $page) )
		{
			$this->style .= $this->img_style;
		}

		if ( preg_match('/<amp-user-notification\b[^>]*>/i', $page) )
		{
			$this->style .= $this->notification_style;
		}

		if ( preg_match('/<amp-sidebar\b[^>]*>/i', $page) )
		{
			$this->style .= $this->sidebar_style;
		}


		// The mandatory tag 'meta charset=utf-8' is missing or incorrect.
		$this->head = '<meta charset="utf-8" />';

		// The mandatory tag 'meta name=viewport' is missing or incorrect.
		$this->head .= "\n" . '<meta name="viewport" content="width=device-width, minimum-scale=1, initial-scale=1" />';

		// The tag 'meta http-equiv=Content-Type' may only appear as a descendant of tag 'head'.
		$page = preg_replace_callback('/<meta\b([^>]*)\s*?\/?>/iU', array($this, 'meta_callback'), $page);

		// pwamp-page-type
		if ( !empty($this->page_type) )
		{
			$this->head .= "\n" . '<meta name="pwamp-page-type" content="' . $this->page_type . '" />';
		}

		// Progressive Web Apps
		$this->head .= "\n" . '<meta name="theme-color" content="#ffffff" />';
		$this->head .= "\n" . '<link rel="manifest" href="' . $this->home_url . '/' . ( !empty($this->permalink) ? 'manifest.webmanifest' : '?manifest.webmanifest' ) . '" />';
		$this->head .= "\n" . '<link rel="apple-touch-icon" href="' . $this->plugin_dir_url . 'pwamp/mf/mf-logo-192.png" />';

		$page = preg_replace_callback('/<title>(.*)<\/title>/iU', array($this, 'title_callback'), $page);

		// The mandatory tag 'amphtml engine v0.js script' is missing or incorrect.
		$this->head .= "\n" . '<link rel="preconnect" href="https://cdn.ampproject.org" />';
		$this->head .= "\n" . '<link rel="dns-prefetch" href="https://s.w.org" />';
		$this->head .= "\n" . '<link rel="preload" as="script" href="https://cdn.ampproject.org/v0.js" />';
		$this->head .= "\n" . '<script async src="https://cdn.ampproject.org/v0.js"></script>';

		$page = preg_replace_callback('/<link\b([^>]*) rel=(("((preconnect)|(dns-prefetch)|(preload)|(prerender)|(prefetch))")|(\'((preconnect)|(dns-prefetch)|(preload)|(prerender)|(prefetch))\'))([^>]*)\s*?\/?>/iU', array($this, 'link_callback'), $page);

		// The tag 'amp-audio' requires including the 'amp-audio' extension JavaScript.
		if ( preg_match('/<amp-audio\b[^>]*>/i', $page) )
		{
			$this->head .= "\n" . '<script async custom-element="amp-audio" src="https://cdn.ampproject.org/v0/amp-audio-0.1.js"></script>';
		}

		// The tag 'amp-fit-text' requires including the 'amp-fit-text' extension JavaScript.
		if ( preg_match('/<amp-fit-text\b[^>]*>/i', $page) )
		{
			$this->head .= "\n" . '<script async custom-element="amp-fit-text" src="https://cdn.ampproject.org/v0/amp-fit-text-0.1.js"></script>';
		}

		// The tag 'FORM [method=POST]' requires including the 'amp-form' extension JavaScript.
		// The tag 'FORM [method=GET]' requires including the 'amp-form' extension JavaScript.
		if ( preg_match('/<form\b[^>]*>/i', $page) )
		{
			$this->head .= "\n" . '<script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>';
		}

		// The tag 'amp-iframe' requires including the 'amp-iframe' extension JavaScript.
		if ( preg_match('/<amp-iframe\b[^>]*>/i', $page) )
		{
			$this->head .= "\n" . '<script async custom-element="amp-iframe" src="https://cdn.ampproject.org/v0/amp-iframe-0.1.js"></script>';
		}

		// The tag 'amp-install-serviceworker' requires including the 'amp-install-serviceworker' extension JavaScript.
		if ( preg_match('/<amp-install-serviceworker\b[^>]*>/i', $page) )
		{
			$this->head .= "\n" . '<script async custom-element="amp-install-serviceworker" src="https://cdn.ampproject.org/v0/amp-install-serviceworker-0.1.js"></script>';
		}

		// The tag 'amp-sidebar' requires including the 'amp-sidebar' extension JavaScript.
		if ( preg_match('/<amp-sidebar\b[^>]*>/i', $page) )
		{
			$this->head .= "\n" . '<script async custom-element="amp-sidebar" src="https://cdn.ampproject.org/v0/amp-sidebar-0.1.js"></script>';
			$this->head .= "\n" . '<script async custom-element="amp-bind" src="https://cdn.ampproject.org/v0/amp-bind-0.1.js"></script>';
		}

		// The tag 'amp-user-notification' requires including the 'amp-user-notification' extension JavaScript.
		if ( preg_match('/<amp-user-notification\b[^>]*>/i', $page) )
		{
			$this->head .= "\n" . '<script async custom-element="amp-user-notification" src="https://cdn.ampproject.org/v0/amp-user-notification-0.1.js"></script>';
		}

		// The tag 'amp-video' requires including the 'amp-video' extension JavaScript.
		if ( preg_match('/<amp-video\b[^>]*>/i', $page) )
		{
			$this->head .= "\n" . '<script async custom-element="amp-video" src="https://cdn.ampproject.org/v0/amp-video-0.1.js"></script>';
		}

		// amp-custom style
		if ( !empty($this->style) )
		{
			$this->style = str_replace('\\', '\\\\', $this->style);
			$this->head .= "\n" . '<style amp-custom>' . $this->style . '</style>';
		}

		$page = preg_replace('/<head>/i', '<head>' . "\n" . $this->head, $page, 1);


		$this->head = '';

		// The parent tag of tag 'link rel=stylesheet for fonts' is 'body', but it can only be 'head'.
		$page = preg_replace_callback('/<link\b([^>]*) rel=(("stylesheet")|(\'stylesheet\'))([^>]*)\s*?\/?>/iU', array($this, 'link2_callback'), $page);

		// The mandatory tag 'link rel=canonical' is missing or incorrect.
		$this->head .= '<link rel="canonical" href="' . $this->canonical . '" />';

		// The mandatory tag 'head > style[amp-boilerplate]' is missing or incorrect.
		// The mandatory tag 'noscript > style[amp-boilerplate]' is missing or incorrect.
		// The mandatory tag 'noscript enclosure for boilerplate' is missing or incorrect.
		$this->head .= "\n" . '<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>';

		$page = preg_replace('/<\/head>/i', $this->head . "\n" . '</head>', $page, 1);


		$page = preg_replace_callback('/<textarea\b([^>]*)>(.*)<\/textarea>/isU', array($this, 'textarea_callback'), $page);

		// Remove blank lines.
		$page = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', "\n", $page);

		// Remove end line spaces.
		$page = preg_replace('/[\s\t]+[\r\n]/', "\n", $page);

		$page = preg_replace_callback('/<textarea\b([^>]*)>(.*)<\/textarea>/isU', array($this, 'textarea2_callback'), $page);

		return $page;
	}


	private function css_callback($matches)
	{
		$match = $matches[1];
		$match2 = $matches[3];

		if ( preg_match('/^@/im', $match) )
		{
			return $match . '{' . $match2 . '}';
		}


		$selector = '';

		$elements = preg_split('/\s*,\s*/', $match);
		foreach ( $elements as $element )
		{
			$included = TRUE;

			$element2 = preg_replace('/\s*::?[a-z][a-z-]*(\([^\)]*\))?\s*/i', ';', $element);
			$element2 = preg_replace('/\s*\[[^\]]*\]\s*/i', ';', $element2);

			preg_match_all('/[\.#]?-?[_a-z]+[_a-z0-9-]*/i', $element2, $keys);
			foreach ( $keys[0] as $key )
			{
				if ( preg_match('/^-((moz)|(ms)|(webkit))/im', $key) )
				{
					continue;
				}
				elseif ( empty($this->selectors[$key]) )
				{
					$included = FALSE;

					break;
				}
			}

			if ( $included )
			{
				$selector .= ( !empty($selector) ? ',' : '' ) . $element;
			}
		}


		if ( empty($selector) )
		{
			return '';
		}

		return $selector . '{' . $match2 . '}';
	}

	private function media_callback($matches)
	{
		$match = $matches[1];
		$match2 = $matches[3];

		if ( empty($match2) )
		{
			return '';
		}

		if ( preg_match('/\bprint\b/i', $match) && !preg_match('/\bscreen\b/i', $match) )
		{
			return '';
		}


		if ( preg_match('/min-width:\s?(\d+)px/i', $match, $match3) )
		{
			$min_width = (int)$match3[1];
		}
		elseif ( preg_match('/min-width:\s?(\d+(\.\d+)?)em/i', $match, $match3) )
		{
			$min_width = (int)$match3[1] * 16;
		}

		if ( preg_match('/max-width:\s?(\d+)px/i', $match, $match3) )
		{
			$max_width = (int)$match3[1];
		}
		elseif ( preg_match('/max-width:\s?(\d+(\.\d+)?)em/i', $match, $match3) )
		{
			$max_width = (int)$match3[1] * 16;
		}

		if ( isset($min_width) && isset($max_width) )
		{
			if ( $this->viewport_width == 0 )
			{
				return '@media ' . $match . '{' . $match2 . '}';
			}
			elseif ( $this->viewport_width >= $min_width && $this->viewport_width <= $max_width )
			{
				return $match2;
			}
			else
			{
				return '';
			}
		}
		elseif ( isset($min_width) )
		{
			if ( $this->viewport_width == 0 )
			{
				return '@media ' . $match . '{' . $match2 . '}';
			}
			elseif ( $this->viewport_width >= $min_width )
			{
				return $match2;
			}
			else
			{
				return '';
			}
		}
		elseif ( isset($max_width) )
		{
			if ( $this->viewport_width == 0 )
			{
				return '@media ' . $match . '{' . $match2 . '}';
			}
			elseif ( $this->viewport_width <= $max_width )
			{
				return $match2;
			}
			else
			{
				return '';
			}
		}
		else
		{
			return '@media ' . $match . '{' . $match2 . '}';
		}
	}
}
