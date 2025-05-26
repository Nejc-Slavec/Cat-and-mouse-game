<?php


namespace ColibriWP\PageBuilder\DemoImport\Hooks;


use function ExtendBuilder\array_get_value;
use function ExtendBuilder\array_set_value;

abstract class ImportHook {
	/** @var static The instance. */
	protected static $instance = [];

	/**
	 * Protected constructor to prevent creating a new instance of the Singleton class from outside the object.
	 */
	private function __construct() {
		$this->run();
	}

	protected abstract function run();

	/**
	 * Returns the only instance of the Singleton class.
	 *
	 * @return static the only instance of the Singleton class.
	 */
	public static function getInstance() {
		static::init();

		return static::$instance[ static::class ];
	}

	public static function init() {
		if ( ! isset( static::$instance[ static::class ] ) ) {
			$instance                          = new static();
			static::$instance[ static::class ] = $instance;
			add_action( 'extendthemes-ocdi/after_import', array( $instance, 'afterImport' ),
				$instance->afterImportPriority() );
		}
	}

	public function afterImportPriority() {
		return 10;
	}

	public function getTransient( $path = "", $default = "" ) {

		$key = $this->transientKey() . ".{$path}";

		return $this->getGlobalTransient( $key, $default );
	}

	abstract function transientKey();

	public function getGlobalTransient( $path = "", $default = "" ) {
		$value = get_transient( "colibri_demo_import_data" );

		if ( ! $value ) {
			return $default;
		}

		$value = (array) $value;

		return array_get_value( $value, $path, $default );
	}

	public function setTransient( $path = "", $value = "" ) {
		$key = $this->transientKey() . ".{$path}";
		$this->setGlobalTransient( $key, $value );
	}

	public function setGlobalTransient( $path = "", $value = "" ) {
		$array = get_transient( "colibri_demo_import_data" );

		if ( ! $array ) {
			$array = [];
		}

		if($path === ""){
			set_transient( 'colibri_demo_import_data', $value );
			return;
		}

		array_set_value( $array, $path, $value );
		set_transient( 'colibri_demo_import_data', $array );
	}

	public function emptyTransient( $path = "" ) {
		$key = $this->transientKey() . ".{$path}";
		$this->emptyGlobalTransient( $key );
	}

	public function emptyGlobalTransient( $path = "" ) {
		$this->setGlobalTransient( $path, null );
	}

	public function afterImport( $data ) {

	}

	/**
	 * Protected clone method to prevent cloning of the Singleton instance.
	 */
	public function __clone() {
	}

	/**
	 * Protected wakeup method to prevent unserializing of the Singleton instance.
	 */
	public function __wakeup() {
	}
}
