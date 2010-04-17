<?php
class DynResize
{
	private $cfg = array();
	private $colors = array();
	private $size = array();
	private $ratio = array();
	private $style;
	private $type;
	private $img;

	public function __construct()
	{
		define('DYNRESIZE_VERSION','1.2');

		if(!defined('DYNRESIZE_ROOT'))				define('DYNRESIZE_ROOT','./');
		if(!defined('DYNRESIZE_STYLE'))				define('DYNRESIZE_STYLE','default');
		if(!defined('DYNRESIZE_STYLE_SOURCE'))		define('DYNRESIZE_STYLE_SOURCE',1);
		if(!defined('DYNRESIZE_STYLE_SOURCE_FILE'))	define('DYNRESIZE_STYLE_SOURCE_FILE','./dynresize-styles.xml');
		if(!defined('DYNRESIZE_CROP'))				define('DYNRESIZE_CROP',true);
		if(!defined('DYNRESIZE_BGCOLOR'))			define('DYNRESIZE_BGCOLOR','#000000');
		if(!defined('DYNRESIZE_EXTERNAL'))			define('DYNRESIZE_EXTERNAL',false);
		if(!defined('DYNRESIZE_EXTERNAL_CACHE'))	define('DYNRESIZE_EXTERNAL_CACHE',true);
		if(!defined('DYNRESIZE_EXTERNAL_CACHEDIR'))	define('DYNRESIZE_EXTERNAL_CACHEDIR','./dynresize/cache/.remote/');
		if(!defined('DYNRESIZE_ERROR_BGCOLOR'))	define('DYNRESIZE_ERROR_BGCOLOR','#FFEBEC');
		if(!defined('DYNRESIZE_ERROR_LINES'))		define('DYNRESIZE_ERROR_LINES',true);
		if(!defined('DYNRESIZE_ERROR_LINECOLOR'))	define('DYNRESIZE_ERROR_LINECOLOR','#FDDBD8');
		if(!defined('DYNRESIZE_CACHE'))				define('DYNRESIZE_CACHE',true);
		if(!defined('DYNRESIZE_CACHEDIR'))			define('DYNRESIZE_CACHEDIR','./dynresize/cache/');
		if(!defined('DYNRESIZE_SCALEUP'))			define('DYNRESIZE_SCALEUP',false);

		if(!isset($_GET['path']) && !defined('DYNRESIZE_ERROR')) define('DYNRESIZE_ERROR',true);

		if(isset($_GET['type']))					define('DYNRESIZE_MODE',1);
		elseif(isset($_GET['w'],$_GET['h']))		define('DYNRESIZE_MODE',2);
		elseif(isset($_GET['w']))					define('DYNRESIZE_MODE',3);
		elseif(isset($_GET['h']))					define('DYNRESIZE_MODE',4);
		elseif(!defined('DYNRESIZE_ERROR'))			define('DYNRESIZE_ERROR',true);

		if(
			!(isset($_GET['type'])
				|| (isset($_GET['w']) || isset($_GET['h'])))
			&& !defined('DYNRESIZE_ERROR')
		)											define('DYNRESIZE_ERROR',true);

		$this->loadStyles();
		$this->parseSourcePath();
		$this->parseConfig();
		$this->checkSource();
		$this->getSourceSize();
		$this->getOutputSize();
		if(DYNRESIZE_CACHE) $this->checkCache(); // Check whether the image has not been already generated. If it has, read the cached image.
		$this->loadSource();
		$this->loadOutput();
		$this->parseColors();
		$this->getResizedSize();
		$this->resize();
		if(isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] != '127.0.0.1') $this->IUseDynResize();
		$this->output();
	}

	private function checkCache()
	{
		if(!defined('DYNRESIZE_ERROR') && !isset($_GET['nocache']))
		{
			if(defined('DYNRESIZE_EXTERNAL_IMAGE') && DYNRESIZE_EXTERNAL_IMAGE)
			{
				if(DYNRESIZE_EXTERNAL_CACHE) $this->cfg['cache'] = DYNRESIZE_EXTERNAL_CACHEDIR . md5($this->cfg['path']) . '.' . $this->size['output'][0] . 'x' . $this->size['output'][1] . '.jpg';
				else $this->cfg['cache'] = 'nocache';
			}
			else
			{
				$this->cfg['cache'] = DYNRESIZE_CACHEDIR . basename($this->cfg['path']) . '.' . hash_file('md5',DYNRESIZE_ROOT . $this->cfg['path']) . '.' . $this->size['output'][0] . 'x' . $this->size['output'][1] . '.jpg';
			}

			if(file_exists($this->cfg['cache']))
			{
				header('content-type: image/jpeg');
				readfile($this->cfg['cache']);
				imagedestroy($this->img['source']);
				die();
			}
		}
	}

	private function parseSourcePath()
	{
		if(!defined('DYNRESIZE_ERROR'))
		{
			// Source path
			if(isset($_GET['path']))
			{
				preg_match('/^http:\/\/([^\/]+)\/(.*?)$/',$_GET['path'],$matches);
				if(isset($matches[1]) && $matches[1][0] != $_SERVER['SERVER_NAME'])
				{
					if(!DYNRESIZE_EXTERNAL) die('<strong>DynResize:</strong> Resizing of external images is not allowed!');
					else
					{
						$this->cfg['path'] = $_GET['path'];
						define('DYNRESIZE_EXTERNAL_IMAGE',true);
					}
				}
				else $this->cfg['path'] = DYNRESIZE_ROOT . $_GET['path'];
			}
			else define('DYNRESIZE_ERROR',true);
		}
	}

	private function parseConfig()
	{
		// Output style
		$this->cfg['style'] = DYNRESIZE_STYLE;

		// Output type
		if(isset($_GET['type'])) $this->cfg['type'] = $_GET['type'];

		// Error lines
		if(isset($_GET['lines'])) $this->cfg['lines'] = (boolean) $_GET['lines'];
		else $this->cfg['lines'] = DYNRESIZE_ERROR_LINES;

		// Error lines radius
		if(isset($_GET['radius'])) $this->cfg['radius'] = (int) $_GET['radius'];
		elseif(!defined('DYNRESIZE_ERROR_RADIUS')) $this->cfg['radius'] = 0;
		else $this->cfg['radius'] = DYNRESIZE_ERROR_RADIUS;

		// Output quality
		if(isset($_GET['quality'])) $this->cfg['quality'] = (int) $_GET['quality'];
		elseif(defined('DYNRESIZE_QUALITY')) $this->cfg['quality'] = DYNRESIZE_QUALITY;
		elseif(isset($this->type->quality[0])) $this->cfg['quality'] = (int) $this->type->quality[0];
		else $this->cfg['quality'] = 60;

		// Output background
		if(isset($_GET['bgcolor'])) $this->cfg['bgcolor'] = '#'.$_GET['bgcolor'];
		else $this->cfg['bgcolor'] = DYNRESIZE_BGCOLOR;
	}

	private function parseColors()
	{
		$this->colors['bg'] = sscanf($this->cfg['bgcolor'],'#%2x%2x%2x');
		$this->colors['bg'] = imagecolorallocate($this->img['output'],$this->colors['bg'][0],$this->colors['bg'][1],$this->colors['bg'][2]);

		// Error colors
		$this->colors['error_bg'] = sscanf(DYNRESIZE_ERROR_BGCOLOR,'#%2x%2x%2x');
		$this->colors['error_bg'] = imagecolorallocate($this->img['output'],$this->colors['error_bg'][0],$this->colors['error_bg'][1],$this->colors['error_bg'][2]);
		$this->colors['error_lines'] = sscanf(DYNRESIZE_ERROR_LINECOLOR,'#%2x%2x%2x');
		$this->colors['error_lines'] = imagecolorallocate($this->img['output'],$this->colors['error_lines'][0],$this->colors['error_lines'][1],$this->colors['error_lines'][2]);
	}

	private function loadStyles()
	{
		if(DYNRESIZE_STYLE_SOURCE == 1)
		{
			if(file_exists(DYNRESIZE_STYLE_SOURCE_FILE))
			{
				$xml = simplexml_load_file(DYNRESIZE_STYLE_SOURCE_FILE);

				$this->style = $xml->xpath('style[@codename="' . DYNRESIZE_STYLE . '"]');
				$this->style = $this->style[0];
				$this->type = $this->style->xpath('types/type[@codename="' . $_GET['type'] . '"]');
				$this->type = $this->type[0];
			}
		}
	}

	private function loadSource()
	{
		if(isset($_GET['path']))
		{
			if(!$this->loadImage('source',$this->cfg['path']) && !defined('DYNRESIZE_ERROR')) define('DYNRESIZE_ERROR',true);
		}
	}

	private function checkSource()
	{
		if(!defined('DYNRESIZE_ERROR') && isset($_GET['path']))
		{
			if(defined('DYNRESIZE_EXTERNAL_IMAGE') && DYNRESIZE_EXTERNAL_IMAGE)
			{
				$h = get_headers($this->cfg['path']);
				if(!preg_match('|200|',$h[0])) define('DYNRESIZE_ERROR',true);
			}
			elseif(!file_exists($this->cfg['path'])) define('DYNRESIZE_ERROR',true);
		}
	}

	private function loadImage($codename,$path)
	{
		/*if($codename == 'source' && defined('DYNRESIZE_EXTERNAL_IMAGE') && DYNRESIZE_EXTERNAL_IMAGE)
		{
			$h = get_headers($path);
			if(!preg_match('|200|',$h[0])) return(false);
		}
		else*/if(!defined('DYNRESIZE_EXTERNAL_IMAGE') && !file_exists($path)) return(false);

		$ext = end(explode('.',$path));
		switch(strtolower($ext))
		{
			case('jpg'):case('jpeg'): $this->img[$codename] = imagecreatefromjpeg($path); break;
			case('png'): $this->img[$codename] = imagecreatefrompng($path); break;
			case('gif'): $this->img[$codename] = imagecreatefromgif($path); break;
			default: if(!defined('DYNRESIZE_ERROR')) define('DYNRESIZE_ERROR',true); break;
		}

		$this->size[$codename] = getimagesize($path);
		return($this->img[$codename]);
	}

	private function loadOutput()
	{
		$this->img['output'] = imagecreatetruecolor($this->size['output'][0],$this->size['output'][1]);
	}

	private function getSourceSize()
	{
		if(!defined('DYNRESIZE_ERROR') && isset($_GET['path']))
		{
			$this->size['source'] = getimagesize($this->cfg['path']);
			$this->ratio['source'] = $this->size['source'][0] / $this->size['source'][1];
		}
		else
		{
			$this->size['source'] = array(128,80);
			$this->ratio['source'] = 128/80;
		}
	}

	private function getOutputSize()
	{
		switch(DYNRESIZE_MODE)
		{
			case(1):
				if($this->type != NULL)
				{
					if(!isset($this->type->width[0]) && !isset($this->type->height[0]) && !defined('DYNRESIZE_ERROR'))
					{
						define('DYNRESIZE_ERROR',true);
					}
					elseif(isset($this->type->width[0],$this->type->height[0]))
					{
						$this->size['output'][0] = (int) $this->type->width[0];
						$this->size['output'][1] = (int) $this->type->height[0];
					}
					elseif(isset($this->type->width[0]))
					{
						$this->size['output'][0] = (int) $this->type->width[0];
						$this->size['output'][1] = $this->getOutputHeight($this->size['output'][0]);
						if($this->size['output'][1] > $this->size['source'][1]) $this->size['output'][1] = $this->size['source'][1];
					}
					else
					{
						$this->size['output'][1] = (int) $this->type->height[0];
						$this->size['output'][0] = $this->getOutputWidth($this->size['output'][1]);
						if($this->size['output'][0] > $this->size['source'][0]) $this->size['output'][0] = $this->size['source'][0];
					}
				}
				else
				{
					if(!defined('DYNRESIZE_ERROR')) define('DYNRESIZE_ERROR',true);
					$this->size['output'][0] = 256;
					$this->size['output'][1] = 160;
				}
				break;
			case(2):
				$this->size['output'][0] = (int) $_GET['w'];
				$this->size['output'][1] = (int) $_GET['h'];
				break;
			case(3):
				$this->size['output'][0] = (int) $_GET['w'];
				$this->size['output'][1] = $this->getOutputHeight($this->size['output'][0]);
				if($this->size['output'][1] > $this->size['source'][1]) $this->size['output'][1] = $this->size['source'][1];
				break;
			case(4):
				$this->size['output'][1] = (int) $_GET['h'];
				$this->size['output'][0] = $this->getOutputWidth($this->size['output'][1]);
				if($this->size['output'][0] > $this->size['source'][0]) $this->size['output'][0] = $this->size['source'][0];
				break;
			default:
				if(!defined('DYNRESIZE_ERROR')) define('DYNRESIZE_ERROR',true);
				$this->size['output'][0] = 256;
				$this->size['output'][1] = 160;
				break;
		}

		$this->ratio['output'] = $this->size['output'][0] / $this->size['output'][1];
	}

	private function getOutputWidth($height)
	{
		if($this->size['source'][1] == NULL) return(256);
		return(floor(($this->size['source'][0] * $height) / $this->size['source'][1]));
	}
	private function getOutputHeight($width)
	{
		if($this->size['source'][0] == NULL) return(160);
		return(floor(($this->size['source'][1] * $width) / $this->size['source'][0]));
	}

	private function getResizedSize()
	{
		if(!defined('DYNRESIZE_ERROR'))
		{
			if($this->ratio['source'] / $this->ratio['output'] == 1)
			{
				$this->size['resized'] = array($this->size['output'][0],$this->size['output'][1]);
				$this->size['resized'][2] = array(0,0);
			}
			else
			{
				if(!DYNRESIZE_CROP) // There will be some stripes on edges.
				{
					if($this->ratio['source'] / $this->ratio['output'] < 1)
					{
						$this->size['resized'] = array($this->getOutputWidth($this->size['output'][1]),$this->size['output'][1]);
						$this->size['resized'][2] = array(($this->size['output'][0] - $this->size['resized'][0]) / 2,0);
					}
					else
					{
						$this->size['resized'] = array($this->size['output'][0],$this->getOutputHeight($this->size['output'][0]));
						$this->size['resized'][2] = array(0,($this->size['output'][1] - $this->size['resized'][1]) / 2);
					}
				}
				else
				{
					if($this->ratio['source'] / $this->ratio['output'] > 1)
					{
						$this->size['resized'] = array($this->getOutputWidth($this->size['output'][1]),$this->size['output'][1]);
						$this->size['resized'][2] = array(($this->getOutputHeight($this->size['output'][0]) - $this->size['output'][1]) / 2,0);

						if(!DYNRESIZE_SCALEUP)
						{
							// Check whether the source height is not smaller than the resized source height.
							if($this->size['source'][1] < $this->size['resized'][1])
							{
								$this->size['resized'] = array($this->size['source'][0],$this->size['source'][1]);
								$this->size['resized'][2] = array(($this->size['output'][0] - $this->size['source'][0]) / 2,($this->size['output'][1] - $this->size['source'][1]) / 2);
							}
						}
					}
					else
					{
						$this->size['resized'] = array($this->size['output'][0],$this->getOutputHeight($this->size['output'][0]));
						$this->size['resized'][2] = array(0,($this->getOutputWidth($this->size['output'][1]) - $this->size['output'][0]) / 2);

						if(!DYNRESIZE_SCALEUP)
						{
							// Check whether the source width is not smaller than the resized source width.
							if($this->size['source'][0] < $this->size['resized'][0])
							{
								$this->size['resized'] = array($this->size['source'][0],$this->size['source'][1]);
								$this->size['resized'][2] = array(($this->size['output'][0] - $this->size['source'][0]) / 2,($this->size['output'][1] - $this->size['source'][1]) / 2);
							}
						}
					}
				}
			}
		}
	}

	private function resize()
	{
		if(!defined('DYNRESIZE_ERROR'))
		{
			imagefill($this->img['output'],0,0,$this->colors['bg']);

			if($this->size['output'][0] > $this->size['source'][0] && $this->size['output'][1] > $this->size['source'][1])
			{
				imagecopy(
					$this->img['output'],$this->img['source'],
					floor(($this->size['output'][0] - $this->size['source'][0]) / 2),floor(($this->size['output'][1] - $this->size['source'][1]) / 2),
					0,0,
					$this->size['source'][0],$this->size['source'][1]
				);
			}
			else
			{
				imagecopyresampled(
					$this->img['output'],$this->img['source'],
					$this->size['resized'][2][0],$this->size['resized'][2][1],
					0,0,
					$this->size['resized'][0],$this->size['resized'][1],
					$this->size['source'][0],$this->size['source'][1]
				);
			}
		}
		else
		{
			imagefill($this->img['output'],0,0,$this->colors['error_bg']);
			if($this->cfg['lines'])
			{
				imageantialias($this->img['output'],true);
				imageline(
					$this->img['output'],
					$this->cfg['radius'],$this->cfg['radius'],
					$this->size['output'][0]-$this->cfg['radius'],$this->size['output'][1]-$this->cfg['radius'],
					$this->colors['error_lines']
				);
				imageline(
					$this->img['output'],
					$this->size['output'][0]-$this->cfg['radius'],$this->cfg['radius'],
					$this->cfg['radius'],$this->size['output'][1]-$this->cfg['radius'],
					$this->colors['error_lines']
				);
				imageantialias($this->img['output'],false);
			}

			if(defined('DYNRESIZE_ERROR_IMAGE') && file_exists(DYNRESIZE_ERROR_IMAGE))
			{
				if($this->loadImage('error',DYNRESIZE_ERROR_IMAGE))
				{
					imagecopy(
						$this->img['output'],$this->img['error'],
						floor(($this->size['output'][0] - $this->size['error'][0]) / 2),floor(($this->size['output'][1] - $this->size['error'][1]) / 2),
						0,0,
						$this->size['error'][0],$this->size['error'][1]
					);
				}
			}
		}
	}

	private function output()
	{
		if(!defined('DYNRESIZE_ERROR'))
		{
			header('content-type: image/jpeg');
			imagejpeg($this->img['output'],NULL,$this->cfg['quality']);
			if(DYNRESIZE_CACHE) imagejpeg($this->img['output'],$this->cfg['cache'],$this->cfg['quality']);
			imagedestroy($this->img['output']);
			imagedestroy($this->img['source']);
		}
		else
		{
			header('content-type: image/png');
			imagepng($this->img['output']);
			imagedestroy($this->img['output']);
			imagedestroy($this->img['error']);
		}
	}

	function IUseDynResize()
	{
		@file_get_contents('http://dynresize.blackpig.cz/i-use-dynresize.php?siteurl='.$_SERVER['SERVER_NAME']);
	}
}
?>