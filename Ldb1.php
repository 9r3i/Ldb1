<?php
/* Ldb - stands for Luthfie database :D
 * ~ custom and personal database
 * authored by 9r3i
 * https://github.com/9r3i
 * started at january 21st 2014
 */

// *** Class Ldb => Luthfie database *** //
class Ldb{
  public $db_dir;
  private $batas;
  private $skat;
  public $base_time;
  public $cid;
  public $count=0;
  public $version = '1.0';
  function __construct($directory='_Ldb'){
    $this->db_dir = $directory;
	if(!is_dir($this->db_dir)){
	  @mkdir($this->db_dir,0700);
	}
	$this->batas = '---------'.md5('9r3i').'---------';
	$this->skat = '==='.md5('===').'===';
	$this->base_time = 452373300; // Luthfie's birthdate in time();
	$this->cid = dechex(time()-$this->base_time);
	$this->htaccess();
	$this->dump_dir();
	@chmod($this->db_dir.'/',0700);
	if(!defined('ALL_SECTOR')){define('ALL_SECTOR',4096);}
	if(!defined('PRIME_SECTOR')){define('PRIME_SECTOR',32);}
  }
  public function write($data=array()){
	$filename = $this->db_dir.'/'.$this->cid.'.ldb';
	$db_content = array();
	foreach($data as $kolom=>$isi){
	  $db_content[] = $kolom.$this->skat.$isi;
	}
	if($this->file_write($filename,'w+',implode($this->batas,$db_content))){
	  return $this->cid;
	}
	else{
	  return false;
	}
  }
  public function read($data=PRIME_SECTOR){
    $sdir = @scandir($this->db_dir);
    $db = array();
    foreach($sdir as $sd){
      if(preg_match('/[0-9a-f]+\.ldb/i',$sd)){
	    $id = str_replace('.ldb','',$sd);
		$content = @file_get_contents($this->db_dir.'/'.$sd);
		$parse = $this->content_parse($content);
	    $db[$id] = $parse;
	    $db[$id]['cid'] = $id;
	    $db[$id]['time'] = hexdec($id)+$this->base_time;
		$this->count++;
		if($data==ALL_SECTOR&&count($parse)>0){
		  foreach($parse as $key=>$val){
		    $this->count++;
		    $db[$key][$val][$id] = $parse;
	        $db[$key][$val][$id]['cid'] = $id;
	        $db[$key][$val][$id]['time'] = hexdec($id)+$this->base_time;
		  }
		}
	  }
    }
	return $db;
  }
  public function select($where,$id='*'){
    if(preg_match('/\=/i',$where)&&preg_match('/\,/i',$where)){
      $explode = explode(',',$where);
	  $select = $this->select($explode[0],$id);
	  $expo = $this->where_parse($where);
	  $hasil = array();
	  if(count($select)>0&&is_array($select)){
	    foreach($select as $key=>$val){
	      $r=0;
          foreach($expo as $index=>$value){
		    if($val[$index]==$value){$r++;}
		  }
		  if($r==count($expo)){
		    $hasil[$key] = $val;
		  }
	    }
	  }
	  $this->count = count($hasil);
	  return $hasil;
	}
    elseif(preg_match('/\=/i',$where)&&!preg_match('/\,/i',$where)){
	  $exp_where = explode('=',$where);
	  if(isset($exp_where[0])&&isset($exp_where[1])){
	    $read = $this->read(ALL_SECTOR);
        $hasil = array();
		if(array_key_exists($exp_where[0],$read)&&array_key_exists($exp_where[1],$read[$exp_where[0]])){
		  $hasil = $read[$exp_where[0]][$exp_where[1]];
		  if($id!=='*'){
		    $expid = explode(',',$id);
			$ret = array();
			foreach($hasil as $has){
			  foreach($expid as $exid){
			    $exid = trim($exid);
			    if(isset($has[$exid])){
				  $ret[$has['cid']][$exid] = $has[$exid];
				}
			  }
			}
			$this->count = count($ret);
		    return $ret;
		  }
		  else{
		    $this->count = count($hasil);
		    return $hasil;
		  }
		}
		else{
		  $this->count = count($hasil);
		  return false;
		}
	  }
	  else{
	    return $this->read();
	  }
	}
	else{
	  return false;
	}
  }
  public function update($cid,$data=array()){
    $filename = $this->db_dir.'/'.$cid.'.ldb';
	if(file_exists($filename)){
      $old_content = @file_get_contents($filename);
	  $new_content = $this->content_parse($old_content);
	  $old_content .= $this->batas.'cid'.$this->skat.$cid;
	  $dump = $this->file_write($this->dump_dir().$this->cid.'.dump','w+',$old_content);
	  $db_content = array();
	  foreach($new_content as $id=>$content){
	    if(isset($data[$id])){
		  $db_content[] = $id.$this->skat.$data[$id];
		}
		else{
		  $db_content[] = $id.$this->skat.$new_content[$id];
		}
	  }
      if($this->file_write($filename,'w+',implode($this->batas,$db_content))){
        return true;
      }
	  else{
	    return false;
	  }
	}
    else{
	  return false;
    }
  }
  public function delete($cid){
    $filename = $this->db_dir.'/'.$cid.'.ldb';
    $old_content = @file_get_contents($filename);
	$old_content .= $this->batas.'cid'.$this->skat.$cid;
	$dump = $this->file_write($this->dump_dir().$this->cid.'.dump','w+',$old_content);
    return @unlink($filename);
  }
  public function htaccess(){
    $ht_filename = $this->db_dir.'/.htaccess';
    if(!file_exists($ht_filename)){
	  $this->file_write($ht_filename,'w+','Options -Indexes');
	}
  }
  public function dump_dir(){
    $dirname = $this->db_dir.'/_dump/';
    if(!is_dir($dirname)){
	  @mkdir($dirname,0700);
	  $ht_filename = $dirname.'.htaccess';
	  $this->file_write($ht_filename,'w+','Options -Indexes');
	}
	@chmod($dirname,0700);
	return $dirname;
  }
  public function file_write($filename,$type='a',$content=''){
    $fp = fopen($filename,$type);
    $write = fwrite($fp,$content);
    fclose($fp);
    if($write){
      return true;
    }
    else{
      return false;
    }
  }
  public function content_parse($content){
    $hasil = array();
	$data_exp = explode($this->batas,$content);
	if(count($data_exp)>0&&is_array($data_exp)){
	  foreach($data_exp as $data){
	    $exp = explode($this->skat,$data);
		if(isset($exp[0])&&isset($exp[1])){
	      $hasil[$exp[0]] = $exp[1];
		}
		else{
		  $hasil[$exp[0]] = $data;
		}
	  }
	  return $hasil;
	}
	else{
	  return $content;
	}
  }
  public function where_parse($string){
    if(preg_match('/\,/i',$string)&&preg_match('/\=/i',$string)){
	  $hasil = array();
	  $explode = explode(',',$string);
	  foreach($explode as $explo){
	    $expo = explode('=',$explo);
		if(isset($expo[1])){
		  $hasil[$expo[0]] = $expo[1];
		}
	  }
	  return $hasil;
	}
	else{
	  return false;
	}
  }
}
