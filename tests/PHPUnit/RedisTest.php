<?php

class RedisTest extends PHPUnit_Framework_TestCase {

	function getInst($memory){
		$inst = new \Redis\Redis;
		$reflection = new ReflectionClass($inst);
		$handle = $reflection->getProperty("handle");
		$handle->setAccessible(true);
		$handle->setValue($inst, $memory);
		return $inst;
	}

	/**
	 * @expectedException Redis\RedisException
	 */
	function test_connect_exception(){
		// $inst = (new \Redis\Redis)->connect("123.123.123.123", "12345", 2);
		$inst = new \Redis\Redis;
		$inst = $inst->connect("123.123.123.123", "12345", 2);
	}

	function test_select(){
		$memory = fopen("php://memory", "rw+");
		$inst = $this->getInst($memory);
		$inst->select(4);
		$this->assertEquals(4, $inst->db);
	}

	function test_pipe(){
		$memory = fopen("php://memory", "rw+");
		$inst = $this->getInst($memory);

		$base = array(
			array("sadd", "testkey1", "testvalue1"),
			array("sadd", "testkey2", "testvalue2"),
		);

		$inst->pipe($base);

		$expected = "*3\r\n$4\r\nsadd\r\n$8\r\ntestkey1\r\n$10\r\ntestvalue1\r\n\r\n*3\r\n$4\r\nsadd\r\n$8\r\ntestkey2\r\n$10\r\ntestvalue2\r\n\r\n";

		rewind($memory);
		$result = fread($memory, strlen($expected));

		$this->assertEquals($expected, $result);

	}

	function test___call_set(){
		$memory = fopen("php://memory", "rw+");
		$inst = $this->getInst($memory);

		$inst->set("testkey1", "testvalue1");

		$expected = "*3\r\n$3\r\nset\r\n$8\r\ntestkey1\r\n$10\r\ntestvalue1\r\n";

		rewind($memory);
		$result = fread($memory, strlen($expected));

		$this->assertEquals($expected, $result);
	}

	function test___call_get(){
		$memory = fopen("php://memory", "rw+");
		$inst = $this->getInst($memory);

		$inst->set("testkey1", "testvalue1");

		rewind($memory);
		// $result = fread($memory, strlen($expected));

		$result = $inst->get("testkey1");

		$expected = "testvalue1";

		$this->assertEquals($expected, $result);
	}

	function test_index2assoc(){
		$inst = new \Redis\Redis;

		$expected = array(
			"one" => "qwer",
			"two" => "asdf",
		);

		$result = $inst->marshal(array(
			"one", "qwer", "two", "asdf"
		));

		$this->assertEquals($expected, $result);
	}

}


