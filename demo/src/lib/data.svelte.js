// Holds the user's expenses, incomes, budgets and recurring templates and
// does all the talking to the backend about them.
//
// Backend shapes (see the API schema):
//   Transaction: { id, type, amount, date, time|null, category, note, nature|null }
//   Budget:      { id, category|null, amount, period, period_start, period_end,
//                  spent, remaining, percentage }   (spent = current period only)
//   Recurring:   { id, type, amount, category, note, nature, frequency, interval,
//                  starts_on, ends_on, next_run_on, is_active }
//
// Transactions do NOT say whether they came from a recurring template, so
// we match them to the templates (same category + amount + note).

import { api } from './api.js';
import { expireSession } from './auth.svelte.js';
import { makeColorFn } from './analytics.js';
import { addMonths } from './format.js';
import { goto } from '$app/navigation';
import { resolve } from '$app/paths';

export const data = $state({
	expenses: [], // newest first
	incomes: [],
	budgets: [],
	recurring: [],
	loading: false,
	error: ''
});

// ----- Normalizing: backend JSON -> the shape the app uses -----

const sameText = (a, b) =>
	String(a || '')
		.trim()
		.toLowerCase() ===
	String(b || '')
		.trim()
		.toLowerCase();

function normalizeTransaction(raw) {
	return {
		id: raw.id,
		amount: Number(raw.amount) || 0,
		date: String(raw.date).slice(0, 10),
		time: raw.time ? String(raw.time).slice(0, 5) : null,
		category: String(raw.category || 'Other').trim(),
		note: raw.note ? String(raw.note) : '',
		nature: raw.nature === 'fixed' ? 'fixed' : 'variable',
		// Filled in by markRecurring() below.
		recurring: false,
		frequency: ''
	};
}

function normalizeBudget(raw) {
	return {
		id: raw.id,
		category: raw.category ? String(raw.category) : null, // null = overall budget
		amount: Number(raw.amount) || 0,
		period: raw.period || 'monthly',
		spent: Number(raw.spent) || 0, // current period only
		remaining: Number(raw.remaining) || 0,
		percentage: Number(raw.percentage) || 0
	};
}

// Flags the expenses that match an active expense template.
function markRecurring(expenses, templates) {
	for (const expense of expenses) {
		const template = templates.find(
			(t) =>
				t.type === 'expense' &&
				sameText(t.category, expense.category) &&
				Math.abs(Number(t.amount) - expense.amount) < 0.005 &&
				sameText(t.note, expense.note)
		);
		if (template) {
			expense.recurring = true;
			expense.frequency = template.frequency;
		}
	}
}

// Newest first: by date, then time, then id.
function newestFirst(a, b) {
	if (a.date !== b.date) return a.date < b.date ? 1 : -1;
	if ((a.time || '') !== (b.time || '')) return (a.time || '') < (b.time || '') ? 1 : -1;
	return Number(b.id) - Number(a.id);
}

// ----- Colours: one stable colour per category, everywhere -----

const colorFn = $derived(makeColorFn(data.expenses));

export function categoryColor(name) {
	return colorFn(name);
}

// ----- Talking to the backend -----

// If the backend answers 401 our token is gone/expired,
// so we forget the login and send the user to the auth page.
function handleNotLoggedIn(error) {
	expireSession();
	goto(resolve('/auth'));
	throw error;
}

// Loads everything for the logged-in user.
// silent = don't show the "loading" state (used to refresh after a change).
export async function loadAll({ silent = false } = {}) {
	if (!silent) data.loading = true;
	data.error = '';

	try {
		const [expenses, incomes, budgets, recurring] = await Promise.all([
			api('/expenses'),
			api('/incomes'),
			api('/budgets'),
			api('/recurring')
		]);

		const list = expenses.map(normalizeTransaction).sort(newestFirst);
		markRecurring(list, recurring);

		data.expenses = list;
		data.incomes = incomes.map(normalizeTransaction).sort(newestFirst);
		data.budgets = budgets.map(normalizeBudget);
		data.recurring = recurring;
	} catch (error) {
		if (error.status === 401) {
			handleNotLoggedIn(error);
		}
		data.error = error.message;
	} finally {
		data.loading = false;
	}
}

// Sends one new expense. When `repeats` is on, it is saved as "fixed" and a
// monthly template is created too, starting next month (the expense itself
// is already logged). If only the template fails the expense is still saved
// and a warning text is returned instead of throwing.
export async function addExpense({
	amount,
	date,
	time,
	category,
	note,
	repeats,
	createTemplate = repeats
}) {
	const nature = repeats ? 'fixed' : 'variable';
	await api('/expenses', {
		method: 'POST',
		body: { amount, date, time: time || null, category, note, nature }
	});

	let warning = '';
	if (repeats && createTemplate) {
		try {
			await api('/recurring', {
				method: 'POST',
				body: {
					type: 'expense',
					amount,
					category,
					note,
					nature,
					frequency: 'monthly',
					interval: 1,
					starts_on: nextMonthDate(date)
				}
			});
		} catch (error) {
			warning = 'Saved, but the monthly repeat could not be set up: ' + error.message;
		}
	}

	await loadAll({ silent: true });
	return warning;
}

// "2026-01-31" + 1 month -> "2026-02-28" (clamped to the month's last day).
function nextMonthDate(date) {
	const day = Number(date.slice(8, 10));
	const next = addMonths(date.slice(0, 7), 1);
	const [nextYear, nextMonth] = next.split('-').map(Number);
	const lastDay = new Date(nextYear, nextMonth, 0).getDate();
	return next + '-' + String(Math.min(day, lastDay)).padStart(2, '0');
}

// Deletes one expense on the backend, then removes it from the list.
export async function deleteExpense(id) {
	await api('/expenses/' + id, { method: 'DELETE' });
	data.expenses = data.expenses.filter((expense) => expense.id !== id);
}

export async function addIncome({ amount, date, time, category, note }) {
	await api('/incomes', {
		method: 'POST',
		body: { amount, date, time: time || null, category, note }
	});
	await loadAll({ silent: true });
}

export async function deleteIncome(id) {
	await api('/incomes/' + id, { method: 'DELETE' });
	data.incomes = data.incomes.filter((income) => income.id !== id);
}

export async function addBudget({ category, amount, period }) {
	await api('/budgets', {
		method: 'POST',
		body: { category: category || null, amount, period }
	});
	await loadAll({ silent: true });
}

export async function deleteBudget(id) {
	await api('/budgets/' + id, { method: 'DELETE' });
	data.budgets = data.budgets.filter((budget) => budget.id !== id);
}
