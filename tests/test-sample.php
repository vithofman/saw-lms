<?php
/**
 * Sample test class
 *
 * @package SAW_LMS
 */

/**
 * Sample test case
 */
class Test_Sample extends WP_UnitTestCase {

	/**
	 * Test that plugin is loaded
	 */
	public function test_plugin_loaded() {
		$this->assertTrue( defined( 'SAW_LMS_VERSION' ) );
	}

	/**
	 * Test that main class exists
	 */
	public function test_main_class_exists() {
		$this->assertTrue( class_exists( 'SAW_LMS' ) );
	}

	/**
	 * Test that logger class exists
	 */
	public function test_logger_class_exists() {
		$this->assertTrue( class_exists( 'SAW_LMS_Logger' ) );
	}

	/**
	 * Test that cache manager exists
	 */
	public function test_cache_manager_exists() {
		$this->assertTrue( class_exists( 'SAW_LMS_Cache_Manager' ) );
	}
}