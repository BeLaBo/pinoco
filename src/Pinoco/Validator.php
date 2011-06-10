<?php
/**
 * Pinoco web site environment
 * It makes existing static web site dynamic transparently.
 *
 * PHP Version 5
 *
 * @category Framework
 * @package  Pinoco
 * @author   Hisateru Tanaka <tanakahisateru@gmail.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  0.4.0
 * @link     https://github.com/tanakahisateru/pinoco
 * @filesource
 */

/**
 */
require_once dirname(__FILE__) . '/VarsList.php';

/**
 * Procedual varidation utility.
 * <code>
 * $validator = new Pinoco_Validator($data);
 * $validator->check('name')->is('not-empty')->is('max-length 255');
 * $validator->check('age')->is('not-empty')->is('integer')
 *                         ->is('>= 21', 'Adult only.');
 * // check $validator->name->invalid then use $validator->name->message.
 * </code>
 *
 * Builtin tests:
 *   pass, fail, empty, not-empty, max-length, min-length, in, not-in,
 *   numeric, integer, alpha, alpha-numeric, ==, !=, >, >=, <,  <=,
 *   match, not-match, email,url
 *
 * @package Pinoco
 */
class Pinoco_Validator extends Pinoco_Vars {

    private $_tests;
    private $_messages;
    
    private $_target;
    private $_current;
    private $_alreadyFixed;

    /**
     * Constructor
     * @param string $target
     * @param string $message
     */
    public function __construct($target, $messages=array())
    {
        parent::__construct();
        
        $this->_tests = array();
        $this->_messages = array();
        $this->overrideErrorMessages($messages);
        
        // builtin testers
        $this->defineValidityTest('pass', array($this, '_testPass'),
            "Valid.");
        $this->defineValidityTest('fail', array($this, '_testFail'),
            "Invalid.");
        $this->defineValidityTest('empty', array($this, '_testEmpty'),
            "Leave as empty.");
        $this->defineValidityTest('not-empty', array($this, '_testNotEmpty'),
            "Reqierd.");
        $this->defineValidityTest('max-length', array($this, '_testMaxLength'),
            "In {0} letters.");
        $this->defineValidityTest('min-length', array($this, '_testMinLength'),
            "At least {0} letters.");
        $this->defineValidityTest('in', array($this, '_testIn'),
            "Coose in {0}.");
        $this->defineValidityTest('not-in', array($this, '_testNotIn'),
            "Choose else of {0}.");
        $this->defineValidityTest('numeric', array($this, '_testNumeric'),
            "By number.");
        $this->defineValidityTest('integer', array($this, '_testInteger'),
            "By integer number.");
        $this->defineValidityTest('alpha', array($this, '_testAlpha'),
            "Alphabet only.");
        $this->defineValidityTest('alpha-numeric', array($this, '_testAlphaNumeric'),
            "Alphabet or number.");
        $this->defineValidityTest('==', array($this, '_testEqual'),
            "Shuld equal to {0}.");
        $this->defineValidityTest('!=', array($this, '_testNotEqual'),
            "Should not equal to {0}.");
        $this->defineValidityTest('>', array($this, '_testGreaterThan'),
            "Greater than {0}.");
        $this->defineValidityTest('>=', array($this, '_testGreaterThanOrEqual'),
            "Greater than or equals to {0}.");
        $this->defineValidityTest('<', array($this, '_testLessorThan'),
            "Lessor than {0}.");
        $this->defineValidityTest('<=', array($this, '_testLessorThanOrEqual'),
            "Lessor than or equals to {0}.");
        $this->defineValidityTest('match', array($this, '_testMatch'),
            "Invalid pattern.");
        $this->defineValidityTest('not-match', array($this, '_testNotMatch'),
            "Not allowed pattern.");
        $this->defineValidityTest('email', array($this, '_testEmail'),
            "Email only.");
        $this->defineValidityTest('url', array($this, '_testUrl'),
            "URL only.");
        
        $this->_target = $target;
        $this->_current = null;
    }
    
    /**
     * Defines custom test
     * @param string $testName
     * @param callable $callback
     * @param string $message
     * @return void
     */
    public function defineValidityTest($testName, $callback, $message)
    {
        $this->_tests[$testName] = array(
            'callback' => $callback,
            'message' => $message
        );
    }
    
    /**
     * Overrides messages for l10n
     * @param array $messages
     * @return void
     */
    public function overrideErrorMessages($messages)
    {
        foreach($messages as $test=>$msg) {
            $this->_messages[$test] = $msg;
        }
    }
    
    /**
     * Starts property checking.
     * @param string $name
     * @return Pinoco_Validator
     */
    public function check($name)
    {
        if(!$this->has($name)) {
            $r = Pinoco_Vars::fromArray(array(
                'field'=>$name,
                'valid'=>true,
                'invalid'=>false
            ));
            $r->setLoose(true);
            $this->set($name, $r);
        }
        $this->_current = $this->get($name);
        $this->_alreadyFixed = false;
        return $this;
    }
    
    private function buildMessage($template, $params)
    {
        $target = array();
        $replacement = array();
        foreach($params as $k=>$v) {
            $target[] = '{'.$k.'}';
            $replacement[] = strval($v);
        }
        return str_replace($target, $replacement, $template);
    }
    
    private function _execute($test, $message=false) {
        $params = explode(' ', $test);
        $testName = array_shift($params);
        if(!isset($this->_tests[$testName])) {
            $this->_current->test = $test;
            $this->_current->valid = false;
            $this->_current->invalid = true;
            $this->_current->message = $testName . ' is not registered.';
        }
        else {
            $field = $this->_current->field;
            //type check
            if($this->_target instanceof Pinoco_Vars) {
                $exists = $this->_target->has($field);
                $value = $this->_target->get($field);
            }
            if($this->_target instanceof Pinoco_List) {
                $exists = intval($field) < $this->_target->count();
                $value = $exists ? $this->_target[$field] : null;
            }
            else if(is_array($this->_target)) {
                $exists = isset($this->_target[$field]);
                $value = $exists ? $this->_target[$field] : null;
            }
            else if(is_object($this->_target)) {
                $exists = isset($this->_target->$field);
                $value = $exists ? $this->_target->$field : null;
            }
            else {
                $pass = true;
            }
            // main
            if(@$pass) {
                $result = false;
            }
            else {
                $args = $params;
                array_unshift($args, $value);
                array_unshift($args, $exists);
                array_unshift($args, $field);
                array_unshift($args, $this->_target);
                $result = call_user_func_array(
                    $this->_tests[$testName]['callback'], $args);
            }
            if($result) {
                if(isset($this->_messages['pass'])) {
                    $template = $this->_messages[$testName];
                }
                else if(isset($this->_tests['pass'])){
                    $template = $this->_tests['pass']['message'];
                }
                else {
                    $template = "Valid.";
                }
                $this->_current->test = $test;
                $this->_current->valid = true;
                $this->_current->invalid = false;
                $this->_current->message = $this->buildMessage($template, $params);
            }
            else {
                if($message) {
                    $template = $message;
                }
                else if(isset($this->_messages[$testName])) {
                    $template = $this->_messages[$testName];
                }
                else {
                    $template = $this->_tests[$testName]['message'];
                }
                $this->_current->test = $test;
                $this->_current->valid = false;
                $this->_current->invalid = true;
                $this->_current->message = $this->buildMessage($template, $params);
            }
        }
    }
    
    /**
     * Check a field by specified test.
     * @param string $test
     * @param string $message
     * @return Pinoco_Validator
     */
    public function is($test, $message=false)
    {
        if($this->_alreadyFixed) {
            return $this;
        }
        if($this->_current->valid) {
            $this->_execute($test, $message);
        }
        else {
            // pass
        }
        return $this;
    }
    
    /**
     * Chains other tests by logical OR.
     * @return Pinoco_Validator
     */
    public function altcheck()
    {
        if($this->_current->valid) {
            $this->_alreadyFixed = true;
        }
        else {
            $this->_current->valid = true;
            $this->_current->invalid = false;
            $this->_current->message = 'Fine';
        }
        return $this;
    }
    
    /**
     * Alias for is() method.
     * @param string $test
     * @param string $message
     * @return Pinoco_Validator
     */
    public function andIs($test, $message=false)
    {
        return $this->is($test, $message);
    }
    
    /**
     * Alias for altcheck()->is() combination.
     * @param string $test
     * @param string $message
     * @return Pinoco_Validator
     */
    public function orIs($test, $message=false)
    {
        return $this->altcheck()->is($test, $message);
    }
    
    /**
     * Exports test results that failed.
     * @return Pinoco_Vars
     */
    public function errors() {
        $errors = new Pinoco_Vars();
        foreach($this as $field=>$result) {
            if($result->invalid) {
                $errors->set($field, $result);
            }
        }
        return $errors;
    }
    
    /////////////////////////////////////////////////////////////////////
    // builtin tests
    private function _testPass($target, $name, $exists, $value)
    {
        return true;
    }
    private function _testFail($target, $name, $exists, $value)
    {
        return false;
    }
    private function _testEmpty($target, $name, $exists, $value)
    {
        if(!$exists || $value === null) { return true; }
        if($value === "0" || $value === 0 || $value === false) { return false; }
        return empty($value);
    }
    private function _testNotEmpty($target, $name, $exists, $value)
    {
        return !$this->_testEmpty($target, $name, $exists, $value);
    }
    private function _testMaxLength($target, $name, $exists, $value, $cond0=0)
    {
        return strlen(strval($value)) <= $cond0;
    }
    private function _testMinLength($target, $name, $exists, $value, $cond0=0)
    {
        return strlen(strval($value)) >= $cond0;
    }
    private function _testIn($target, $name, $exists, $value, $cond0='')
    {
        $as = explode(',', $cond0);
        foreach($as as $a) {
            if($value == $a) { return true; }
        }
        return false;
    }
    private function _testNotIn($target, $name, $exists, $value, $cond0='')
    {
        return !$this->_testIn($target, $name, $exists, $value, $cond0);
    }
    private function _testNumeric($target, $name, $exists, $value)
    {
        return is_numeric($value);
    }
    private function _testInteger($target, $name, $exists, $value)
    {
        return is_integer($value);
    }
    private function _testAlpha($target, $name, $exists, $value)
    {
        return ctype_alpha($value);
    }
    private function _testAlphaNumeric($target, $name, $exists, $value)
    {
        return ctype_alnum($value);
    }
    private function _testEqual($target, $name, $exists, $value, $cond0=null)
    {
        return $value == $cond0;
    }
    private function _testNotEqual($target, $name, $exists, $value, $cond0=null)
    {
        return !$this->_testEqual($target, $name, $exists, $value, $cond0);
    }
    private function _testGreaterThan($target, $name, $exists, $value, $cond0=0)
    {
        return $value > $cond0;
    }
    private function _testGreaterThanOrEqual($target, $name, $exists, $value, $cond0=0)
    {
        return $value >= $cond0;
    }
    private function _testLessorThan($target, $name, $exists, $value, $cond0=0)
    {
        return $value < $cond0;
    }
    private function _testLessorThanOrEqual($target, $name, $exists, $value, $cond0=0)
    {
        return $value <= $cond0;
    }
    private function _testMatch($target, $name, $exists, $value, $cond0='/^$/')
    {
        return preg_match($cond0, $value);
    }
    private function _testNotMatch($target, $name, $exists, $value, $cond0='/^$/')
    {
        return !$this->_testMatch($target, $name, $exists, $value, $cond0);
    }
    private function _testEmail($target, $name, $exists, $value)
    {
        return preg_match('/@[A-Z0-9][A-Z0-9_-]*(\.[A-Z0-9][A-Z0-9_-]*)*$/i', $value);
    }
    private function _testUrl($target, $name, $exists, $value)
    {
        return preg_match('/^[A-Z]+:\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)*):?(\d+)?\/?/i', $value);
    }
}
