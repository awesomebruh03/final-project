// Which month and range the user is looking at.
// Shared by the header controls (month stepper, range dropdown)
// and every page, so they always agree.

import { addMonths, currentMonthKey } from './format.js';

export const view = $state({
	month: currentMonthKey(), // "YYYY-MM"
	range: 6 // how many months the trend charts show: 3, 6 or 12
});

export function prevMonth() {
	view.month = addMonths(view.month, -1);
}

// Can't go into the future.
export function canGoNext() {
	return view.month < currentMonthKey();
}

export function nextMonth() {
	if (canGoNext()) {
		view.month = addMonths(view.month, 1);
	}
}
