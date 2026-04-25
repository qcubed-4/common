<?php

    use QCubed\Test\UnitTestCaseBase;
    use QCubed\Timer;

    /**
 * 
 * @package Tests
 */
class QTimerTests extends UnitTestCaseBase {
	public function testTimerBasic() {
		Timer::start('timer1');
		$this->longOperation();
		$fltValue1 = Timer::stop('timer1');
		$fltValue2 = Timer::getTime('timer1');
		
		$this->assertTrue($fltValue1 > 0);
		
		// Comparing doubles for equality - using epsilon
		$this->assertTrue(abs($fltValue1 - $fltValue2) < 0.000000001);
	}

	public function testTimerResume() {
		Timer::start('timer2');
		$this->longOperation();
		$fltValue1 = Timer::stop('timer2');
		
		$this->assertTrue($fltValue1 > 0);

		Timer::start('timer2');
		$this->longOperation();
		$fltValue2 = Timer::stop('timer2');

		Timer::start('timer2');
		$this->longOperation();
		$fltValue3 = Timer::stop('timer2');
		
		// Comparing doubles - using epsilon
		$this->assertTrue($fltValue1 > 0); 
		$this->assertTrue($fltValue2 > 0); 
		$this->assertTrue($fltValue3 > 0); 
		$this->assertTrue($fltValue1 < $fltValue2);
		$this->assertTrue($fltValue2 < $fltValue3);
		
		$objTimer = Timer::GetTimer('timer2');
		$this->assertEquals(3, $objTimer->CountStarted);
	}
	
	public function testReset() {
		Timer::start('timerA');
		$this->longOperation();
		$fltValue1 = Timer::GetTime('timerA');
		$this->longOperation();
		$fltValue2 = Timer::GetTime('timerA');
		$this->longOperation();
		$fltValue3 = Timer::reset('timerA');
		$fltValue4 = Timer::stop('timerA');

		$this->assertTrue($fltValue1 > 0); 
		$this->assertTrue($fltValue2 > 0); 
		$this->assertTrue($fltValue3 > 0); 
		$this->assertTrue($fltValue4 > 0);
		$this->assertTrue($fltValue1 < $fltValue2);
		$this->assertTrue($fltValue2 < $fltValue3);
		$this->assertTrue($fltValue4 < $fltValue3); // because we've reset the timer
		
		$objTimer = Timer::GetTimer('timerA');
		$this->assertEquals(2, $objTimer->CountStarted);
	}
	

	public function testExceptions1() {
		// requires v 5.3 of PHP UNIT
		$this->setExpectedException("\QCubed\Exception\Caller");
		Timer::stop('timer4');
	}
	
	public function testExceptions2() {		
		$this->setExpectedException("\QCubed\Exception\Caller");
		Timer::getTime('timer5');
	}
	
	public function testExceptions3() {		
		Timer::start('timer6');
		$this->setExpectedException("\QCubed\Exception\Caller");
		Timer::start('timer6');
	}
	
	public function testExceptions4() {
		$objTimer = Timer::GetTimer('timer7');
		$this->assertEquals(null, $objTimer, "Requests for non-existing timer objects should return null");
	}


	private function longOperation() {
		$a = [];
		for($i = 0; $i < 10000; $i++) {
		    $a[] = rand(1,5000);
        }
        ksort($a);
	}
}