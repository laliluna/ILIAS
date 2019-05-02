<?php

/* Copyright (c) 2019 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\Tests\Setup;

use ILIAS\Setup;
use ILIAS\UI\Component\Input\Field\Factory as FieldFactory;
use ILIAS\UI\Component\Input\Field\Input as Input;
use ILIAS\Transformation\Transformation;
use ILIAS\Transformation\Factory as TransformationFactory;

class ConsumerCollectionTest extends \PHPUnit\Framework\TestCase {
	public function testHasConfig() {
		$ff = $this->createMock(FieldFactory::class);
		$tf = new TransformationFactory();

		$c1 = $this->newConsumer();
		$c2 = $this->newConsumer();
		$c3 = $this->newConsumer();
		$c4 = $this->newConsumer();

		$c1->method("hasConfig")->willReturn(true);
		$c2->method("hasConfig")->willReturn(true);
		$c3->method("hasConfig")->willReturn(false);
		$c4->method("hasConfig")->willReturn(false);

		$col1 = new Setup\ConsumerCollection($ff, $tf, ["c1" => $c1]);
		$col2 = new Setup\ConsumerCollection($ff, $tf, ["c1" => $c1, "c2" => $c2]);
		$col3 = new Setup\ConsumerCollection($ff, $tf, ["c1" => $c1, "c3" => $c3]);
		$col4 = new Setup\ConsumerCollection($ff, $tf, ["c3" => $c3]);
		$col5 = new Setup\ConsumerCollection($ff, $tf, ["c3" => $c3, "c4" => $c4]);

		$this->assertTrue($col1->hasConfig());
		$this->assertTrue($col2->hasConfig());
		$this->assertTrue($col3->hasConfig());
		$this->assertFalse($col4->hasConfig());
		$this->assertFalse($col5->hasConfig());
	}

	public function testGetConfigInput() {
		$ff = $this->createMock(FieldFactory::class);
		$tf = new TransformationFactory();

		$c1 = $this->newConsumer();
		$c2 = $this->newConsumer();
		$c3 = $this->newConsumer();

		$inp1 = $this->newInput();
		$inp2 = $this->newInput();
		$group = $this->newInput();

		foreach([$c1,$c3] as $c) {
			$c
				->expects($this->once())
				->method("hasConfig")
				->willReturn(true);
		}
		$c2
			->expects($this->once())
			->method("hasConfig")
			->willReturn(false);
		$c1
			->expects($this->once())
			->method("getConfigInput")
			->willReturn($inp1);
		$c2
			->expects($this->never())
			->method("getConfigInput");
		$c3
			->expects($this->once())
			->method("getConfigInput")
			->willReturn($inp2);

		$col = new Setup\ConsumerCollection($ff, $tf, ["c1"=>$c1,"c2"=>$c2,"c3"=>$c3]);

		$ff
			->expects($this->once())
			->method("group")
			->with([$inp1, $inp2])
			->willReturn($group);

		$group
			->expects($this->once())
			->method("withAdditionalTransformation")
			->with($this->callback(function(Transformation $t) {
				$conf1 = $this->newConfig();
				$conf2 = $this->newConfig();
				$res = $t->transform([$conf1, $conf2]);
				$this->assertInstanceOf(Setup\ConfigCollection::class, $res);
				$this->assertEquals(["c1", "c3"], $res->getKeys());
				$this->assertEquals($conf1, $res->getConfig("c1"));
				$this->assertEquals($conf2, $res->getConfig("c3"));
				return true;
			}))
			->willReturn($group);

		$res = $col->getConfigInput();

		$this->assertEquals($group, $res);
	}

	public function testGetConfigInputUsesSuppliedConfig() {
		$ff = $this->createMock(FieldFactory::class);
		$tf = new TransformationFactory();

		$c1 = $this->newConsumer();
		$c2 = $this->newConsumer();
		$c3 = $this->newConsumer();

		$inp = $this->newInput();

		$conf1 = $this->newConfig();
		$conf3 = $this->newConfig();

		foreach([$c1,$c3] as $c) {
			$c
				->method("hasConfig")
				->willReturn(true);
		}
		$c2
			->method("hasConfig")
			->willReturn(false);
		$c1
			->expects($this->once())
			->method("getConfigInput")
			->with($conf1)
			->willReturn($inp);
		$c2
			->expects($this->never())
			->method("getConfigInput");
		$c3
			->expects($this->once())
			->method("getConfigInput")
			->with($conf3)
			->willReturn($inp);

		$col = new Setup\ConsumerCollection($ff, $tf, ["c1"=>$c1,"c2"=>$c2,"c3"=>$c3]);

		$ff
			->method("group")
			->willReturn($inp);

		$inp
			->method("withAdditionalTransformation")
			->willReturn($inp);

		$conf = new Setup\ConfigCollection(["c1" => $conf1, "c3" => $conf3]);

		$col->getConfigInput($conf);
	}

	public function testGetConfigFromArray() {
		$ff = $this->createMock(FieldFactory::class);
		$tf = new TransformationFactory();

		$c1 = $this->newConsumer();
		$c2 = $this->newConsumer();
		$c3 = $this->newConsumer();

		$conf1 = $this->newConfig();
		$conf3 = $this->newConfig();

		foreach([$c1,$c3] as $c) {
			$c
				->method("hasConfig")
				->willReturn(true);
		}
		$c2
			->method("hasConfig")
			->willReturn(false);

		$arr = ["c1" => ["c1_data"], "c3" => ["c3_data"]];

		$c1
			->expects($this->once())
			->method("getConfigFromArray")
			->with(["c1_data"])
			->willReturn($conf1);
		$c2
			->expects($this->never())
			->method("getConfigFromArray");
		$c3
			->expects($this->once())
			->method("getConfigFromArray")
			->with(["c3_data"])
			->willReturn($conf3);

		$col = new Setup\ConsumerCollection($ff, $tf, ["c1"=>$c1,"c2"=>$c2,"c3"=>$c3]);
		$conf = $col->getConfigFromArray($arr);

		$this->assertInstanceOf(Setup\ConfigCollection::class, $conf);
		$this->assertEquals(["c1", "c3"], $conf->getKeys());
		$this->assertEquals($conf1, $conf->getConfig("c1"));
		$this->assertEquals($conf3, $conf->getConfig("c3"));
	}

	public function testGetInstallGoal() {
		$ff = $this->createMock(FieldFactory::class);
		$tf = new TransformationFactory();

		$c1 = $this->newConsumer();
		$c2 = $this->newConsumer();

		$g1 = $this->newGoal();
		$g2 = $this->newGoal();

		$conf1 = $this->newConfig();

		$c1
			->expects($this->once())
			->method("hasConfig")
			->willReturn(true);
		$c2
			->expects($this->once())
			->method("hasConfig")
			->willReturn(false);

		$c1
			->expects($this->once())
			->method("getInstallGoal")
			->with($conf1)
			->willReturn($g1);
		$c2
			->expects($this->once())
			->method("getInstallGoal")
			->with()
			->willReturn($g2);

		$col = new Setup\ConsumerCollection($ff, $tf, ["c1"=>$c1,"c2"=>$c2]);
		$conf = new Setup\ConfigCollection(["c1" => $conf1]);

		$g = $col->getInstallGoal($conf);

		$this->assertInstanceOf(Setup\GoalCollection::class, $g);
		$this->assertEquals([$g1, $g2], $g->getGoals());
	}

	public function testGetUpdateGoal() {
		$ff = $this->createMock(FieldFactory::class);
		$tf = new TransformationFactory();

		$c1 = $this->newConsumer();
		$c2 = $this->newConsumer();

		$g1 = $this->newGoal();
		$g2 = $this->newGoal();

		$conf1 = $this->newConfig();

		$c1
			->expects($this->once())
			->method("hasConfig")
			->willReturn(true);
		$c2
			->expects($this->once())
			->method("hasConfig")
			->willReturn(false);

		$c1
			->expects($this->once())
			->method("getUpdateGoal")
			->with($conf1)
			->willReturn($g1);
		$c2
			->expects($this->once())
			->method("getUpdateGoal")
			->with()
			->willReturn($g2);

		$col = new Setup\ConsumerCollection($ff, $tf, ["c1"=>$c1,"c2"=>$c2]);
		$conf = new Setup\ConfigCollection(["c1" => $conf1]);

		$g = $col->getUpdateGoal($conf);

		$this->assertInstanceOf(Setup\GoalCollection::class, $g);
		$this->assertEquals([$g1, $g2], $g->getGoals());
	}

	protected function newConsumer() {
		static $no = 0;

		$consumer = $this
			->getMockBuilder(Setup\Consumer::class)
			->setMethods(["hasConfig", "getDefaultConfig", "getConfigInput", "getConfigFromArray", "getInstallGoal", "getUpdateGoal"])
			->setMockClassName("Mock_ConsumerNo".($no++))
			->getMock();

		return $consumer;
	}

	protected function newGoal() {
		static $no = 0;

		$goal = $this
			->getMockBuilder(Setup\Goal::class)
			->setMethods(["getHash", "getLabel", "isNotable", "withResourcesFrom", "getPreconditions", "achieve"])
			->setMockClassName("Mock_GoalNo".($no++))
			->getMock();

		$goal
			->method("getHash")
			->willReturn("".$no);

		return $goal;
	}

	protected function newInput() {
		static $no = 0;

		$input = $this
			->getMockBuilder(Input::class)
			->setMethods([])
			->setMockClassName("Mock_InputNo".($no++))
			->getMock();

		return $input;
	}

	protected function newConfig() {
		static $no = 0;

		$config = $this
			->getMockBuilder(Setup\Config::class)
			->setMethods([])
			->setMockClassName("Mock_ConfigNo".($no++))
			->getMock();

		return $config;
	}

}