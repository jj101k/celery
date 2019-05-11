<?php
/**
 * Tests that the whole lot of work can be done quickly.
 */
class TimingTest extends \PHPUnit\Framework\TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test() {
        ob_start(function($buffer) {return "";});

        // Now to do the job
        $s = microtime(true);
        require_once "test/sample.php";
        $r = microtime(true) - $s;
        // All done!

        ob_end_flush();
        $this->assertLessThan(
            0.004,
            $r,
            "All handling complete in < 4ms"
        );
    }
}
