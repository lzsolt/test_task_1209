<?php

	/**
	 * Class dueDate
	 */
	class dueDate
	{
		/**
		 * Working time from to
		 */
		const _BEGIN_WORK_TIME = 9;
		const _END_WORK_TIME = 17;

		/**
		 * Time format 24H or AM/PM
		 */
		const _12H = 12;
		const _24H = 24;

		/**
		 * Error codes
		 */
		const _PARAM_ERR = 1;
		const _OUT_WORK_TIME_ERR = 2;

		/**
		 * Error messages
		 *
		 * @var array
		 */
		private $errMsg = [
			self::_PARAM_ERR => 'Invalid datetime parameter!',
			self::_OUT_WORK_TIME_ERR => 'Submit is out of working time!'
		];

		/**
		 * Array contain date parts: year, month, day
		 *
		 * @var array
		 */
		private $submitDate = array();

		/**
		 * Array contain time parts: hour, time and am/pm format if there included
		 *
		 * @var array
		 */
		private $submitTime = array();

		/**
		 * Save origin time format
		 *
		 * @var int
		 */
		private $givenFormat;

		/**
		 * Turnaround time (it means day(s))
		 *
		 * @var int
		 */
		private $dayNum;

		/**
		 * Error code
		 *
		 * @var
		 */
		private $error;


		/**
		 * Prepare and check given values
		 *
		 * @param $submitDateTime
		 * @param $dayNum
		 */
		public function __construct($submitDateTime, $dayNum) {
			if (($splitResult = $this->splitDateTime($submitDateTime)) === false) {
				return $this->setError(self::_PARAM_ERR);
			} else {
				$this->setDateParts($splitResult[0]);
				$this->setTimeParts($splitResult[1]);
			}

			if (!$this->checkDate() | !$this->checkTime()) {
				return $this->setError(self::_PARAM_ERR);
			}

			if (is_numeric($dayNum) && intval($dayNum) == $dayNum) {
				$this->dayNum = $dayNum;
			} else {
				return $this->setError(self::_PARAM_ERR);;
			}

			$this->givenFormat = $this->getTimeFormat();

			return true;
		}

		/**
		 * Calculate due date
		 *
		 * @return string
		 */
		public function calculate() {
			if ($this->error) {
				return $this->error;
			}

			if ($this->checkWorkingTime() != false) {
				$resultDay = $this->searchValidWorkingDay();

				$this->convertTimeFormat($this->givenFormat);

				$min = ($this->submitTime['min'] < 10 ? '0' . (string) $this->submitTime['min'] : $this->submitTime['min']);

				return date('Y-m-d', $resultDay) . ' ' . $this->submitTime['hour'] . ':' . $min . (isset($this->submitTime['ampm']) ? ' ' . $this->submitTime['ampm'] : '');
			}

			return $this->error;
		}

		/**
		 * Search next valid working day
		 *
		 * @return int
		 */
		private function searchValidWorkingDay() {
			$tempDayNum = $this->dayNum;

			$foundValidDay = false;

			$searchDay = strtotime($this->submitDate['month'] . '/' . $this->submitDate['day'] . '/' . $this->submitDate['year']);

			for ($i = 1; $i <= $tempDayNum | !$foundValidDay; $i++) {
				$searchDay += 86400;

				if (date('N', $searchDay) <= 5) {
					$foundValidDay = true;
				} else {
					$foundValidDay = false;
					$i--;
				}
			}

			return $searchDay;
		}

		/**
		 * Check the given time is in working time
		 *
		 * @return bool
		 */
		private function checkWorkingTime() {
			$this->convertTimeFormat();

			if ($this->submitTime['hour'] < self::_BEGIN_WORK_TIME || $this->submitTime['hour'] >= self::_END_WORK_TIME) {
				return $this->setError(self::_OUT_WORK_TIME_ERR);
			} else {
				return true;
			}
		}

		/**
		 * Separate date and time
		 *
		 * @param $dateTimeStr
		 * @return array|bool
		 */
		private function splitDateTime($dateTimeStr) {
			$parts = explode(' ', $dateTimeStr, 2);

			if (count($parts) == 2) {
				return $parts;
			} else {
				return false;
			}
		}

		/**
		 * Check time format 12H or 24H
		 *
		 * @return int
		 */
		private function getTimeFormat() {
			if (isset($this->submitTime['ampm'])) {
				return self::_12H;
			} else {
				return self::_24H;
			}
		}

		/**
		 * Convert time format
		 *
		 * @param int $toFormat
		 */
		private function convertTimeFormat($toFormat = self::_24H) {
			if ($toFormat == self::_12H && !isset($this->submitTime['ampm'])) {
				$this->submitTime['ampm'] = date("A", strtotime($this->submitTime['hour'] . ':00'));
				$this->submitTime['hour'] = date("g", strtotime($this->submitTime['hour'] . ':00'));
			} elseif ($toFormat == self::_24H && isset($this->submitTime['ampm'])) {
				$this->submitTime['hour'] = date("H", strtotime($this->submitTime['hour'] . ':00 ' . $this->submitTime['ampm']));
				unset($this->submitTime['ampm']);
			}
		}

		/**
		 * String date value split to array year, month and day part
		 *
		 * @param $submitDate
		 */
		private function setDateParts($submitDate) {
			foreach (['-', '/'] as $dateSeparator) {
				$dateParts = explode($dateSeparator, $submitDate);

				if (count($dateParts) != 3) {
					continue;
				} else {
					$onlyNum = str_replace($dateSeparator, '', $submitDate);

					if (is_numeric($onlyNum)) {
						$this->submitDate = ['year' => $dateParts[0], 'month' => $dateParts[1], 'day' => $dateParts[2]];
					}
				}
			}
		}

		/**
		 * String time value split to hour, min and AM/PM part if format is 12H
		 *
		 * @param $submitTime
		 */
		private function setTimeParts($submitTime) {
			$submitTime = strtoupper($submitTime);
			$timeParts = explode(':', $submitTime);

			if (is_numeric($timeParts[0])) {
				$hour = $timeParts[0];

				if (is_numeric($timeParts[1])) {
					$min = $timeParts[1];
				} else {
					if (strpos($timeParts[1], 'AM') > 1) {
						$ampm = 'AM';
					} elseif (strpos($timeParts[1], 'PM') > 1) {
						$ampm = 'PM';
					}

					if (isset($ampm)) {
						$tempMin = trim(str_replace($ampm, '', $timeParts[1]));

						if (is_numeric($tempMin)) {
							$min = $tempMin;
						}
					}
				}
			}

			if (isset($hour) && isset($min)) {
				$this->submitTime = ['hour' => (int) $hour, 'min' => (int) $min];

				if (isset($ampm)) {
					$this->submitTime['ampm'] = $ampm;
				}
			}
		}

		/**
		 * Check valid date
		 *
		 * @return bool
		 */
		private function checkDate() {
			if (count($this->submitDate) == 3) {
				return checkdate($this->submitDate['month'], $this->submitDate['day'], $this->submitDate['year']);
			} else {
				return false;
			}
		}

		/**
		 * Check valid time
		 *
		 * @return bool
		 */
		private function checkTime() {
			if (!isset($this->submitTime['hour'])) {
				return false;
			}

			if (!is_numeric($this->submitTime['min']) || $this->submitTime['min'] < 0 || $this->submitTime['min'] > 59) {
				return false;
			}

			if (!is_numeric($this->submitTime['hour']) || $this->submitTime['hour'] < 0 || $this->submitTime['hour'] > 23) {
				return false;
			}

			if (isset($this->submitTime['ampm']) && ($this->submitTime['hour'] > 12 || $this->submitTime['hour'] < 1)) {
				return false;
			}

			return true;
		}

		/**
		 * Save error code
		 *
		 * @param $errType
		 * @return bool
		 */
		private function setError($errType) {
			$this->error = $errType;
			return false;
		}

		/**
		 * Get error message via error code
		 *
		 * @return string
		 */
		public function getErrorMsg() {
			if ($this->error) {
				return $this->errMsg[$this->error] . " [Error code: {$this->error}]";
			} else {
				return '';
			}
		}
	}