<?php
if ( !defined('ABSPATH') )
{
	exit;
}

class PWAMPTranscodingCommon
{
	protected $style;
	protected $home_url;
	protected $page_type;
	protected $themes_url;
	protected $plugins_url;
	protected $viewport_width;
	protected $permalink;
	protected $page_url;
	protected $canonical;


	public function __construct()
	{
	}

	public function __destruct()
	{
	}


	protected function init($home_url, $data)
	{
		$this->style = '.fixed-height-container{position:relative;width:100%;height:300px}amp-img.contain img{object-fit:contain}.revert{all:revert;display:inline}';

		$this->home_url = $home_url;

		if ( !empty($data['page_type']) && is_string($data['page_type']) )
		{
			$this->page_type = $data['page_type'];
		}
		else
		{
			$this->page_type = '';
		}

		if ( !empty($data['themes_url']) && is_string($data['themes_url']) )
		{
			$this->themes_url = $data['themes_url'];
		}
		else
		{
			$this->themes_url = '';
		}

		if ( !empty($data['plugins_url']) && is_string($data['plugins_url']) )
		{
			$this->plugins_url = $data['plugins_url'];
		}
		else
		{
			$this->plugins_url = '';
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
			$canonical = preg_replace('/((\?)|(&(amp;)?))(amp|desktop)(=1)?$/im', '', $this->page_url);
			$canonical .= ( strpos($canonical, '?') !== false ) ? '&desktop=1' : '?desktop=1';
			$this->canonical = htmlspecialchars($canonical);
		}
	}


	protected function get_style()
	{
		if ( !empty($this->style_list[$this->page_type]) )
		{
			$this->style .= $this->style_list[$this->page_type];
		}
		elseif ( !empty($this->style_list['default']) )
		{
			$this->style .= $this->style_list['default'];
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
			<a></a>
		*/
		// The attribute 'alt' may not appear in tag 'a'.
		$page = preg_replace('/<a\b([^>]*) alt=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<a${1}${5}>', $page);


		/*
			<area/>
		*/
		// The tag 'area' is disallowed.
		$page = preg_replace('/<area\b([^>]*)\s*?\/?>/iU', '', $page);


		/*
			<audio></audio>
		*/
		// The tag 'audio' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-audio'?
		$page = preg_replace('/<audio\b([^>]*)>/i', '<amp-audio${1}>', $page);


		/*
			<body></body>
		*/
		// Service Workers
		$pattern = str_replace(array('/', '.'), array('\/', '\.'), $this->home_url);
		if ( preg_match('/^' . $pattern . '\/(index\.php)?(\?amp(=1)?)?$/im', $this->page_url) )
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

		// Pixel
		$pattern = '/<noscript><img height="1" width="1".* src="([^"]+)".*\/><\/noscript>/isU';
		while ( preg_match($pattern, $page, $match) )
		{
			$page = preg_replace('/<\/body>/i', '<amp-pixel src="' . $match[1] . '" layout="nodisplay"></amp-pixel>' . "\n" . '</body>', $page, 1);
			$page = preg_replace($pattern, '', $page, 1);
		}


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
		$page = preg_replace('/<font[^>]*>/i', '', $page);
		$page = preg_replace('/<\/font>/i', '', $page);


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

		$page = preg_replace('/<html\b([^>]*)>/i', '<html amp${1}>', $page, 1);


		/*
			<icon></icon>
		*/
		$page = preg_replace('/<icon class="([^"]+)"><\/icon>/i', '<div class="${1}"></div>', $page);


		/*
			<iframe></iframe>
		*/
		$page = preg_replace('/<iframe\b([^>]*) src=(("[^"]*")|(\'[^\']*\'))([^>]*) data-lazy-src=(("([^"]*)")|(\'([^\']*)\'))([^>]*)\s*?\/?>/iU', '<iframe${1} src="${8}${10}"${5}${11} />', $page);

		// The tag 'iframe' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-iframe'?
		$page = preg_replace('/<iframe\b([^>]*)>/i', '<amp-iframe${1}>', $page);

		// Invalid URL protocol 'http:' for attribute 'src' in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) src=(("http:\/\/([^"]*)")|(\'http:\/\/([^\']*)\'))([^>]*)>/i', '<amp-iframe${1} src="https://${4}${6}"${7}>', $page);

		// The attribute 'align' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) align=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);

		// The attribute 'allowtransparency' in tag 'amp-iframe' is set to the invalid value 'true'.
		$page = preg_replace('/<amp-iframe\b([^>]*) allowtransparency=(("true")|(\'true\'))([^>]*)>/i', '<amp-iframe${1} allowtransparency="allowtransparency"${5}>', $page);

		// The attribute 'frameborder' in tag 'amp-iframe' is set to the invalid value 'no'.
		$page = preg_replace('/<amp-iframe\b([^>]*) frameborder=(("no")|(\'no\'))([^>]*)>/i', '<amp-iframe${1} frameborder="0"${5}>', $page);

		// The attribute 'marginheight' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) marginheight=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);

		// The attribute 'marginwidth' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) marginwidth=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);

		// The attribute 'mozallowfullscreen' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) mozallowfullscreen\b(=(("[^"]*")|(\'[^\']*\')))?([^>]*)\s*?>/iU', '<amp-iframe${1}${6}>', $page);

		// The attribute 'name' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) name=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<amp-iframe${1}${5}>', $page);

		// The attribute 'webkitallowfullscreen' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) webkitallowfullscreen\b(=(("[^"]*")|(\'[^\']*\')))?([^>]*)\s*?>/iU', '<amp-iframe${1}${6}>', $page);


		/*
			<img/>
		*/
		$page = preg_replace('/<img\b([^>]*) src=(("\/?")|(\'\/?\'))([^>]*)\s*?\/?>/iU', '', $page);

		$page = preg_replace('/<img\b([^>]*) src=(("[^"]*")|(\'[^\']*\'))([^>]*) data-lazy-src=(("([^"]*)")|(\'([^\']*)\'))([^>]*)\s*?\/?>/iU', '<img${1} src="${8}${10}"${5}${11} />', $page);
		$page = preg_replace('/<img\b([^>]*) src=(("[^"]+(data:image\/gif;base64,[^"]+)")|(\'[^\']+(data:image\/gif;base64,[^\']+)\'))([^>]*)\s*?\/?>/iU', '<img${1} src="${4}${6}"${7} />', $page);
		$page = preg_replace('/<img\b([^>]*) src=(("\/\/([^"]+)")|(\'\/\/([^\']+)\'))([^>]*)\s*?\/?>/iU', '<img${1} src="https://${4}${6}"${7} />', $page);

		$page = preg_replace('/<img\b([^>]*) width=(("\d+%")|(\'\d+%\'))([^>]*)\s*?\/?>/iU', '<img${1}${2} />', $page);
		$page = preg_replace('/<img\b([^>]*) height=(("\d+%")|(\'\d+%\'))([^>]*)\s*?\/?>/iU', '<img${1}${2} />', $page);

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
		// The attribute 'tooltip' may not appear in tag 'input'.
		$page = preg_replace('/<input\b([^>]*) tooltip=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<input${1}${5}>', $page);


		/*
			<link/>
		*/
		// The tag 'link rel=canonical' appears more than once in the document.
		$page = preg_replace('/<link rel="canonical" href="[^"]+"\s*\/?>/i', '', $page);

		$page = preg_replace('/<link rel=\'dns-prefetch\' href=\'(\/\/[^\']+)\'\s*\/?>/i', '<link rel="dns-prefetch" href="https:${1}">', $page);

		$page = preg_replace('/<link\b([^>]*) rel=(("stylesheet")|(\'stylesheet\'))([^>]*)\s? href=(("(\/\/[^"]+)")|(\'(\/\/[^\']+)\'))([^>]*)\s*?\/?>/iU', '<link${1} rel="stylesheet"${5} href="https:${8}${10}"${11}>', $page);

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

		// The parent tag of tag 'link rel=stylesheet for fonts' is 'body', but it can only be 'head'.
		$pattern = '/<body([^>]*)>(.*)(<link rel="stylesheet"[^>]*>)(.*)<\/body>/is';
		while ( preg_match($pattern, $page, $match) )
		{
			$page = preg_replace($pattern, '<body${1}>${2}${4}</body>', $page);
			$page = preg_replace('/<\/head>/i', $match[3] . "\n" . '</head>', $page);
		}

		$page = preg_replace('/^[\s\t]*<link\b([^>]*)>/im', '<link${1}>', $page);


		/*
			<map></map>
		*/
		// The tag 'map' is disallowed.
		$page = preg_replace('/<map\b[^>]*>.*<\/map>/isU', '', $page);


		/*
			<meta>
		*/
		// The mandatory tag 'meta charset=utf-8' is missing or incorrect.
		if ( !preg_match('/<meta\b[^>]* charset=(("utf-8")|(\'utf-8\'))[^>]*\s*?\/?>/i', $page) )
		{
			$page = preg_replace('/<head>/i', '<head>' . "\n" . '<meta charset="utf-8" />', $page);
		}

		// The tag 'meta http-equiv=Content-Type' may only appear as a descendant of tag 'head'.
		$pattern = '/<body([^>]*)>(.*)(<meta http-equiv="Content-Type"[^>]*>)(.*)<\/body>/is';
		if ( preg_match($pattern, $page, $match) )
		{
			$page = preg_replace($pattern, '<body${1}>${2}${4}</body>', $page);
			$page = preg_replace('/<head>/i', '<head>' . "\n" . $match[3], $page);
		}

		// The attribute 'content' in tag 'meta http-equiv=Content-Type' is set to the invalid value 'text/html;charset=utf-8'.
		$page = preg_replace('/<meta http-equiv="Content-Type" content="text\/html;\s?charset=[^"]*"\s*\/?>/i', '', $page);

		// The attribute 'http-equiv' may not appear in tag 'meta name= and content='.
		$page = preg_replace('/<meta\b[^>]* http-equiv=(("refresh")|(\'refresh"\'))[^>]*\s*?\/?>/iU', '', $page);

		// The attribute 'name' in tag 'meta name= and content=' is set to the invalid value 'revisit-after'.
		$page = preg_replace('/<meta\b[^>]* name=(("revisit-after")|(\'revisit-after\'))[^>]*\s*?\/?>/iU', '', $page);

		// The property 'minimum-scale' is missing from attribute 'content' in tag 'meta name=viewport'.
		$page = preg_replace('/<meta\b([^>]*) name=(("viewport")|(\'viewport\'))([^>]*) content=(("[^"]*width\s*=\s*device-width[^"]*")|(\'[^\']*width\s*=\s*device-width[^\']*\'))([^>]*)\s*?\/?>/iU', '<meta${1} name="viewport"${5} content="width=device-width, minimum-scale=1, initial-scale=1"${9} />', $page);

		$page = preg_replace('/^[\s\t]*<meta\b([^>]*)>/im', '<meta${1}>', $page);


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
		$page = preg_replace('/<span\b([^>]*) temscope\b(=(("[^"]*")|(\'[^\']*\')))?([^>]*)\s*?>/iU', '<span${1}${6}>', $page);


		/*
			<style></style>
		*/
		// The mandatory attribute 'amp-custom' is missing in tag 'style amp-custom'.
		$page = preg_replace('/(<noscript>)?<style\b[^>]*>.*<\/style>(<\/noscript>)?/isU', '', $page);


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
			<title></title>
		*/
		$page = preg_replace('/^[\s\t]*<title>(.*)<\/title>/im', '<title>${1}</title>', $page, 1);


		/*
			<time></time>
		*/
		// The attribute 'pubdate' may not appear in tag 'time'.
		$page = preg_replace('/<time\b([^>]*) pubdate\b(=(("[^"]*")|(\'[^\']*\')))?([^>]*)\s*?>/iU', '<time${1}${6}>', $page);


		/*
			<ul></ul>
		*/
		// The attribute 'featured_post_id' may not appear in tag 'ul'.
		$page = preg_replace('/<ul\b([^>]*) featured_post_id=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<ul${1}${5}>', $page);


		/*
			<video></video>
		*/
		// The tag 'video' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-video'?
		$page = preg_replace('/<video\b([^>]*)>/i', '<amp-video${1}>', $page);


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

		// Progressive Web Apps
		$pattern = str_replace(array('/', '.'), array('\/', '\.'), $this->home_url);
		if ( preg_match('/^' . $pattern . '\/(index\.php)?(\?amp(=1)?)?$/im', $this->page_url) )
		{
			$header .= "\n" . '<link rel="manifest" href="' . $this->home_url . '/' . ( !empty($this->permalink) ? 'manifest.webmanifest' : '?manifest.webmanifest' ) . '" />';
			$header .= "\n" . '<meta name="theme-color" content="#ffffff" />';
			$header .= "\n" . '<link rel="apple-touch-icon" href="' . $this->plugins_url . '/pwamp/mf/mf-logo-192.png" />';
		}

		if ( !empty($this->page_type) )
		{
			$header .= "\n" . '<meta name="pwamp-page-type" content="' . $this->page_type . '" />';
		}

		$page = preg_replace('/<title>(.*)<\/title>/i', '<title>${1}</title>' . "\n" . $header, $page, 1);
	}
}
