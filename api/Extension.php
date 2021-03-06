<?php
abstract class DevblocksApplication {
	
}

/**
 * The superclass of instanced extensions.
 *
 * @abstract 
 * @ingroup plugin
 */
class DevblocksExtension {
	public $manifest = null;
	public $id  = '';
	private $params = array();
	private $params_loaded = false;
	
	/**
	 * Constructor
	 *
	 * @private
	 * @param DevblocksExtensionManifest $manifest
	 * @return DevblocksExtension
	 */
	function DevblocksExtension($manifest) { /* @var $manifest DevblocksExtensionManifest */
        if(empty($manifest)) return;
        
		$this->manifest = $manifest;
		$this->id = $manifest->id;
//		$this->params = $this->_getParams();
	}
	
	function getParams() {
	    if(!$this->params_loaded) {
	        $this->params = $this->_getParams();
	        $this->params_loaded = true;
	    }
	    return $this->params;
	}
	
	function setParam($key, $value) {
	    $this->params[$key] = $value;
	    
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

		$db->Replace(
			$prefix.'property_store',
			array('extension_id'=>$db->qstr($this->id),'property'=>$db->qstr($key),'value'=>$db->qstr($value)),
			array('extension_id','property'),
			false
		);
	}
	
	function getParam($key,$default=null) {
	    $params = $this->getParams(); // make sure we're fresh
	    return isset($params[$key]) ? $params[$key] : $default;
	}
	
	/**
	 * Loads parameters unique to this extension instance.  Returns an 
	 * associative array indexed by parameter key.
	 *
	 * @private
	 * @return array
	 */
	private function _getParams() {
//		static $params = null;
		
		if(empty($this->id))
			return null;
		
//		if(null != $params)
//			return $params;
		
		$params = $this->manifest->params;
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		$sql = sprintf("SELECT property,value ".
			"FROM %sproperty_store ".
			"WHERE extension_id=%s ",
			$prefix,
			$db->qstr($this->id)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$params[$rs->fields['property']] = $rs->fields['value'];
			$rs->MoveNext();
		}
		
		return $params;
	}
};

abstract class DevblocksHttpResponseListenerExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
    
	function run(DevblocksHttpResponse $request, Smarty $tpl) {
	}
}

abstract class DevblocksTranslationsExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	function getTmxFile() {
		return NULL;
	}
}

/**
 * 
 */
abstract class DevblocksPatchContainerExtension extends DevblocksExtension {
	private $patches = array();

	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
		
	public function registerPatch(DevblocksPatch $patch) {
		// index by revision
		$rev = $patch->getRevision();
		$this->patches[$rev] = $patch;
		ksort($this->patches);
	}
	
	public function run() {
		if(is_array($this->patches))
		foreach($this->patches as $rev => $patch) { /* @var $patch DevblocksPatch */
			if(!$patch->run())
				return FALSE;
		}
		
		return TRUE;
	}
	
	public function runRevision($rev) {
		die("Overload " . __CLASS__ . "::runRevision()");
	}
	
	/**
	 * @return DevblocksPatch[]
	 */
	public function getPatches() {
		return $this->patches;
	}
};

abstract class DevblocksControllerExtension extends DevblocksExtension implements DevblocksHttpRequestHandler {
    function __construct($manifest) {
        self::DevblocksExtension($manifest);
    }

	public function handleRequest(DevblocksHttpRequest $request) {}
	public function writeResponse(DevblocksHttpResponse $response) {}
};

abstract class DevblocksEventListenerExtension extends DevblocksExtension {
    function __construct($manifest) {
        self::DevblocksExtension($manifest);
    }
    
    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {}
};

interface DevblocksHttpRequestHandler {
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request);
	public function writeResponse(DevblocksHttpResponse $response);
}

class DevblocksHttpRequest extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
}

class DevblocksHttpResponse extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
}

abstract class DevblocksHttpIO {
	public $path = array();
	public $query = array();
	
	/**
	 * Enter description here...
	 *
	 * @param array $path
	 */
	function __construct($path,$query=array()) {
		$this->path = $path;
		$this->query = $query;
	}
}
