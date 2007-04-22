<?php
interface IDevblocksSearchFields {
    static function getFields();
}
class DevblocksSearchCriteria {
    const OPER_EQ = '=';
    const OPER_NEQ = '!=';
    const OPER_IN = 'in';
    const OPER_NIN = 'not in';
    const OPER_LIKE = 'like';
    const OPER_GT = '>';
    const OPER_LT = '<';
    const OPER_GTE = '>=';
    const OPER_LTE = '<=';
    
	public $field;
	public $operator;
	public $value;
	
	/**
	 * Enter description here...
	 *
	 * @param string $field
	 * @param string $oper
	 * @param mixed $value
	 * @return DevblocksSearchCriteria
	 */
	 public function DevblocksSearchCriteria($field,$oper,$value) {
		$this->field = $field;
		$this->operator = $oper;
		$this->value = $value;
	}
};
class DevblocksSearchField {
	public $token;
	public $db_table;
	public $db_column;
	
	function __construct($token, $db_table, $db_column) {
		$this->token = $token;
		$this->db_table = $db_table;
		$this->db_column = $db_column;
	}
}

class DevblocksExtensionPoint {
	var $id = '';
	var $extensions = array();
}

/**
 * Manifest information for plugin.
 * @ingroup plugin
 */
class DevblocksPluginManifest {
	var $id = '';
	var $enabled = 0;
	var $name = '';
	var $description = '';
	var $author = '';
	var $revision = 0;
	var $dir = '';
	var $extensions = array();
	
	function setEnabled($bool) {
		$this->enabled = ($bool) ? 1 : 0;
		
		// [JAS]: Persist to DB
		$fields = array(
			'enabled' => $this->enabled
		);
		DAO_Platform::updatePlugin($this->id,$fields);
	}
	
	/**
	 * @return DevblocksPatchContainer
	 */
	function getPatchContainer() {
		return null;
	}
};

/**
 * Manifest information for a plugin's extension.
 * @ingroup plugin
 */
class DevblocksExtensionManifest {
	var $id = '';
	var $plugin_id ='';
	var $point = '';
	var $name = '';
	var $file = '';
	var $class = '';
	var $params = array();

	function DevblocksExtensionManifest() {}
	
	/**
	 * Creates and loads a usable extension from a manifest record.  The object returned 
	 * will be of type $class defined by the manifest.  $instance_id is passed as an 
	 * argument to uniquely identify multiple instances of an extension.
	 *
	 * @param integer $instance_id
	 * @return object
	 */
	function createInstance($instance_id=1) {
		if(empty($this->id) || empty($this->plugin_id)) // empty($instance_id) || 
			return null;

//		$plugins = DevblocksPlatform::getPluginRegistry();
//		
//		if(!isset($plugins[$this->plugin_id]))
//			return null;
//		
//		$plugin = $plugins[$this->plugin_id]; /* @var $plugin DevblocksPluginManifest */

		$plugin = DevblocksPlatform::getPlugin($this->plugin_id);
			
		$class_file = DEVBLOCKS_PLUGIN_PATH . $plugin->dir . '/' . $this->file;
		$class_name = $this->class;

		if(!file_exists($class_file))
			return null;

		include_once($class_file);
		if(!class_exists($class_name)) {
			return null;
		}
		
		$instance = new $class_name($this,$instance_id);
		return $instance;
	}
	
	function getPlugin() {
		$plugin = DevblocksPlatform::getPlugin($this->plugin_id);
		return $plugin;
	}
};

/**
 * A single session instance
 *
 * @ingroup core
 * [TODO] Evaluate if this is even needed, or if apps can have their own unguided visit object
 */
abstract class DevblocksVisit {
	private $registry = array();
	
	public function exists($key) {
		return isset($this->registry[$key]);
	}
	
	public function get($key, $default=null) {
		$value = $this->registry[$key];
		
		if(is_null($value) && !is_null($default)) 
			$value = $default;
			
		return $value;
	}
	
	public function set($key, $object) {
		$this->registry[$key] = $object;
	}
};


/**
 * 
 */
abstract class DevblocksPatch {
	private $plugin_id = ''; // cerberusweb.core
	private $revision = 0; // 100
//	private $one_run = false;
	
	protected function __construct($plugin_id, $revision) { // $one_run=false
		$this->plugin_id = $plugin_id;
		$this->revision = intval($revision);
//		$this->one_run = $one_run;
	}
	
	/**
	 * @return boolean
	 */
	public function run() {
		// Override
		die("Your patch class should overload the run() method.");
	}
	
	protected function _ran() {
		DAO_Platform::setPatchRan($this->plugin_id,$this->revision);
	}
	
	/**
	 * @return boolean
	 */
	public function hasRun() {
		// Compare PLUGIN_ID + REVISION in script history
		return DAO_Platform::hasPatchRun($this->plugin_id,$this->revision);
	}
	
	public function getPluginId() {
		return $this->plugin_id;
	}
	
	public function getRevision() {
		return $this->revision;
	}
	
	public function getOneRun() {
		return $this->one_run;
	}
		
};

?>