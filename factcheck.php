<?php

namespace atst\factcheck;

require "vendor/autoload.php";

use iter;

const DEFAULT_NUMBER_OF_TEST_CASES=1000;

function ints($min = null, $max = null) {
    return iter\chain(
        iter\filter(iter\fn\operator("!==", null), [$min, $max]),
        random_values("rand", $min ?: 0, $max ?: 1000000)
    );
}

function always($value) {
    return iter\repeat($value);
}

function unique($elements) {
    $seen = array();
    foreach ($elements as $element) {
        if (!in_array($element, $seen)) {
            $seen[] = $element;
            yield $element;
        }
    }
}

function for_all(/** [numberOfTestCases], [generator[, generator[, generator...]]], [callable test] */) {

    list($numberOfTestCases, $generators, $test) = parse_for_all_args(func_get_args());
    $testCases = generate_test_cases($numberOfTestCases, $generators);

    $testGenerator = function ($test) use ($testCases) {
        return function($test) use ($testCases) {

            $passed = 0;
            foreach ($testCases as $testCase) {
                if (!call_user_func_array($test, $testCase)) {
                    throw new TestFailure($test, $testCase);
                }
                $passed++;
            }
         
            return $passed;
        };
    };

    return null !== $testGenerator ? $testGenerator($test) : $testGenerator;
}

function generate_test_cases($testCases, $generators) {
    return iter\take($testCases, call_user_func_array("iter\zip", $generators));
}
 
function factcheck(/** test, [int numberOfTestCases], [generator[, generator[, generator...]]] */) {

    $args = func_get_args();
 
    $test = array_shift($args);

    $numberOfTestCases = DEFAULT_NUMBER_OF_TEST_CASES;
    if (is_int($args[0])) {
        $numberOfTestCases = array_shift($args);
    }
 
    $generators = $args;
 
    try {
        $forAllArgs = array_merge([$numberOfTestCases], $generators, [$test]);
        $testCheck = call_user_func_array('atst\factcheck\for_all', $forAllArgs);

        $passes = $testCheck($test);

    } catch (TestFailure $e) {
        echo "*** Failed!\n" . var_export($e->test, true);
        return false;
    }
 
    echo "+++ OK, passed $passes tests.\n";
    return true;
}

class TestFailure extends \Exception
{
    public $test;
    public $testCase;
 
    public function __construct($test, $testCase)
    {
        $this->test = $test;
        $this->testCase = $testCase;
    }
}

/** @api private */
function parse_for_all_args($args)
{
    $numberOfTestCases = DEFAULT_NUMBER_OF_TEST_CASES;

    if (is_int($args[0])) {
        $numberOfTestCases = array_shift($args);
    }

    $test = null;

    if (is_callable(end($args))) {
        $test = array_pop($args);
    }

    $generators = $args;

    return [$numberOfTestCases, $generators, $test];
}
/** @api private */
function random_values(/* $fn,  args */) {
    $args = func_get_args();
    $fn = array_shift($args);

    while (true) 
        yield call_user_func_array($fn, $args);
}
