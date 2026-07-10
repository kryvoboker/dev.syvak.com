<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Services;

/**
 * Class ScheduleFormatter
 * Formats work schedule from JSON or array into human readable Ukrainian text.
 */
class ShippingScheduleFormatter
{
	// Map English weekday keys to Ukrainian short names
	private static array $weekday_map;
	// Order of days to ensure correct grouping
	private static array $ordered_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

	/**
	 * Format schedule from JSON string or associative array.
	 *
	 * @param string|array $input              JSON string or associative array like ["Monday":"09:00-18:00", ...]
	 * @param string       $text_day_off       Text to use for closed days
	 * @param string       $text_work_schedule Header text for the schedule
	 *
	 * @return string Human friendly schedule in Ukrainian
	 */
	public static function formatSchedule(string|array $input, string $text_day_off, string $text_work_schedule): string
	{
		// Convert input to associative array if JSON provided
		if (is_string($input)) {
			$decoded = json_decode($input, true);

			if (!is_array($decoded)) {
				return 'Невірний формат графіку';
			}

			$schedule_array = $decoded;
		} else {
			$schedule_array = $input;
		}

		// Normalize values and build list in order
		$lines = [];
		foreach (self::$ordered_days as $day_key) {
			$raw_value = $schedule_array[$day_key] ?? '-';

			// Normalize different possible formats
			if (is_array($raw_value)) {
				// join array values with comma
				$value = implode(', ', $raw_value);
			} else {
				$value = trim((string)$raw_value);
			}

			// Interpret dash or empty as closed
			if ($value === '' || $value === '-' || strcasecmp($value, 'closed') === 0) {
				$value = $text_day_off;
			}

			$lines[] = [
				'key'   => $day_key,
				'short' => self::$weekday_map[$day_key],
				'value' => $value,
			];
		}

		// Group consecutive days with identical value
		$groups      = [];
		$start_index = 0;
		$count       = count($lines);

		for ($i = 1; $i <= $count; $i++) {
			// When value changes or reached end, close current group
			if ($i === $count || $lines[$i]['value'] !== $lines[$start_index]['value']) {
				$groups[] = [
					'start' => $start_index,
					'end'   => $i - 1,
					'value' => $lines[$start_index]['value'],
				];

				$start_index = $i;
			}
		}

		// Build human readable lines
		$result_lines   = [];
		$result_lines[] = $text_work_schedule;

		foreach ($groups as $group) {
			$start = $lines[$group['start']]['short'];
			$end   = $lines[$group['end']]['short'];
			$value = $group['value'];

			if ($group['start'] === $group['end']) {
				// Single day
				$result_lines[] = '<span class="font-medium">' . sprintf('%s: %s', $start, $value) . '</span>';
			} else {
				// Range of days
				$result_lines[] = '<span class="font-medium">' . sprintf('%s-%s: %s', $start, $end, $value) . '</span>';
			}
		}

		return implode('<br/>', $result_lines);
	}

	/**
	 * @param array $weekday_map
	 *
	 * @return void
	 */
	public static function setWeekdayMap(array $weekday_map): void
	{
		self::$weekday_map = $weekday_map;
	}

	/**
	 * @param array $ordered_days
	 *
	 * @return void
	 */
	public static function setOrderedDays(array $ordered_days): void
	{
		self::$ordered_days = $ordered_days;
	}
}
