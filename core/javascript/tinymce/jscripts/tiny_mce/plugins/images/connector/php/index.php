<?php

include 'config.php';

class TinyImageManager {
	
	var $dir;
	var $firstAct = false;
	var $folderAct = false;
	var $ALLOWED_IMAGES;
	var $ALLOWED_FILES;
	var $SID;
	
	/**
	 * РљРѕРЅСЃС‚СЂСѓРєС‚РѕСЂ
	 *
	 * @return TinyImageManager
	 */
	function TinyImageManager() {
		error_reporting(E_ALL);
		ob_start("ob_gzhandler");
		header('Content-Type: text/html; charset=utf-8');
		
		if(isset($_POST['SID'])) session_id($_POST['SID']);
		if(!isset($_SESSION)) session_start();
		$this->SID = session_id();
		require 'yoursessioncheck.php';
		
		if(!isset($_SESSION['tiny_image_manager_path'])) $_SESSION['tiny_image_manager_path'] = '';
		
		$this->ALLOWED_IMAGES = array('jpeg','jpg','gif','png');
		$this->ALLOWED_FILES = array('doc','docx','ppt','pptx','xls','xlsx','mdb','accdb', 'swf', 'zip', 'rar', 'rtf', 'pdf', 'psd', 'mp3', 'wma' ,'txt');
		
		$this->dir = array(
			'images'	=> realpath(DIR_ROOT.DIR_IMAGES),
			'files'		=> realpath(DIR_ROOT.DIR_FILES)
		);
		
		require 'image_toolbox.class.php';
		require 'exifreader.php';
		
		switch ($_POST['action']) {
			
			//РЎРѕР·РґР°С‚СЊ РїР°РїРєСѓ
			case 'newfolder':
				
				$result = array();
				$dir = $this->AccessDir($_POST['path'], $_POST['type']);
				if($dir) {
					if (preg_match('/[a-z0-9-_]+/sim', $_POST['name'])) {
						if(is_dir($dir.'/'.$_POST['name'])) {
							$result['error'] = 'РўР°РєР°СЏ РїР°РїРєР° СѓР¶Рµ РµСЃС‚СЊ';
						} else {
							if(mkdir($dir.'/'.$_POST['name'])) {
								$result['tree']  = $this->DirStructure('images', 'first', $dir.'/'.$_POST['name']);
								$result['tree'] .= $this->DirStructure('files', 'first', $dir.'/'.$_POST['name']);
								$result['addr'] = $this->DirPath($_POST['type'], $this->AccessDir($_POST['path'].'/'.$_POST['name'], $_POST['type']));
								$result['error'] = '';
							} else {
								$result['error'] = 'РћС€РёР±РєР° СЃРѕР·РґР°РЅРёСЏ РїР°РїРєРё';
							}
						}
					} else {
						$result['error'] = 'РќР°Р·РІР°РЅРёРµ РїР°РїРєРё РјРѕР¶РµС‚ СЃРѕРґРµСЂР¶Р°С‚СЊ С‚РѕР»СЊРєРѕ Р»Р°С‚РёРЅСЃРєРёРµ Р±СѓРєРІС‹, С†РёС„СЂС‹, С‚РёСЂРµ Рё Р·РЅР°Рє РїРѕРґС‡РµСЂРєРёРІР°РЅРёСЏ';
					}
				} else {
					$result['error'] = 'РћС‚РєР°Р· РІ РґРѕСЃС‚СѓРїРµ';
				}
				
				echo "{'tree':'{$result['tree']}', 'addr':'{$result['addr']}', 'error':'{$result['error']}'}";
				exit();
				
			break;
			
			//РџРѕРєР°Р·Р°С‚СЊ РґРµСЂРµРІРѕ РїР°РїРѕРє
			case 'showtree':
				if(!isset($_POST['path'])) $_POST['path'] = '';
				if(!isset($_POST['type'])) $_POST['type'] = '';
				
				if($_POST['path'] == '/') $_POST['path'] = '';
				
				if(isset($_POST['default']) && isset($_SESSION['tiny_image_manager_path'])) $path = $_SESSION['tiny_image_manager_path'];
				else $path = $_SESSION['tiny_image_manager_path'] = $_POST['path'];
				
				if($_POST['type']=='files') $this->firstAct = true;
				if($_POST['type']=='files') echo $this->DirStructure('images', 'first');
				else 						echo $this->DirStructure('images', 'first', $this->AccessDir($path, 'images'));
				if($_POST['type']=='files') $this->firstAct = false;
				if($_POST['type']=='images') echo $this->DirStructure('files', 'first');
				else 						 echo $this->DirStructure('files', 'first', $this->AccessDir($path, 'files'));
				exit();
			break;
			
			//РџРѕРєР°Р·Р°С‚СЊ РїСѓС‚СЊ (С…Р»РµР±РЅС‹Рµ РєСЂРѕС€РєРё РІРІРµСЂС…Сѓ)
			case 'showpath':
				if(isset($_POST['default']) && isset($_SESSION['tiny_image_manager_path'])) $path = $_SESSION['tiny_image_manager_path'];
				else $path = $_SESSION['tiny_image_manager_path'] = $_POST['path'];
				
				echo $this->DirPath($_POST['type'], $this->AccessDir($path, $_POST['type']));
				exit();
			break;
			
			//РџРѕРєР°Р·Р°С‚СЊ С„Р°Р№Р»С‹
			case 'showdir':
				if(isset($_POST['default']) && isset($_SESSION['tiny_image_manager_path'])) $path = $_SESSION['tiny_image_manager_path'];
				else $path = $_SESSION['tiny_image_manager_path'] = $_POST['path'];
				
				echo $this->ShowDir($path, $_POST['pathtype']);
				exit();
			break;
			
			//Р—Р°РіСЂСѓР·РёС‚СЊ РёР·РѕР±СЂР°Р¶РµРЅРёРµ
			case 'uploadfile':
				echo $this->UploadFile($_POST['path'], $_POST['pathtype']);
				exit();
			break;
			
			//РЈРґР°Р»РёС‚СЊ С„Р°Р№Р», РёР»Рё РЅРµСЃРєРѕР»СЊРєРѕ С„Р°Р№Р»РѕРІ
			case 'delfile':
				if(is_array($_POST['md5'])) {
					foreach ($_POST['md5'] as $k=>$v) {
						$this->DelFile($_POST['pathtype'], $_POST['path'], $v, $_POST['filename'][$k], true);
					}
					echo $this->ShowDir($_POST['path'], $_POST['pathtype']);
				} else {
					echo $this->DelFile($_POST['pathtype'], $_POST['path'], $_POST['md5'], $_POST['filename'], true);
				}
				exit();
			break;
			
			case 'delfolder':
				echo $this->DelFolder($_POST['pathtype'], $_POST['path']);
				exit();
			break;
			
			case 'renamefile':
				echo $this->RenameFile($_POST['pathtype'], $_POST['path'], $_POST['filename'], $_POST['newname']);
				exit();
			break;
			
			case 'SID':
				echo $this->SID;
				exit();
			break;
			
			default:
				;
			break;
		}
		
	}
	
	/**
	 * РџСЂРѕРІРµСЂРєР° РЅР° СЂР°Р·СЂРµС€РµРЅРёРµ Р·Р°РїРёСЃРё РІ РїР°РїРєСѓ (РЅРµ СЃРёСЃС‚РµРјРЅРѕРµ)
	 *
	 * @param string $requestDirectory Р—Р°РїСЂР°С€РёРІР°РµРјР°СЏ РїР°РїРєР° (РѕС‚РЅРѕСЃРёС‚РµР»СЊРЅРѕ DIR_IMAGES РёР»Рё DIR_FILES)
	 * @param (images|files) $typeDirectory РўРёРї РїР°РїРєРё, РёР·РѕР±СЂР°Р¶РµРЅРёСЏ РёР»Рё С„Р°Р№Р»С‹
	 * @return path|false
	 */
	function AccessDir($requestDirectory, $typeDirectory) {
		if($typeDirectory == 'images') {
			$full_request_images_dir = realpath($this->dir['images'].$requestDirectory);
			if(strpos($full_request_images_dir, $this->dir['images']) === 0) {
				return $full_request_images_dir;
			} else return false;
		} elseif($typeDirectory == 'files') {
			$full_request_files_dir = realpath($this->dir['files'].$requestDirectory);
			if(strpos($full_request_files_dir, $this->dir['files']) === 0){
				return $full_request_files_dir;
			} else return false;
		} else return false;
	}
	
	
	/**
	 * Р”РµСЂРµРІРѕ РєР°С‚Р°Р»РѕРіРѕРІ
	 * С„СѓРЅРєС†РёСЏ СЂРµРєСѓСЂСЃРёРІРЅР°СЏ
	 * 
	 * @return array
	 */
	function Tree($beginFolder) {
		$struct = array();
		$handle = opendir($beginFolder);
		if ($handle) {
			$struct[$beginFolder]['path'] = str_replace(array($this->dir['files'], $this->dir['images']),'',$beginFolder);
			$tmp = preg_split('[\\/]',$beginFolder);
			$tmp = array_filter($tmp);
			end($tmp);
			$struct[$beginFolder]['name'] = current($tmp);
			$struct[$beginFolder]['count'] = 0;
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && $file != '.thumbs') {
					if(is_dir($beginFolder.'/'.$file)) {
						$struct[$beginFolder]['childs'][] = $this->Tree($beginFolder.'/'.$file);
					} else {
						$struct[$beginFolder]['count']++;
					}
				}
			}
			closedir($handle);
			asort($struct);
			return $struct;
		}
		return false;
	}
	
	/**
	 * Р’РёР·СѓР°Р»РёР·Р°С†РёСЏ РґРµСЂРµРІР° РєР°С‚Р°Р»РѕРіРѕРІ
	 * С„СѓРЅРєС†РёСЏ СЂРµРєСѓСЂСЃРёРІРЅР°СЏ
	 *
	 * @param images|files $type
	 * @param first|String $innerDirs
	 * @param String $currentDir
	 * @param int $level
	 * @return html
	 */
	function DirStructure($type, $innerDirs='first', $currentDir='', $level=0) {
		//РџРѕРєР° РѕС‚РєР»СЋС‡РёРј С„Р°Р№Р»С‹
		//if($type=='files') return ;
		
		$currentDirArr = array();
		if(!empty($currentDir)) {
			$currentDirArr = preg_split('[\\/]',str_replace($this->dir[$type],'',realpath($currentDir)));
			$currentDirArr = array_filter($currentDirArr);
		}
		
		if($innerDirs == 'first') {
			$innerDirs = array();
			$innerDirs = $this->Tree($this->dir[$type]);
			if(realpath($currentDir) == $this->dir[$type] && !$this->firstAct) {
				$firstAct = 'folderAct';
				$this->firstAct = true;
			} else {
				$firstAct = '';
			}
			$ret = '';
			if($innerDirs == false) return 'РќРµРІРµСЂРЅРѕ Р·Р°РґР°РЅР° РєРѕСЂРЅРµРІР°СЏ РґРёСЂРµРєС‚РѕСЂРёСЏ ('.DIR_IMAGES.')';
			foreach ($innerDirs as $v) {
				$ret = '<div class="folder'.ucfirst($type).' '.$firstAct.'" path="" pathtype="'.$type.'">'.($type=='images'?'РР·РѕР±СЂР°Р¶РµРЅРёСЏ':'Р¤Р°Р№Р»С‹').($v['count']>0?' ('.$v['count'].')':'').'</div><div class="folderOpenSection" style="display:block;">';
				if(isset($v['childs'])) {
					$ret .= $this->DirStructure($type, $v['childs'], $currentDir, $level);
				}
				break;
			}
			$ret .= '</div>';
			return $ret;
		}
		
		if(sizeof($innerDirs)==0) return false;
		$ret = '';
		foreach ($innerDirs as $v) {
			foreach ($v as $v) {}
			if(isset($v['count'])) {
				$files = 'Р¤Р°Р№Р»РѕРІ: '.$v['count'];
				$count_childs = isset($v['childs'])?sizeof($v['childs']):0;
				if($count_childs!=0) {
					$files .= ', РїР°РїРѕРє: '.$count_childs;
				}
			} else {
				$files = '';
			}
			if(isset($v['childs'])) {
				$folderOpen = '';
				$folderAct = '';
				$folderClass = 'folderS';
				if(isset($currentDirArr[$level+1])) {
					if($currentDirArr[$level+1] == $v['name']) {
						$folderOpen = 'style="display:block;"';
						$folderClass = 'folderOpened';
						if($currentDirArr[sizeof($currentDirArr)]==$v['name'] && !$this->folderAct) {
							$folderAct = 'folderAct';
							$this->folderAct = true;
						} else {
							$folderAct = '';
						}
					}
				}
				$ret .= '<div class="'.$folderClass.' '.$folderAct.'" path="'.$v['path'].'" title="'.$files.'" pathtype="'.$type.'">'.$v['name'].($v['count']>0?' ('.$v['count'].')':'').'</div><div class="folderOpenSection" '.$folderOpen.'>';
				$ret .= $this->DirStructure($type, $v['childs'], $currentDir, $level+1);
				$ret .= '</div>';
			} else {
				$soc = sizeof($currentDirArr);
				if (isset($currentDirArr[$soc])) {
				if($soc>0 && $currentDirArr[$soc]==$v['name']) {
					$folderAct = 'folderAct';
				} else {
					$folderAct = '';
				}
				} else {
					$folderAct = '';
				}
				$ret .= '<div class="folderClosed '.$folderAct.'" path="'.$v['path'].'" title="'.$files.'" pathtype="'.$type.'">'.$v['name'].($v['count']>0?' ('.$v['count'].')':'').'</div>'; 
			}
		}
		
		return $ret;
	}
	
	/**
	 * РџСѓС‚СЊ (С…Р»РµР±РЅС‹Рµ РєСЂРѕС€РєРё)
	 *
	 * @param images|files $type
	 * @param String $path
	 * @return html
	 */
	function DirPath($type, $path='') {
		
		if(!empty($path)) {
			$path = preg_split('[\\/]',str_replace($this->dir[$type],'',realpath($path)));
			$path = array_filter($path);
		}
		
		$ret = '<div class="addrItem" path="" pathtype="'.$type.'" title=""><img src="img/'.($type=='images'?'folder_open_image':'folder_open_document').'.png" width="16" height="16" alt="РљРѕСЂРЅРµРІР°СЏ РґРёСЂРµРєС‚РѕСЂРёСЏ" /></div>';
		$i=0;
		$addPath = '';
		if(is_array($path)) { 
			foreach ($path as $v) {
				$i++;
				$addPath .= '/'.$v;
				if(sizeof($path) == $i) {
					$ret .= '<div class="addrItemEnd" path="'.$addPath.'" pathtype="'.$type.'" title=""><div>'.$v.'</div></div>';
				} else {
					$ret .= '<div class="addrItem" path="'.$addPath.'" pathtype="'.$type.'" title=""><div>'.$v.'</div></div>';
				}
			}
		}
		
		
		return $ret;
	}
	
	
	
	function CallDir($dir, $type) {
		$dir = $this->AccessDir($dir, $type);
		if(!$dir) return false;
		
		@set_time_limit(120);
		
		if(!is_dir($dir.'/.thumbs')) {
			mkdir($dir.'/.thumbs');
		}
		
		$dbfile = $dir.'/.thumbs/.db';
		if(is_file($dbfile)) {
			$dbfilehandle = fopen($dbfile, "r");
			$dblength = filesize($dbfile);
			if($dblength>0) $dbdata = fread($dbfilehandle, $dblength);
			fclose($dbfilehandle);
			$dbfilehandle = @fopen($dbfile, "w");
		} else {
			$dbfilehandle = fopen($dbfile, "w");
		}
		
		if(!empty($dbdata)) {
			$files = unserialize($dbdata);
		} else $files = array();
		
		$handle = opendir($dir);
		if ($handle) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					if(isset($files[$file])) continue;
					if(is_file($dir.'/'.$file) ) {
						$file_info = pathinfo($dir.'/'.$file);
						$file_info['extension'] = strtolower($file_info['extension']);
						if(!in_array(strtolower($file_info['extension']),$this->ALLOWED_IMAGES)) {
							continue;
						}
						$link = str_replace(array('/\\','//','\\\\','\\'),'/', '/'.str_replace(realpath(DIR_ROOT),'',realpath($dir.'/'.$file)));
						$path = pathinfo($link);
						$path = $path['dirname'];
						if($file_info['extension']=='jpg' || $file_info['extension']=='jpeg') {
							$er = new phpExifReader($dir.'/'.$file);
							$files[$file]['exifinfo'] = $er->getImageInfo();
							$files[$file]['imageinfo'] = getimagesize($dir.'/'.$file);
							
							$files[$file]['general'] = array(
								'filename' => $file,
								'name'	=> basename(strtolower($file_info['basename']), '.'.$file_info['extension']),
								'ext'	=> $file_info['extension'],
								'path'	=> $path,
								'link'	=> $link,
								'size'	=> filesize($dir.'/'.$file),
								'date'	=> filemtime($dir.'/'.$file),
								'width'	=> $files[$file]['imageinfo'][0],
								'height'=> $files[$file]['imageinfo'][1],
								'md5'	=> md5_file($dir.'/'.$file)
							);
							
						} else {
							$files[$file]['imageinfo'] = getimagesize($dir.'/'.$file);
							$files[$file]['general'] = array(
								'filename' => $file,
								'name'	=> basename(strtolower($file_info['basename']), '.'.$file_info['extension']),
								'ext'	=> $file_info['extension'],
								'path'	=> $path,
								'link'	=> $link,
								'size'	=> filesize($dir.'/'.$file),
								'date'	=> filemtime($dir.'/'.$file),
								'width'	=> $files[$file]['imageinfo'][0],
								'height'=> $files[$file]['imageinfo'][1],
								'md5'	=> md5_file($dir.'/'.$file)
							);
						}
					}
				}
			}
			closedir($handle);
		}
		
		@fwrite($dbfilehandle, serialize($files));
		@fclose($dbfilehandle);
		
		return $files;
	}
	
	
	
	function UploadFile($dir, $type) {
		$dir = $this->AccessDir($dir, $type);
		if(!$dir) return false;
		
		if(!is_dir($dir.'/.thumbs')) {
			mkdir($dir.'/.thumbs');
		}
		
		$dbfile = $dir.'/.thumbs/.db';
		if(is_file($dbfile)) {
			$dbfilehandle = fopen($dbfile, "r");
			$dblength = filesize($dbfile);
			if($dblength>0) $dbdata = fread($dbfilehandle, $dblength);
			fclose($dbfilehandle);
			//$dbfilehandle = fopen($dbfile, "w");
		} else {
			//$dbfilehandle = fopen($dbfile, "w");
		}
		
		if(!empty($dbdata)) {
			$files = unserialize($dbdata);
		} else $files = array();
		
		//Р¤Р°Р№Р» РёР· flash-РјСѓР»СЊС‚РёР·Р°РіСЂСѓР·РєРё
		if(isset($_POST['Filename'])) {
			//РўРёРї (РёР·РѕР±СЂР°Р¶РµРЅРёРµ/С„Р°Р№Р»)
			$pathtype = $_POST['pathtype'];
			if (strpos($_POST['Filename'], '.') !== false) {
				$extension = end(explode('.', $_POST['Filename']));
				$filename = substr($_POST['Filename'], 0, strlen($_POST['Filename']) - strlen($extension) - 1);
			} else {
				header('HTTP/1.1 403 Forbidden');
				exit();
			}
			if($pathtype == 'images') $allowed = $this->ALLOWED_IMAGES;
			elseif($pathtype == 'files') $allowed = $this->ALLOWED_FILES;
			//Р•СЃР»Рё РЅРµ РїРѕРґС…РѕРґРёС‚ СЂР°Р·СЂРµС€РµРЅРёРµ С„Р°Р№Р»Р°
			if(!in_array(strtolower($extension),$allowed)) {
				header('HTTP/1.1 403 Forbidden');
				exit();
			}
			
			$md5 = md5_file($_FILES['Filedata']['tmp_name']);
			$file = $md5.'.'.$extension;
			
			//РџСЂРѕРІРµСЂРєР° РЅР° РёР·РѕР±СЂР°Р¶РµРЅРёРµ
			if($pathtype == 'images') {
				$files[$file]['imageinfo'] = getimagesize($_FILES['Filedata']['tmp_name']);
				
				if(empty($files[$file]['imageinfo'])) {
					header('HTTP/1.1 403 Forbidden');
					exit();
				}
			}
			
			if(!copy($_FILES['Filedata']['tmp_name'],$dir.'/'.$file)) {
				header('HTTP/1.0 500 Internal Server Error');
				exit();
			}
			
			$link = str_replace(array('/\\','//','\\\\','\\'),'/', '/'.str_replace(realpath(DIR_ROOT),'',realpath($dir.'/'.$file)));
			$path = pathinfo($link);
			$path = $path['dirname'];
			if($extension=='jpg' || $extension=='jpeg') {
				$er = new phpExifReader($dir.'/'.$file);
				$files[$file]['exifinfo'] = $er->getImageInfo();
				
				$files[$file]['general'] = array(
					'filename' => $file,
					'name'	=> $filename,
					'ext'	=> $extension,
					'path'	=> $path,
					'link'	=> $link,
					'size'	=> filesize($dir.'/'.$file),
					'date'	=> filemtime($dir.'/'.$file),
					'width'	=> $files[$file]['imageinfo'][0],
					'height'=> $files[$file]['imageinfo'][1],
					'md5'	=> $md5
				);
			} else {
				$files[$file]['general'] = array(
					'filename' => $file,
					'name'	=> $filename,
					'ext'	=> $extension,
					'path'	=> $path,
					'link'	=> $link,
					'size'	=> filesize($dir.'/'.$file),
					'date'	=> filemtime($dir.'/'.$file),
					'width'	=> $files[$file]['imageinfo'][0],
					'height'=> $files[$file]['imageinfo'][1],
					'md5'	=> $md5
				);
			}
		}
		//Р¤Р°Р№Р»С‹ РёР· РѕР±С‹С‡РЅРѕР№ Р·Р°РіСЂСѓР·РєРё 
		else {
			sort($_FILES);
			$ufiles = $_FILES[0];
			
			foreach ($ufiles['name'] as $k=>$v) {
				if($ufiles['error'][$k] != 0) continue;
				
				//РўРёРї (РёР·РѕР±СЂР°Р¶РµРЅРёРµ/С„Р°Р№Р»)
				$pathtype = $_POST['pathtype'];
				if (strpos($ufiles['name'][$k], '.') !== false) {
					$extension = end(explode('.', $ufiles['name'][$k]));
					$filename = substr($ufiles['name'][$k], 0, strlen($ufiles['name'][$k]) - strlen($extension) - 1);

				} else {
					continue;
				}
				if($pathtype == 'images') $allowed = $this->ALLOWED_IMAGES;
				elseif($pathtype == 'files') $allowed = $this->ALLOWED_FILES;
				//Р•СЃР»Рё РЅРµ РїРѕРґС…РѕРґРёС‚ СЂР°СЃС€РёСЂРµРЅРёРµ С„Р°Р№Р»Р°
				if(!in_array(strtolower($extension),$allowed)) {
					continue;
				}
				
				$md5 = md5_file($ufiles['tmp_name'][$k]);
				$file = $md5.'.'.$extension;
				
				//РџСЂРѕРІРµСЂРєР° РЅР° РёР·РѕР±СЂР°Р¶РµРЅРёРµ
				if($pathtype == 'images') {
					$files[$file]['imageinfo'] = getimagesize($ufiles['tmp_name'][$k]);
				
					if(empty($files[$file]['imageinfo'])) {
						header('HTTP/1.1 403 Forbidden');
						exit();
					}
				}
				
				if(!copy($ufiles['tmp_name'][$k],$dir.'/'.$file)) {
					continue;
				}
				$link = str_replace(array('/\\','//','\\\\','\\'),'/', '/'.str_replace(realpath(DIR_ROOT),'',realpath($dir.'/'.$file)));
				$path = pathinfo($link);
				$path = $path['dirname'];
				if($extension=='jpg' || $extension=='jpeg') {
					$er = new phpExifReader($dir.'/'.$file);
					$files[$file]['exifinfo'] = $er->getImageInfo();
					
					$files[$file]['general'] = array(
						'filename' => $file,
						'name'	=> $filename,
						'ext'	=> $extension,
						'path'	=> $path,
						'link'	=> $link,
						'size'	=> filesize($dir.'/'.$file),
						'date'	=> filemtime($dir.'/'.$file),
						'width'	=> $files[$file]['imageinfo'][0],
						'height'=> $files[$file]['imageinfo'][1],
						'md5'	=> $md5
					);
				} else {
					$files[$file]['general'] = array(
						'filename' => $file,
						'name'	=> $filename,
						'ext'	=> $extension,
						'path'	=> $path,
						'link'	=> $link,
						'size'	=> filesize($dir.'/'.$file),
						'date'	=> filemtime($dir.'/'.$file),
						'width'	=> $files[$file]['imageinfo'][0],
						'height'=> $files[$file]['imageinfo'][1],
						'md5'	=> $md5
					);
				}
			}
		}
		
		$dbfilehandle = fopen($dbfile, "w");
		fwrite($dbfilehandle, serialize($files));
		fclose($dbfilehandle);
		
		return '';
	}
	
	
	
	function RenameFile($type, $dir, $filename, $newname) {
		$dir = $this->AccessDir($dir, $type);
		if(!$dir) return false;
		
		$filename = trim($filename);
		
		if(empty($filename)) {
			return 'error';
		}
		
		if(!is_dir($dir.'/.thumbs')) {
			return 'error';
		}
		
		$dbfile = $dir.'/.thumbs/.db';
		if(is_file($dbfile)) {
			$dbfilehandle = fopen($dbfile, "r");
			$dblength = filesize($dbfile);
			if($dblength>0) $dbdata = fread($dbfilehandle, $dblength);
			fclose($dbfilehandle);
		} else {
			return 'error';
		}
		
		$files = unserialize($dbdata);
		
		foreach ($files as $file=>$fdata) {
			if($file == $filename) {
				$files[$file]['general']['name'] = $newname;
				break;
			}
		}
		
		$dbfilehandle = fopen($dbfile, "w");
		fwrite($dbfilehandle, serialize($files));
		fclose($dbfilehandle);
		
		return 'ok';
	}
	
	function bytes_to_str($bytes) {
		$d = '';
		if($bytes >= 1048576) {
			$num = $bytes/1048576;
			$d = 'Mb';
		} elseif($bytes >= 1024) {
			$num = $bytes/1024;
			$d = 'kb';
		} else {
			$num = $bytes;
			$d = 'b';
		}
	
		return number_format($num, 2, ',', ' ').$d;
	}
	
	
	
	function ShowDir($dir, $type) {
		$dir_orig = $dir;
		$dir = $this->CallDir($dir, $type);
		
		if(!$dir) {
			//echo 'РћС€РёР±РєР° С‡С‚РµРЅРёСЏ, РІРѕР·РјРѕР¶РЅРѕ РЅРµС‚ РґРѕСЃС‚СѓРїР°.';
			exit();
		}
		$ret = '';
		foreach ($dir as $v) {
			$thumb = $this->GetThumb($v['general']['path'],$v['general']['md5'],$v['general']['filename'],2,100,100);
			if($v['general']['width'] > WIDTH_TO_LINK || $v['general']['height'] > HEIGHT_TO_LINK) {
				if($v['general']['width'] > $v['general']['height']) {
					$middle_thumb = $this->GetThumb($v['general']['path'],$v['general']['md5'],$v['general']['filename'],0,WIDTH_TO_LINK,0);
				} else {
					$middle_thumb = $this->GetThumb($v['general']['path'],$v['general']['md5'],$v['general']['filename'],0,0,HEIGHT_TO_LINK);
				}
				list($middle_width, $middle_height) = getimagesize(DIR_ROOT.$middle_thumb);
				$middle_thumb_attr = 'fmiddle="'.$middle_thumb.'" fmiddlewidth="'.$middle_width.'" fmiddleheight="'.$middle_height.'" fclass="'.CLASS_LINK.'" frel="'.REL_LINK.'"';
			} else {
				$middle_thumb = '';
				$middle_thumb_attr = '';
			}
			
			$img_params = 'width="100" height="100"';
			$div_params = '';
			if ($type == 'files') { $img_params = ''; $div_params = 'style="width: 100px; height: 80px; padding-top: 16px;"'; }
			
			$ret .= '
   <table class="imageBlock0" cellpadding="0" cellspacing="0" filename="'.$v['general']['filename'].'" fname="'.$v['general']['name'].'" type="'.$type.'" ext="'.strtoupper($v['general']['ext']).'" path="'.$v['general']['path'].'" linkto="'.$v['general']['link'].'" fsize="'.$v['general']['size'].'" fsizetext="'.$this->bytes_to_str($v['general']['size']).'" date="'.date('d.m.Y H:i',$v['general']['date']).'" fwidth="'.$v['general']['width'].'" fheight="'.$v['general']['height'].'" md5="'.$v['general']['md5'].'" '.$middle_thumb_attr.'><tr><td valign="bottom" align="center">
    <div class="imageBlock1">
     <div class="imageImage" '.$div_params.'>
      <img src="'.$thumb.'" '.$img_params.' alt="'.$v['general']['name'].'" />
     </div>
     <div class="imageName">'.$v['general']['name'].'</div>
    </div>
   </td></tr></table>
			';
		}
		
		return $ret;
	}
	
	
	
	function GetThumb($dir, $md5, $filename, $mode, $width=100, $height=100) {
		$path = realpath(DIR_ROOT.'/'.$dir);
		if(is_file($path.'/.thumbs/'.$md5.'_'.$width.'_'.$height.'_'.$mode.'.jpg')) return $dir.'/.thumbs/'.$md5.'_'.$width.'_'.$height.'_'.$mode.'.jpg';
		
		$isfile = (strpos($dir, DIR_FILES) === 0 ? true : false);
		
		if ($isfile)
		{
			$server_url = rtrim(dirname(__FILE__), '/').'/../../';
			$server_url = realpath($server_url);
			$server_url = rtrim($server_url, '/').'/img/fileicons/';
			$url = '/'.ltrim(substr($server_url, strlen(DIR_ROOT)), '/');
			
			$ext = strtolower(end(explode('.', $filename)));
			
			if (!empty($ext) && file_exists($server_url.$ext.'.png'))
			{
				return $url.$ext.'.png';
			}
			else
			{
				return $url.'none.png';
			}
		}
		
		$t = new Image_Toolbox($path.'/'.$filename);
		$t->newOutputSize($width, $height, $mode, false, '#FFFFFF');
		$t->save($path.'/.thumbs/'.$md5.'_'.$width.'_'.$height.'_'.$mode.'.jpg', 'jpg', 80);
		return $dir.'/.thumbs/'.$md5.'_'.$width.'_'.$height.'_'.$mode.'.jpg';
	}
	
	
	function DelFile($pathtype, $path, $md5, $filename, $callShowDir=false) {
		$tmppath = $path;
		$path = $this->AccessDir($path, $pathtype);
		if(!$path) return false;
		
		if(is_dir($path.'/.thumbs')) {
			if($pathtype == 'images') {
				$handle = opendir($path.'/.thumbs');
				if ($handle) {
					while (false !== ($file = readdir($handle))) {
						if ($file != "." && $file != "..") {
							if(substr($file,0,32) == $md5) {
								unlink($path.'/.thumbs/'.$file);
							}
						}
					}
				}
			}
			
			$dbfile = $path.'/.thumbs/.db';
			if(is_file($dbfile)) {
				$dbfilehandle = fopen($dbfile, "r");
				$dblength = filesize($dbfile);
				if($dblength>0) $dbdata = fread($dbfilehandle, $dblength);
				fclose($dbfilehandle);
				$dbfilehandle = fopen($dbfile, "w");
			} else {
				$dbfilehandle = fopen($dbfile, "w");
			}
		
			
			if(isset($dbdata)) {
				$files = unserialize($dbdata);
			} else $files = array();
			
			unset($files[$filename]);
			
			fwrite($dbfilehandle, serialize($files));
			fclose($dbfilehandle);
		}
		
		if(is_file($path.'/'.$filename)) {
			if(unlink($path.'/'.$filename)) {
				if($callShowDir) {
					return $this->ShowDir($tmppath, $pathtype);
				} else {
					return true;
				}
			}
		} else return 'error';
		
		return 'error'; 
	}
	
	function DelFolder($pathtype, $path) {
		$path = $this->AccessDir($path, $pathtype);
		if(!$path) return false;
		
		if(realpath($path.'/') == realpath(DIR_ROOT.DIR_IMAGES.'/')) {
			return '{error:"РќРµР»СЊР·СЏ СѓРґР°Р»СЏС‚СЊ РєРѕСЂРЅРµРІСѓСЋ РїР°РїРєСѓ!"}';
		}
		
		$files = array();
		
		$handle = opendir($path);
		if ($handle) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && trim($file) != "" && $file != ".thumbs") {
					if(is_dir($path.'/'.$file)) {
						return '{error:"РџРѕРєР° РїР°РїРєР° СЃРѕРґРµСЂР¶РёС‚ РІР»РѕР¶РµРЅРЅС‹Рµ РїР°РїРєРё, РѕРЅР° РЅРµ РјРѕР¶РµС‚ Р±С‹С‚СЊ СѓРґР°Р»РµРЅР°."}';
					} else {
						$files[] = $file;
					}
				}
			}
		}
		closedir($handle);
		
		$handle = opendir($path.'/.thumbs');
		if ($handle) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					if(is_file($path.'/.thumbs/'.$file)) {
						unlink($path.'/.thumbs/'.$file);
					}
				}
			}
			closedir($handle);
			rmdir($path.'/.thumbs');
		}
		
		foreach ($files as $f) {
			if(is_file($path.'/'.$f)) unlink($path.'/'.$f);
		}
		
		if(!rmdir($path)) return '{error:"РћС€РёР±РєР° СѓРґР°Р»РµРЅРёСЏ РїР°РїРєРё"}';
		
		return '{ok:\'\'}';
	}
	
}

$letsGo = new TinyImageManager();

?>
