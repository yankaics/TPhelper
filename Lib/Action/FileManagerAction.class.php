<?php
/**
 * User: zhuyajie
 * Date: 12-11-5
 * Time: 上午2:25
 */
class FileManagerAction extends CommonAction
{

	/**
	 * 扫描项目目录下已存在的js文件
	 * @param        $dir
	 * @param string $ext
	 *
	 * @return array
	 */
	protected function scanFile( $dir, $ext = '.js' ) {
		$rdi   = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS|FilesystemIterator::CURRENT_AS_SELF|FilesystemIterator::KEY_AS_FILENAME);
		$rii   = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::LEAVES_ONLY);
		$files = array();
		foreach ( $rii as $k=> $v ) {
			if ( strrpos( $k, $ext ) ) {
				$files[] = $k;
			}
		}
		return $files;
	}

	public function getFileList() {
		$dir = cookie ( 'base_dir' );
		if ( is_dir( $dir ) && is_writeable( $dir ) && is_readable( $dir ) ) {
//			$oldjsLib = $this->scanFile( $dir );//扫描项目下面的js文件
			$jsLib = new GlobIterator(realpath('public'.DIRECTORY_SEPARATOR.'jsLib').DIRECTORY_SEPARATOR.'*',GlobIterator::CURRENT_AS_PATHNAME|GlobIterator::KEY_AS_FILENAME);
			$jsLib = iterator_to_array( $jsLib );//扫描TP助手下面的js库
			unset($jsLib['jsLib.xml'],$jsLib['readme.txt']);
//			$this->assign( 'oldjsLib', $oldjsLib );
			$this->assign( 'jsLib', $jsLib );
		} else {
			$this->error( '尚未添加TP项目或'.$dir.'目录权限不足' );
			return;
		}
		if ( file_exists( 'public'.DIRECTORY_SEPARATOR.'jsLib'.DIRECTORY_SEPARATOR.'jsLib.xml' ) ) {
			$doc  = new SimpleXMLIterator('public'.DIRECTORY_SEPARATOR.'jsLib'.DIRECTORY_SEPARATOR.'jsLib.xml', null, true);
			$result=array();
			foreach ( $doc->jslib as $v ) {
				$r                  = array();
				$r['file']          = (string)$v->file;
				$r['desc']          = (string)$v->desc;
				$r['site']          = (string)$v->site;
				$r['size']          = (string)$v->size;
				$result[$r['file']] = $r;
			}

			$json = json_encode( $result );
			$this->assign( 'json', $json );
		}
		$this->display();
	}

	public function addlibs() {
		if ( isset($_POST['jslibs']) ) {
			$dir =json_decode($_COOKIE['base_dir']);
			if ( !file_exists( $dir.'js'.DIRECTORY_SEPARATOR ) ) {
				if(!mkdir( $dir.'js'.DIRECTORY_SEPARATOR )){
					$this->error( $dir.'---没有写入权限' );
					return;
				}
			} elseif ( !is_dir( $dir.'js' ) ) {
				$this->error( $dir.'js---不是一个目录' );
				return;
			}

			foreach ( $_POST['jslibs'] as $k=> $v ) {
				if (is_file('public'.DIRECTORY_SEPARATOR.'jsLib'.DIRECTORY_SEPARATOR.$k)) {
					copy( 'public'.DIRECTORY_SEPARATOR.'jsLib'.DIRECTORY_SEPARATOR.$k, $dir.'js'.DIRECTORY_SEPARATOR.$k );
				} elseif ( is_dir( 'public'.DIRECTORY_SEPARATOR.'jsLib'.DIRECTORY_SEPARATOR.$k ) ) {
					self::dirCopy('public'.DIRECTORY_SEPARATOR.'jsLib'.DIRECTORY_SEPARATOR.$k,$dir.'js'.DIRECTORY_SEPARATOR.$k);
				}
			}
			$this->success( '操作成功，即将跳转到首页',U('Index/index') );
		}
	}

	protected static function dirCopy($source,$dest) {
		$rdi = new RecursiveDirectoryIterator($source,RecursiveDirectoryIterator::KEY_AS_PATHNAME|RecursiveDirectoryIterator::SKIP_DOTS|RecursiveDirectoryIterator::CURRENT_AS_SELF);
		$rii = new RecursiveIteratorIterator($rdi,RecursiveIteratorIterator::SELF_FIRST);
		$dest = CheckConfig::dirModifier( $dest );
		mkdir( $dest );
		foreach ( $rii as $k=> $v ) {
			if ( is_file( $k ) ) {
				copy($k,$dest.$v->getSubPathname());
			}elseif(is_dir($k)){
				mkdir($dest.$v->getSubPathname());
			}
		}
	}
}
