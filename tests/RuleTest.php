<?php

use Mockery as m;

use Authority\Authority;
use Authority\Rule;

class RuleTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->rule = new Rule(true, 'read', m::mock('Obj'));
        $this->auth = new Authority(new stdClass);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testCanBeSetToAllowOrDeny()
    {
        $allowed_rule = new Rule(true, 'read', m::mock('Obj'));
        $denied_rule = new Rule(false, 'write', m::mock('Obj'));
        $this->assertTrue($allowed_rule->getBehavior());
        $this->assertTrue($allowed_rule->isPrivilege());

        $this->assertFalse($denied_rule->getBehavior());
        $this->assertTrue($denied_rule->isRestriction());
    }

    public function testCanMatchAction()
    {
        $this->assertTrue($this->rule->matchesAction('read'));
        $this->assertFalse($this->rule->matchesAction('write'));
    }

    public function testCanMatchResource()
    {
        $this->assertTrue($this->rule->matchesResource(m::mock('Obj')));
        $this->assertTrue($this->rule->matchesResource('Mockery\\Mock'));
        $this->assertFalse($this->rule->matchesResource('Duck'));
    }

    public function testCanDetermineRelevance()
    {
        $this->assertTrue($this->rule->isRelevant('read', 'Mockery\\Mock'));
        $this->assertTrue($this->rule->isRelevant(array('read', 'write'), 'Mockery\\Mock'));
        $this->assertFalse($this->rule->isRelevant('write', 'Mockery\\Mock'));
    }

    public function testCanSetAndCheckIfAllowed()
    {
        $rule = new Rule(true, 'read', 'stdClass');
        $this->assertTrue($rule->isAllowed());

        $rule2 = new Rule(false, 'read', 'stdClass');
        $this->assertFalse($rule2->isAllowed());
    }

    public function testCanSetAndCheckPrivilegeAgainstConditions()
    {
        $object1 = new stdClass;
        $object1->id = 1;

        $object2 = new stdClass;
        $object2->id = 2;

        $rule = new Rule(true, 'read', 'stdClass', function($obj) { return $obj->id == 1; });
        $this->assertTrue($rule->isAllowed($this->auth, [$object1]));
        $this->assertFalse($rule->isAllowed($this->auth, [$object2]));

        $rule->when(function($obj) { return 1 == 2; });

        $this->assertFalse($rule->isAllowed($this->auth, [$object1]));
        $this->assertFalse($rule->isAllowed($this->auth, [$object2]));
    }

    public function testCanSetAndCheckPrivilegeAgainstMultipleConditions()
    {
        $object1 = new stdClass;
        $object1->id = 1;

        $object2 = new stdClass;
        $object2->id = 2;

        $rule = new Rule(true, 'read', 'stdClass', function($obj1, $obj2) { return $obj1->id == 1 && $obj2->id == 2; });
        $params = [$object1, $object2];
        $this->assertTrue($rule->isAllowed($this->auth, $params));
        $this->assertFalse($rule->isAllowed($this->auth, [$object1, new class() { public $id = 3; }]));

        $rule->when(function($obj) { return 1 == 2; });

        $this->assertFalse($rule->isAllowed($this->auth, $params));
    }


    public function testCanSetAndCheckRestrictionAgainstConditions()
    {
        $object1 = new stdClass;
        $object1->id = 1;

        $object2 = new stdClass;
        $object2->id = 2;

        $rule = new Rule(false, 'read', 'stdClass', function($obj) { return $obj->id == 1; });

        $this->assertFalse($rule->isAllowed($this->auth, [$object1]));
        $this->assertTrue($rule->isAllowed($this->auth, [$object2]));

        $rule->when(function($obj) { return 1 == 2; });

        $this->assertFalse($rule->isAllowed($this->auth, [$object1]));
        $this->assertTrue($rule->isAllowed($this->auth, [$object2]));

        $rule->when(function($obj) { return 1 == 1; });

        $this->assertFalse($rule->isAllowed($this->auth, [$object1]));
        $this->assertFalse($rule->isAllowed($this->auth, [$object2]));
    }
}
