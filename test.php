<?php
	include('dueDate.php');

	$testDTs = [
		1 => '2016/12/09 12:59 PM',
		2 => '2016-12-09 12:59 PM',
		3 => '2016/12/09 14:25',
		4 => '2016-12-09 14:25',
		5 => '2016/12/09 10:45 am',
		6 => '2016-12-09 10:45 am',
		7 => '2016/11/29 9:15 AM',
		8 => '2016-11-29 9:15 AM',
		9 => '2016/11/29 09:00',
		10 => '2016-11-29 09:00',
		11 => '2016/12/29 4:59 PM',
		12 => '2016-12-29 4:59 PM',
		13 => '2016/12/29 5:00 PM',
		14 => '2016-12-29 5:00 PM',
		15 => '',
		16 => '123',
		17 => 'asdf',
		18 => '45/13/59 57:98',
		19 => 'sdse/fe/se fe:aa'
	];

	foreach ($testDTs as $i => $testDT) {
		echo $i . ' - ' . $testDT . PHP_EOL;

		$dueDate = new dueDate($testDT, 4);

		if (is_numeric($resultDateTime = $dueDate->calculate())) {
			echo $dueDate->getErrorMsg();
		} else {
			echo 'Due Date: '.$resultDateTime;
		}

		echo PHP_EOL . PHP_EOL;
	}