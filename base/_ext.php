<?php
/*
<NPT, a web development framework.>
Copyright (C) <2009>  <NPT>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('XFS')) exit;

class __ext extends xmd
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
	}
	
	public function home()
	{
		global $user;
		
		$v = $this->__(w('f e'));
		
		if (array_empty($v))
		{
			_fatal();
		}
		
		$location = './style/' . $v['e'] . '/';
		$filename = _filename($v['f'], $v['e']);
		
		if (!@is_dir($location))
		{
			_fatal();
		}
		
		if ($v['e'] == 'css' && $v['f'] != 'default')
		{
			$v['field'] = (!is_numb($v['f'])) ? 'alias' : 'id';
			
			$sql = 'SELECT *
				FROM _tree
				WHERE tree_?? = ?
				LIMIT 1';
			if (!$tree = _fieldrow(sql_filter($sql, $v['field'], $v['f'])))
			{
				_fatal();
			}
			
			$filetree = _rewrite($tree);
			$filename = _filename('_tree_' . $filetree, $v['e']);
		}
		
		//
		// 304 Not modified response header
		if (@file_exists($location . $filename))
		{
			$f_last_modified = gmdate('D, d M Y H:i:s', filemtime($location . $filename)) . ' GMT';
			$http_if_none_match = v_server('HTTP_IF_NONE_MATCH');
			$http_if_modified_since = v_server('HTTP_IF_MODIFIED_SINCE');
			
			header('Last-Modified: ' . $f_last_modified);
			
			if ($f_last_modified == $http_if_modified_since)
			{
				header('HTTP/1.0 304 Not Modified');
				header('Content-Length: 0');
				exit;
			}
		}
		
		switch ($v['e'])
		{
			case 'css':
				if ($v['f'] != 'default')
				{
					$filetree = _rewrite($tree);
					$filename = _filename('_tree_' . $filetree, $v['e']);
					
					if (!@file_exists($location . $filename))
					{
						_fatal();
					}
				}
				
				$browser = _browser();
				
				if (f($browser['browser']))
				{
					$custom = array($browser['browser'] . '-' . $browser['version'], $browser['browser']);
					
					foreach ($custom as $row)
					{
						$handler = _filename('_tree_' . $row, 'css');
						
						if (@file_exists($location . $handler))
						{
							_style('includes', array(
								'CSS' => _style_handler('css/' . $handler))
							);
						}
					}
				}
				break;
			case 'js':
				if (!@file_exists($location . $filename))
				{
					_fatal();
				}
				
				_style_vreplace(false);
				break;
		}
		
		v_style(array(
			'SPATH' => LIBD . 'visual')
		);
		sql_close();
		
		//
		// Headers
		$ext = _style_handler($v['e'] . '/' . $filename);
		
		switch ($v['e'])
		{
			case 'css':
				$content_type = 'text/css; charset=utf-8';
				
				$radius = array(
					'firefox' => '-moz-\1: \2',
					'chrome' => '-webkit-\1: \2'
				);
				
				$radius_v = '';
				foreach ($radius as $radius_app => $radius_val)
				{
					if (_browser($radius_app))
					{
						$radius_v = $radius_val;
						break;
					}
				}
				
				$ext = preg_replace('#(border-radius\-?.*?)\: ?(([0-9]+)px;)#is', ((f($radius_val)) ? $radius_val : ''), $ext);
				$ext = preg_replace('/(#([0-9A-Fa-f]{3})\b)/i', '#\2\2', $ext);
				$ext = preg_replace('#\/\*(.*?)\*\/#is', '', $ext);
				$ext = str_replace(array("\r\n", "\n", "\t"), '', $ext);
				break;
			case 'js':
				$content_type = 'application/x-javascript';
				
				if (($exit_code = strpos($ext, '[[EXIT]]')) !== false)
				{
					$ext = substr($ext, 0, $exit_code);
				}
				
				try {
					require_once(XFS . 'core/jsmin.php');
					//$ext = JSMin::minify($ext);
				} catch (Exception $e) { }
				
				break;
		}
		
		ob_start('ob_gzhandler');
		
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (60 * 60 * 24 * 30)) . ' GMT');
		header('Content-type: ' . $content_type);
		
		echo $ext;
		exit;
	}
}

?>