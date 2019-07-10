<?php
if ( !defined('IN_MYBB') )
{
	exit;
}

class PWAMP_TranscodingCommon
{
	protected $style;
	protected $home_url;
	protected $themes_url;
	protected $viewport_width;
	protected $permalink;
	protected $page_url;
	protected $canonical;


	public function __construct($home_url, $data)
	{
		$this->style = '.fixed-height-container{position:relative;width:100%;height:300px}amp-img.contain img{object-fit:contain}.revert{all:revert;display:inline}';

		$this->home_url = $home_url;

		if ( !empty($data['themes_url']) && is_string($data['themes_url']) )
		{
			$this->themes_url = $data['themes_url'];
		}
		else
		{
			$this->themes_url = '';
		}

		if ( !empty($data['viewport_width']) && is_string($data['viewport_width']) )
		{
			$this->viewport_width = (int)$data['viewport_width'];
		}
		else
		{
			$this->viewport_width = 0;
		}

		if ( !empty($data['permalink']) && is_string($data['permalink']) )
		{
			$this->permalink = $data['permalink'];
		}
		else
		{
			$this->permalink = '';
		}

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
			$canonical = preg_replace('/((\?)|(&(amp;)?))(amp|desktop)$/im', '', $this->page_url);
			$canonical .= ( strpos($canonical, '?') !== false ) ? '&desktop' : '?desktop';
			$this->canonical = htmlspecialchars($canonical);
		}
	}

	public function __destruct()
	{
	}


	protected function get_style(&$page)
	{
		if ( empty($this->style_list) || empty($this->themes_url) )
		{
			return;
		}

		$pattern = str_replace(array('/', '.'), array('\/', '\.'), $this->themes_url);
		if ( preg_match_all('/<link\b([^>]*)\s*?\/?>/iU', $page, $matches) )
		{
			foreach ( $matches[1] as $key => $value )
			{
				if ( preg_match('/ type=(("text\/css")|(\'text\/css\'))/i', $value) && preg_match('/ rel=(("stylesheet")|(\'stylesheet\'))/i', $value) && preg_match('/ href=(("([^"]*)")|(\'([^\']*)\'))/i', $value, $match) )
				{
					$key = !empty($match[2]) ? $match[3] : $match[5];
					$key = preg_replace('/^' . $pattern . '/im', '', $key);

					if ( !empty($this->style_list[$key]) )
					{
						$this->style .= $this->style_list[$key];
					}
				}
			}
		}
	}

	protected function minify_media()
	{
		if ( $this->viewport_width == 0 )
		{
			return;
		}

		preg_match_all('/@media\b([^{]+)\{([\s\S]+?\})\s*\}/', $this->style, $matches);
		foreach ( $matches[1] as $key => $value )
		{
			unset($min_width);
			if ( preg_match('/min-width:\s?(\d+)px/i', $value, $matches2) )
			{
				$min_width = (int)$matches2[1];
			}
			elseif ( preg_match('/min-width:\s?(\d+(\.\d+)?)em/i', $value, $matches2) )
			{
				$min_width = (int)$matches2[1] * 16;
			}

			unset($max_width);
			if ( preg_match('/max-width:\s?(\d+)px/i', $value, $matches2) )
			{
				$max_width = (int)$matches2[1];
			}
			elseif ( preg_match('/max-width:\s?(\d+(\.\d+)?)em/i', $value, $matches2) )
			{
				$max_width = (int)$matches2[1] * 16;
			}

			$value = str_replace(array('(', ')', '.', '/'), array('\(', '\)', '\.', '\/'), $value);
			if ( isset($min_width) && isset($max_width) )
			{
				if ( $this->viewport_width < $min_width || $this->viewport_width > $max_width )
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', '', $this->style, 1);
				}
				else
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', $matches[2][$key], $this->style, 1);
				}
			}
			elseif ( isset($min_width) )
			{
				if ( $this->viewport_width < $min_width )
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', '', $this->style, 1);
				}
				else
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', $matches[2][$key], $this->style, 1);
				}
			}
			elseif ( isset($max_width) )
			{
				if ( $this->viewport_width > $max_width )
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', '', $this->style, 1);
				}
				else
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', $matches[2][$key], $this->style, 1);
				}
			}
			else
			{
				$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', '', $this->style, 1);
			}
		}
	}

	protected function update_font_face()
	{
		$pattern = '/(@font-face{.*src:url\(data:application\/font-.+;charset=utf-8;base64,.+\).*})/iU';
		if ( preg_match_all($pattern, $this->style, $matches) )
		{
			$font_face = '';

			foreach ( $matches[1] as $value )
			{
				$font_face .= $value;
				$this->style = preg_replace($pattern, '', $this->style, 1);
			}

			$this->style .= $font_face;
		}
	}

	protected function minify_style($style, $id = '')
	{
		$style = !empty($id) ? $id . '{' . $style . '}' : $style;

		$style = preg_replace('/\/\*[^*]*\*+([^\/][^*]*\*+)*\//', '', $style);
		$style = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '   ', '    '), '', $style);
		$style = preg_replace('/\s*([{}:;,])\s*/i', '${1}', $style);
		$style = str_replace(';}', '}', $style);
		$style = preg_replace('/#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3/i','#$\1\2\3',$style);
		$style = preg_replace('/\s*!important\b\s*/i', '', $style);
		$style = trim($style);

		if ( preg_match('/{}$/im', $style) )
		{
			return;
		}

		return $style;
	}


	protected function update_image(&$page)
	{
		if ( empty($this->image_list) )
		{
			return;
		}

		$pattern = '/<img\b([^>]*)\s*?\/?>/iU';
		$pattern2 = str_replace(array('/', '.'), array('\/', '\.'), $this->home_url . '/');
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[1] as $key => $value )
			{
				if ( preg_match('/ (data-(lazy-)?)?src=(("([^"]*)")|(\'([^\']*)\'))/i', $value, $match) )
				{
					$key = !empty($match[4]) ? $match[5] : $match[7];
					$key = preg_replace('/^' . $pattern2 . '/im', '', $key);

					if ( !empty($this->image_list[$key]) )
					{
						$value .= $this->image_list[$key];
					}
				}

				$page = preg_replace($pattern, '<amp-img' . $value . ' />', $page, 1);
			}

			$page = preg_replace('/<amp-img\b([^>]*) \/>/i', '<img${1} />', $page);
		}
	}


	protected function transcode_html(&$page)
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
			<body></body>
		*/
		// Service Workers
		$pattern = str_replace(array('/', '.'), array('\/', '\.'), $this->home_url);
		if ( preg_match('/^' . $pattern . '\/(index\.php)?(\?amp)?$/im', $this->page_url) )
		{
			$serviceworker = '<amp-install-serviceworker
	src="' . $this->home_url . '/' . ( !empty($this->permalink) ? 'pwamp-sw-js' : '?pwamp-sw-js' ) . '"
	data-iframe-src="' . $this->home_url . '/' . ( !empty($this->permalink) ? 'pwamp-sw-html' : '?pwamp-sw-html' ) . '"
	layout="nodisplay">
</amp-install-serviceworker>';

			$page = preg_replace('/<\/body>/i', $serviceworker . "\n" . '</body>', $page, 1);
		}

		// Viewport Width
		if ( empty($this->viewport_width) )
		{
			$viewport_width = '<amp-pixel src="' . $this->home_url . '/?pwamp-viewport-width=VIEWPORT_WIDTH" layout="nodisplay"></amp-pixel>';

			$page = preg_replace('/<\/body>/i', $viewport_width . "\n" . '</body>', $page, 1);
		}


		/*
			<form></form>
		*/
		$pattern = '/<form\b([^>]*)\s*?>/iU';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[1] as $value )
			{
				if ( preg_match('/ method=(("post")|(\'post\'))/i', $value) )
				{
					// The attribute 'action' may not appear in tag 'FORM [method=POST]'.
					$value = preg_replace('/ action=(("([^"]*)")|(\'([^\']*)\'))/i', ' action-xhr="${3}${5}"', $value);

					// Invalid URL protocol 'http:' for attribute 'action-xhr' in tag 'FORM [method=POST]'.
					$value = preg_replace('/ action-xhr="http:\/\/([^"]*)"/i', ' action-xhr="https://${1}"', $value);
				}
				else
				{
					// The mandatory attribute 'action' is missing in tag 'FORM [method=GET]'.
					if ( !preg_match('/ action=(("[^"]*")|(\'[^\']*\'))/i', $value) )
					{
						$value .= ' action="' . $this->page_url . '"';
					}

					// The mandatory attribute 'target' is missing in tag 'FORM [method=GET]'.
					if ( !preg_match('/ target=(("[^"]*")|(\'[^\']*\'))/i', $value) )
					{
						$value .= ' target="_top"';
					}
				}

				$page = preg_replace($pattern, '<amp-form' . $value . '>', $page, 1);
			}

			$page = preg_replace('/<amp-form\b([^>]*)>/i', '<form${1}>', $page);
		}


		/*
			<html>
		*/
		// The attribute 'xml:lang' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xml:lang=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<html${1}${5}>', $page);

		// The attribute 'xmlns' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xmlns=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<html${1}${5}>', $page);

		$page = preg_replace('/<html\b([^>]*)>/i', '<html amp${1}>', $page, 1);


		/*
			<img/>
		*/
		// The tag 'img' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-img'?
		$pattern = '/<img\b([^>]*)\s*?\/?>/iU';
		$pattern2 = '/ class=(("([^"]*)")|(\'([^\']*)\'))/i';
		$pattern3 = '/ layout=(("[^"]*")|(\'[^\']*\'))/i';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[1] as $value )
			{
				if ( !preg_match('/ width=(("[^"]*")|(\'[^\']*\'))/i', $value) || !preg_match('/ height=(("[^"]*")|(\'[^\']*\'))/i', $value) )
				{
					if ( preg_match($pattern2, $value) )
					{
						$value = preg_replace($pattern2, ' class="${3}${5} contain"', $value);
					}
					else
					{
						$value .= ' class="contain"';
					}

					$value = preg_replace($pattern3, '', $value);
					$value .= ' layout="fill"';

					$page = preg_replace($pattern, '<div class="fixed-height-container"><amp-img' . $value . ' /></div>', $page, 1);
				}
				else
				{
					$value = preg_replace($pattern3, '', $value);
					$value .= ' layout="intrinsic"';

					$page = preg_replace($pattern, '<div class="revert"><amp-img' . $value . ' /></div>', $page, 1);
				}
			}
		}

		// The attribute 'border' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) border=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?\/?>/iU', '<amp-img${1}${5} />', $page);


		/*
			<link/>
		*/
		// The attribute 'href' in tag 'link rel=stylesheet for fonts' is set to the invalid value...
		$pattern = '/<link\b([^>]*)\s*?\/?>/iU';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[1] as $value )
			{
				if ( !preg_match('/ rel=(("stylesheet")|(\'stylesheet\'))/i', $value) )
				{
					$page = preg_replace($pattern, '<amp-link${1} />', $page, 1);

					continue;
				}

				if ( !preg_match('/ href=(("([^"]+)")|(\'([^\']+)\'))/i', $value, $match) )
				{
					$page = preg_replace($pattern, '<amp-link${1} />', $page, 1);

					continue;
				}

				$value = !empty($match[2]) ? $match[3] : $match[5];
				if ( preg_match('/^https:\/\/cloud\.typography\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<amp-link${1} />', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/fast\.fonts\.net\//im', $value) )
				{
					$page = preg_replace($pattern, '<amp-link${1} />', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/fonts\.googleapis\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<amp-link${1} />', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/use\.typekit\.net\//im', $value) )
				{
					$page = preg_replace($pattern, '<amp-link${1} />', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/maxcdn\.bootstrapcdn\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<amp-link${1} />', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/use\.fontawesome\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<amp-link${1} />', $page, 1);
				}
				else
				{
					$page = preg_replace($pattern, '', $page, 1);
				}
			}

			$page = preg_replace('/<amp-link\b([^>]*) \/>/i', '<link${1} />', $page);
		}


		/*
			<meta>
		*/
		// The mandatory tag 'meta charset=utf-8' is missing or incorrect.
		if ( !preg_match('/<meta\b[^>]* charset=(("utf-8")|(\'utf-8\'))[^>]*\s*?\/?>/i', $page) )
		{
			$page = preg_replace('/<head>/i', '<head>' . "\n" . '<meta charset="utf-8" />', $page);
		}

		// The attribute 'http-equiv' may not appear in tag 'meta name= and content='.
		$page = preg_replace('/<meta\b[^>]* http-equiv=(("refresh")|(\'refresh"\'))[^>]*\s*?\/?>/iU', '', $page);


		/*
			<script></script>
		*/
		// Custom JavaScript is not allowed.
		$page = preg_replace('/<script\b[^>]*>.*<\/script>/isU', '', $page);


		/*
			<style></style>
		*/
		// The mandatory attribute 'amp-custom' is missing in tag 'style amp-custom'.
		$pattern = '/(<noscript>)?<style\b[^>]*>(.*)<\/style>(<\/noscript>)?/isU';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[2] as $key => $value )
			{
				$this->style .= $this->minify_style($value);
			}

			$page = preg_replace($pattern, '', $page);
		}


		/*
			Any Tag
		*/
		// The attribute 'onclick' may not appear in tag 'a'.
		$pattern = '/<(\w+\b[^>]*) on\w+=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*(\s?)(\/?)>/iU';
		while ( preg_match($pattern, $page) )
		{
			$page = preg_replace($pattern, '<${1}${5}${6}${7}>', $page);
		}

		$pattern = '/<([^>]+) style=(("[^"]*")|(\'[^\']*\'))([^>]*)>/isU';
		$pattern2 = '/(("([^"]*)")|(\'([^\']*)\'))/i';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[2] as $key => $value )
			{
				if ( preg_match($pattern2, $value, $match) )
				{
					$value = !empty($match[2]) ? $match[3] : $match[5];
					$value = $this->minify_style($value);
				}

				$page = preg_replace($pattern, '<${1} style-amp="' . $value . '"${5}>', $page, 1);
			}

			$page = preg_replace('/<([^>]+) style-amp="([^"]*)"([^>]*)>/isU', '<${1} style="${2}"${3}>', $page);
		}
	}

	protected function transcode_head(&$page)
	{
		$pattern = '/<textarea\b([^>]*)>(.*)<\/textarea>/isU';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[2] as $key => $value )
			{
				$value = str_replace(array("\r\n", "\r", "\n"), '<amp-br />', $value);

				$page = preg_replace($pattern, '<amp-textarea${1}>' . $value . '</amp-textarea>', $page, 1);
			}

			$page = preg_replace('/<amp-textarea\b([^>]*)>(.*)<\/amp-textarea>/isU', '<textarea${1}>${2}</textarea>', $page);
		}

		// Remove blank lines.
		$page = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', "\n", $page);

		// Remove end line spaces.
		$page = preg_replace('/[\s\t]+[\r\n]/', "\n", $page);

		$pattern = '/<textarea\b([^>]*)>(.*)<\/textarea>/isU';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[2] as $key => $value )
			{
				$value = str_replace('<amp-br />', "\n", $value);

				$page = preg_replace($pattern, '<amp-textarea${1}>' . $value . '</amp-textarea>', $page, 1);
			}

			$page = preg_replace('/<amp-textarea\b([^>]*)>(.*)<\/amp-textarea>/isU', '<textarea${1}>${2}</textarea>', $page);
		}


		$pattern = '/<meta\b[^>]* name=(("viewport")|(\'viewport\'))[^>]*\s*?\/?>/iU';
		if ( !preg_match($pattern, $page) )
		{
			// The mandatory tag 'meta name=viewport' is missing or incorrect.
			$header = '<meta name="viewport" content="width=device-width, minimum-scale=1, initial-scale=1" />' . "\n";
		}
		else
		{
			$header = '';
		}

		// The mandatory tag 'amphtml engine v0.js script' is missing or incorrect.
		$header .= '<script async src="https://cdn.ampproject.org/v0.js"></script>';

		// The mandatory tag 'link rel=canonical' is missing or incorrect.
		$header .= "\n" . '<link rel="canonical" href="' . $this->canonical . '" />';

		// The mandatory tag 'noscript enclosure for boilerplate' is missing or incorrect.
		$header .= "\n" . '<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>';

		// Import amp-audio component.
		if ( preg_match('/<amp-audio\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-audio" src="https://cdn.ampproject.org/v0/amp-audio-0.1.js"></script>';
		}

		// Import amp-fit-text component.
		if ( preg_match('/<amp-fit-text\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-fit-text" src="https://cdn.ampproject.org/v0/amp-fit-text-0.1.js"></script>';
		}

		// The tag 'FORM [method=POST]' requires including the 'amp-form' extension JavaScript.
		// The tag 'FORM [method=GET]' requires including the 'amp-form' extension JavaScript.
		if ( preg_match('/<form\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>';
		}

		// Import amp-iframe component.
		if ( preg_match('/<amp-iframe\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-iframe" src="https://cdn.ampproject.org/v0/amp-iframe-0.1.js"></script>';
		}

		// Import amp-install-serviceworker component.
		if ( preg_match('/<amp-install-serviceworker\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-install-serviceworker" src="https://cdn.ampproject.org/v0/amp-install-serviceworker-0.1.js"></script>';
		}

		// Import amp-sidebar component.
		if ( preg_match('/<amp-sidebar\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-sidebar" src="https://cdn.ampproject.org/v0/amp-sidebar-0.1.js"></script>';
		}

		// Import amp-video component.
		if ( preg_match('/<amp-video\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-video" src="https://cdn.ampproject.org/v0/amp-video-0.1.js"></script>';
		}

		// amp-custom style
		if ( !empty($this->style) )
		{
			$header .= "\n" . '<style amp-custom>';
			$header .= "\n" . $this->style;
			$header .= "\n" . '</style>';
		}

		// Progressive Web App
		$pattern = str_replace(array('/', '.'), array('\/', '\.'), $this->home_url);
		if ( preg_match('/^' . $pattern . '\/(index\.php)?(\?amp)?$/im', $this->page_url) )
		{
			$header .= "\n" . '<link rel="manifest" href="' . $this->home_url . '/' . ( !empty($this->permalink) ? 'manifest.webmanifest' : '?manifest.webmanifest' ) . '" />';
			$header .= "\n" . '<meta name="theme-color" content="#ffffff" />';
		}

		$page = preg_replace('/<title>(.*)<\/title>/i', '<title>${1}</title>' . "\n" . $header, $page, 1);
	}
}
