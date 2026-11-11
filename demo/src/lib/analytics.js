// All the numbers shown on the Overview / Insights / Categories pages.
// Everything here is a plain function: lists in, numbers out. No backend calls,
// so it is easy to test and the pages stay short.
//
// An "expense" here is the normalized object from data.svelte.js:
//   { id, amount, date: 'YYYY-MM-DD', time: 'HH:MM'|null, category, note, nature, recurring, frequency }
// A "month key" is 'YYYY-MM'.

import {
	addMonths,
	currentMonthKey,
	daysInMonth,
	monthShort,
	parseDate,
	todayString
} from './format.js';

// ----- Small helpers -----

const keyOf = (name) => String(name).trim().toLowerCase();

export function sum(list) {
	let total = 0;
	for (const item of list) total += item.amount;
	return total;
}

export function inMonth(list, monthKey) {
	return list.filter((item) => item.date.startsWith(monthKey));
}

// Change in percent, or null when there is nothing to compare with.
export function pctChange(current, previous) {
	if (!previous) return null;
	return ((current - previous) / previous) * 100;
}

// ----- Category colours -----

const PALETTE = [
	'#0f766e', // teal
	'#2563eb', // blue
	'#d97706', // amber
	'#9333ea', // purple
	'#be185d', // crimson
	'#0891b2', // cyan
	'#65a30d', // lime
	'#ea580c', // orange
	'#475569' // slate
];
const FALLBACK_COLOR = '#a8a29e';

// Biggest all-time category gets the first colour, and so on.
// Returns a function: category name -> colour.
export function makeColorFn(expenses) {
	const totals = {};
	for (const expense of expenses) {
		const key = keyOf(expense.category);
		totals[key] = (totals[key] || 0) + expense.amount;
	}
	const ranked = Object.entries(totals)
		.sort((a, b) => b[1] - a[1])
		.map(([key]) => key);

	return (name) => {
		const index = ranked.indexOf(keyOf(name));
		return index === -1 ? FALLBACK_COLOR : PALETTE[index % PALETTE.length];
	};
}

// ----- One month -----

// Totals and per-day amounts for one month.
export function monthSummary(list, monthKey) {
	const items = inMonth(list, monthKey);
	const days = daysInMonth(monthKey);
	const now = currentMonthKey();

	// How many days of this month have already happened.
	let elapsed = 0;
	if (monthKey === now) elapsed = Number(todayString().slice(8, 10));
	else if (monthKey < now) elapsed = days;

	const daily = new Array(days).fill(0);
	for (const item of items) {
		daily[Number(item.date.slice(8, 10)) - 1] += item.amount;
	}

	let noSpendDays = 0;
	for (let d = 0; d < elapsed; d++) {
		if (daily[d] === 0) noSpendDays++;
	}

	const total = sum(items);
	const count = items.length;
	const avgPerDay = elapsed > 0 ? total / elapsed : 0;

	return {
		items,
		total,
		count,
		avgExpense: count > 0 ? total / count : 0,
		largest: items.reduce((max, item) => Math.max(max, item.amount), 0),
		daysInMonth: days,
		elapsed,
		daily,
		noSpendDays,
		avgPerDay,
		// Past months are finished, so the "projection" is just the total.
		projected: monthKey === now && elapsed > 0 ? avgPerDay * days : total
	};
}

// The last `range` months, ending at monthKey: [{ key, label, total }].
export function monthlyTotals(list, monthKey, range) {
	const months = [];
	for (let i = range - 1; i >= 0; i--) {
		const key = addMonths(monthKey, -i);
		months.push({ key, label: monthShort(key), total: sum(inMonth(list, key)) });
	}
	return months;
}

// Cheapest and most expensive month that had any spending (null if none).
export function bestWorst(months) {
	const active = months.filter((month) => month.total > 0);
	if (active.length === 0) return null;
	let best = active[0];
	let worst = active[0];
	for (const month of active) {
		if (month.total < best.total) best = month;
		if (month.total > worst.total) worst = month;
	}
	return { best, worst };
}

// Year to date: everything from Jan 1st up to the end of the selected month.
export function yearToDate(list, monthKey) {
	const year = monthKey.slice(0, 4);
	return sum(
		list.filter((item) => item.date.startsWith(year) && item.date.slice(0, 7) <= monthKey)
	);
}

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const WEEKDAYS_LONG = [
	'Mondays',
	'Tuesdays',
	'Wednesdays',
	'Thursdays',
	'Fridays',
	'Saturdays',
	'Sundays'
];

// Spending per weekday (Mon..Sun) in the month, plus the average per
// occurrence of that weekday. `top` is the index of the biggest day (-1 if none).
export function dayOfWeek(summary, monthKey) {
	const totals = new Array(7).fill(0);
	const occurrences = new Array(7).fill(0);

	for (let day = 1; day <= summary.daysInMonth; day++) {
		const weekday = (parseDate(monthKey + '-' + String(day).padStart(2, '0')).getDay() + 6) % 7;
		totals[weekday] += summary.daily[day - 1];
		if (day <= summary.elapsed) occurrences[weekday]++;
	}

	const rows = WEEKDAYS.map((label, i) => ({
		label,
		longLabel: WEEKDAYS_LONG[i],
		total: totals[i],
		avg: occurrences[i] > 0 ? totals[i] / occurrences[i] : 0
	}));

	let top = -1;
	rows.forEach((row, i) => {
		if (row.total > 0 && (top === -1 || row.total > rows[top].total)) top = i;
	});
	return { rows, top };
}

// Wk 1 = days 1-7, Wk 2 = 8-14, ... Wk 5 = 29 and later.
export function weekOfMonth(summary) {
	const weeks = [0, 1, 2, 3, 4].map((i) => ({
		label: 'Wk ' + (i + 1),
		total: 0,
		started: i * 7 + 1 <= summary.elapsed
	}));
	summary.daily.forEach((amount, index) => {
		weeks[Math.min(Math.floor(index / 7), 4)].total += amount;
	});
	return weeks;
}

// ----- Categories -----

// [{ key, name, total, count }] biggest first.
export function categoryTotals(list) {
	const map = {};
	for (const item of list) {
		const key = keyOf(item.category);
		if (!map[key]) map[key] = { key, name: item.category, total: 0, count: 0 };
		map[key].total += item.amount;
		map[key].count++;
	}
	return Object.values(map).sort((a, b) => b.total - a.total);
}

// This month's categories with their share and the change vs the month before.
export function categoryShare(list, monthKey) {
	const current = categoryTotals(inMonth(list, monthKey));
	const previous = categoryTotals(inMonth(list, addMonths(monthKey, -1)));
	const total = current.reduce((acc, row) => acc + row.total, 0);

	const rows = current.map((row) => {
		const before = previous.find((p) => p.key === row.key);
		return {
			...row,
			percent: total > 0 ? Math.round((row.total / total) * 100) : 0,
			isNew: !before,
			delta: before ? pctChange(row.total, before.total) : null
		};
	});

	return {
		rows,
		total,
		newNames: rows.filter((row) => row.isNew).map((row) => row.name),
		droppedNames: previous
			.filter((p) => !current.some((row) => row.key === p.key))
			.map((p) => p.name)
	};
}

// Stacked bars: one entry per month, each split by category.
export function categoryTrend(list, monthKey, range) {
	const months = [];
	const seen = {};
	for (let i = range - 1; i >= 0; i--) {
		const key = addMonths(monthKey, -i);
		const totals = categoryTotals(inMonth(list, key));
		for (const row of totals) seen[row.key] = row.name;
		months.push({
			key,
			label: monthShort(key),
			total: totals.reduce((acc, row) => acc + row.total, 0),
			segments: totals.map((row) => ({ name: row.name, value: row.total }))
		});
	}

	// Legend order = all-time ranking.
	const names = categoryTotals(list)
		.filter((row) => seen[row.key])
		.map((row) => row.name);
	return { months, names };
}

// ----- Notes -----

const STOP_WORDS = new Set([
	'the',
	'and',
	'for',
	'with',
	'from',
	'that',
	'this',
	'are',
	'was',
	'not',
	'but',
	'you',
	'your',
	'our',
	'out',
	'off',
	'all',
	'new',
	'one',
	'two',
	'any',
	'got',
	'get'
]);

// Most used words in notes + how many expenses have no note.
export function noteWords(list, limit = 6) {
	const counts = {};
	let blank = 0;
	for (const item of list) {
		if (!item.note.trim()) {
			blank++;
			continue;
		}
		for (const word of item.note.toLowerCase().match(/[a-z\u00c0-\u024f]{3,}/g) || []) {
			if (!STOP_WORDS.has(word)) counts[word] = (counts[word] || 0) + 1;
		}
	}
	const words = Object.entries(counts)
		.map(([word, count]) => ({ word, count }))
		.sort((a, b) => b.count - a.count || a.word.localeCompare(b.word))
		.slice(0, limit);
	return { words, blank };
}

// ----- Time of day -----

const BUCKETS = [
	// `name` completes the sentence "Most spending happens around ..."
	{ label: 'Morning', name: 'breakfast time', from: 5, to: 9 },
	{ label: 'Late am', name: 'late morning', from: 9, to: 11 },
	{ label: 'Noon', name: 'noon', from: 11, to: 14 },
	{ label: 'Afternoon', name: 'mid-afternoon', from: 14, to: 18 },
	{ label: 'Evening', name: 'dinner time', from: 18, to: 22 },
	{ label: 'Night', name: 'late night', from: 22, to: 29 } // 22:00 - 04:59
];

// Spending per part of the day. Expenses without a time are skipped.
export function timeOfDay(items) {
	const buckets = BUCKETS.map((bucket) => ({ ...bucket, total: 0 }));
	let withTime = 0;

	for (const item of items) {
		if (!item.time) continue;
		withTime++;
		let hour = Number(item.time.slice(0, 2));
		if (hour < 5) hour += 24; // 00:00-04:59 counts as the end of "Night"
		const bucket = buckets.find((b) => hour >= b.from && hour < b.to);
		if (bucket) bucket.total += item.amount;
	}

	let top = -1;
	buckets.forEach((bucket, i) => {
		if (bucket.total > 0 && (top === -1 || bucket.total > buckets[top].total)) top = i;
	});
	return { buckets, top, withTime };
}

// ----- Budgets -----

// Budget rows for the selected month. Monthly budgets are calculated here
// from the expenses (so they follow the month stepper); weekly/yearly ones
// use what the backend calculated for the current period.
export function budgetRows(budgets, expenses, monthKey) {
	return budgets.map((budget) => {
		let spent = budget.spent;
		if (budget.period === 'monthly') {
			const items = inMonth(expenses, monthKey).filter(
				(item) => !budget.category || keyOf(item.category) === keyOf(budget.category)
			);
			spent = sum(items);
		}
		const percent = budget.amount > 0 ? (spent / budget.amount) * 100 : 0;
		return {
			id: budget.id,
			name: budget.category || 'Overall',
			category: budget.category,
			period: budget.period,
			amount: budget.amount,
			spent,
			percent,
			over: percent > 100
		};
	});
}

// ----- Income and savings -----

export function incomeSavings(incomes, expenses, monthKey) {
	const income = sum(inMonth(incomes, monthKey));
	const spent = sum(inMonth(expenses, monthKey));
	const net = income - spent;
	return { income, spent, net, rate: income > 0 ? (net / income) * 100 : null };
}

// ----- Fixed vs variable -----

const capitalize = (text) => text.charAt(0).toUpperCase() + text.slice(1);

// Fixed = marked "fixed" or created from a recurring template.
export function fixedVsVariable(list, monthKey) {
	const items = inMonth(list, monthKey);
	const fixedItems = items.filter((item) => item.nature === 'fixed' || item.recurring);
	const fixed = sum(fixedItems);
	const variable = sum(items) - fixed;
	const total = fixed + variable;

	const byCategory = {};
	for (const item of fixedItems) {
		const key = keyOf(item.category);
		if (!byCategory[key]) {
			byCategory[key] = {
				name: item.category,
				total: 0,
				label: item.recurring && item.frequency ? capitalize(item.frequency) : 'Fixed'
			};
		}
		byCategory[key].total += item.amount;
	}

	return {
		fixed,
		variable,
		fixedPercent: total > 0 ? Math.round((fixed / total) * 100) : 0,
		variablePercent: total > 0 ? 100 - Math.round((fixed / total) * 100) : 0,
		rows: Object.values(byCategory).sort((a, b) => b.total - a.total)
	};
}
