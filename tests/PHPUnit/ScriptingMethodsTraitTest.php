<?php

namespace ScriptingMethodsTraitTest;

class ProperRedis extends \Redis\Redis {

	use \Redis\Traits\ScriptingMethodsTrait;

}

class ScriptingMethodsTraitTest extends \PHPUnit_Framework_TestCase {

	function getInst($memory){
		$inst = new ProperRedis;
		$reflection = new \ReflectionClass($inst);
		$handle = $reflection->getProperty("handle");
		$methods = $reflection->getMethods();
		$handle->setAccessible(true);
		$handle->setValue($inst, $memory);
		return [$inst, $methods];
	}

	function test_all_the_things(){
		$memory = fopen("php://memory", "rw+");
		list($inst, $methods) = $this->getInst($memory);

		$seek = 0;
		foreach($methods as $method){

			$message = strtoupper($method->getName()) . "'s converstion to Redis protocol failed.";
			$method = "do_{$method->getName()}";

			if(!method_exists($this, $method)){ continue; }

			$expected = $this->$method($inst);
			$expected = str_replace(" ", "\r\n", $expected);

			fseek($memory, $seek);
			$result = fread($memory, strlen($expected));
			$seek += strlen($expected);

			$this->assertEquals($expected, $result, $message);
		}
	}

	function do_evalLua($inst) {
		$inst->evalLua("testkey1", ["testkey2"]);
		return "*4 $4 eval $8 testkey1 $1 1 $8 testkey2 ";
	}

	function do_evalsha($inst) {
		$inst->evalsha("testkey1", ["testkey2", "testkey3"]);
		return "*5 $7 evalsha $8 testkey1 $1 2 $8 testkey2 $8 testkey3 ";
	}

	function do_scriptExists($inst) {
		$inst->scriptExists(["testkey1", "testkey2"]);
		return "*4 $6 script $6 exists $8 testkey1 $8 testkey2 ";
	}


	function do_scriptFlush($inst) {
		$inst->scriptFlush();
		return "*2 $6 script $5 flush ";
	}

	function do_scriptKill($inst) {
		$inst->scriptKill();
		return "*2 $6 script $4 kill ";
	}

	function do_scriptLoad($inst) {
		$inst->scriptLoad("testkey1");
		return "*3 $6 script $4 load $8 testkey1 ";
	}

	/**
	 * @expectedException Redis\RedisException
	 */
	function test_evalLua_exception() {
		$memory = fopen("php://memory", "rw+");
		list($inst, $methods) = $this->getInst($memory);
		$inst->evalLua("script", []);
	}

	/**
	 * @expectedException Redis\RedisException
	 */
	function test_evalsha_exception() {
		$memory = fopen("php://memory", "rw+");
		list($inst, $methods) = $this->getInst($memory);
		$inst->evalsha("testkey1", []);
	}

	/**
	 * @expectedException Redis\RedisException
	 */
	function test_scriptExists_exception() {
		$memory = fopen("php://memory", "rw+");
		list($inst, $methods) = $this->getInst($memory);
		$inst->scriptExists([]);
	}


}
