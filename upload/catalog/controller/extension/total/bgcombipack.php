<?php
class ControllerExtensionTotalbgcombipack extends Controller {
	private $error = array();
	private $modpath = 'total/bgcombipack'; 
	private $modvar = 'model_total_bgcombipack';
	private $modtpl = 'total/bgcombipack.tpl';
	private $modname = 'bgcombipack';
	private $evntcode = 'bgcombipack';
 	private $modurl = 'extension/total';
	private $status = false;
	private $setting = array();

	public function __construct($registry) {		
		parent::__construct($registry);		
		ini_set("serialize_precision", -1);
		
		if(substr(VERSION,0,3)=='2.0') {
		}
		if(substr(VERSION,0,3)=='2.1') {
		}		
		if(substr(VERSION,0,3)=='2.2') {
			$this->modtpl = 'total/bgcombipack';
		}
		if(substr(VERSION,0,3)=='2.3') {
			$this->modpath = 'extension/total/bgcombipack';
			$this->modvar = 'model_extension_total_bgcombipack';
			$this->modtpl = 'extension/total/bgcombipack';			
			$this->modurl = 'extension/extension';
		}
		if(substr(VERSION,0,3)=='3.0') {			
			$this->modpath = 'extension/total/bgcombipack';
			$this->modvar = 'model_extension_total_bgcombipack';
			$this->modtpl = 'extension/total/bgcombipack30X';
			$this->modname = 'total_bgcombipack';
			$this->modurl = 'marketplace/extension'; 
		} 
		if(substr(VERSION,0,3)=='4.0') {
			$this->modpath = 'extension/bgcombipack/total/bgcombipack';
			$this->modvar = 'model_extension_bgcombipack_total_bgcombipack';
			$this->modtpl = 'extension/bgcombipack/total/bgcombipack40X';			
			$this->modname = 'total_bgcombipack';
			$this->modurl = 'marketplace/extension'; 
		}
		
		//$this->setting = $this->getSetting();
		//$this->status = ($this->config->get($this->modname.'_status') && $this->setting['status']) ? true : false;	
		$this->status = $this->config->get($this->modname.'_status');
 	}
	public function getcache() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->getcache();
	}
	public function getSetting() {		
		$this->load->model($this->modpath);
		return $this->{$this->modvar}->getSetting();	
	}
	public function loadjscss(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->loadjscss();
	}
}