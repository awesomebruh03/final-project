// Everything money-, date- and time-related in the app goes through these
// helpers, so changing the currency later is a one-line change.
//
// Dates are always parsed from their parts ("2026-11-11" -> 2026, 11, 11)
// and never with new Date("2026-11-11"), because that one is read as UTC
// and can shift the day by one in some time zones.

// The currency symbol shown in front of every amount.
const CURRENCY = '$';

// Turns a number into money text, e.g. 1390.5 -> "$1,390.50".
export function formatMoney(amount) {
	const text = Number(amount || 0).toLocaleString('en-US', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2
	});
	return CURRENCY + text;
}

// Same, without cents: 1390.5 -> "$1,391".
export function formatMoneyRound(amount) {
	const text = Math.round(Number(amount || 0)).toLocaleString('en-US');
	return CURRENCY + text;
}

// "2026-11-11" -> a local Date (at midnight).
export function parseDate(dateString) {
	const [year, month, day] = dateString.split('-').map(Number);
	return new Date(year, month - 1, day);
}

// A local Date -> "YYYY-MM-DD".
export function dateToString(date) {
	const month = String(date.getMonth() + 1).padStart(2, '0');
	const day = String(date.getDate()).padStart(2, '0');
	return date.getFullYear() + '-' + month + '-' + day;
}

// Today's date as "YYYY-MM-DD" (the format the backend expects).
// Uses the local clock, not UTC, so the date stays correct around midnight.
export function todayString() {
	return dateToString(new Date());
}

// The time right now as "HH:MM" (24 hour clock, what <input type="time"> uses).
export function nowTimeString() {
	const now = new Date();
	return String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
}

// "2026-11-11" -> "Nov 11, 2026".
export function formatDate(dateString) {
	return parseDate(dateString).toLocaleDateString('en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric'
	});
}

// "13:09" -> "1:09 PM".
export function formatTime(timeString) {
	if (!timeString) return '';
	const [hours, minutes] = timeString.split(':').map(Number);
	const suffix = hours >= 12 ? 'PM' : 'AM';
	const hour12 = hours % 12 === 0 ? 12 : hours % 12;
	return hour12 + ':' + String(minutes).padStart(2, '0') + ' ' + suffix;
}

// "2026-11-11" -> "Today, Nov 11" / "Yesterday, Nov 10" / "Mon, Nov 9".
export function formatDayHeading(dateString) {
	const date = parseDate(dateString);
	const short = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

	const today = new Date();
	const yesterday = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
	if (dateString === dateToString(today)) return 'Today, ' + short;
	if (dateString === dateToString(yesterday)) return 'Yesterday, ' + short;

	const weekday = date.toLocaleDateString('en-US', { weekday: 'short' });
	return weekday + ', ' + short;
}

// ----- Month keys: "YYYY-MM" -----

export function currentMonthKey() {
	return todayString().slice(0, 7);
}

// "2026-11" shifted by n months (n can be negative): ("2026-11", -1) -> "2026-10".
export function addMonths(monthKey, n) {
	const [year, month] = monthKey.split('-').map(Number);
	const date = new Date(year, month - 1 + n, 1);
	return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
}

// "2026-11" -> 30
export function daysInMonth(monthKey) {
	const [year, month] = monthKey.split('-').map(Number);
	return new Date(year, month, 0).getDate();
}

// "2026-11" -> "November 2026"
export function monthLabel(monthKey) {
	const [year, month] = monthKey.split('-').map(Number);
	return new Date(year, month - 1, 1).toLocaleDateString('en-US', {
		month: 'long',
		year: 'numeric'
	});
}

// "2026-11" -> "November"
export function monthName(monthKey) {
	const [year, month] = monthKey.split('-').map(Number);
	return new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'long' });
}

// "2026-11" -> "Nov"
export function monthShort(monthKey) {
	const [year, month] = monthKey.split('-').map(Number);
	return new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'short' });
}
