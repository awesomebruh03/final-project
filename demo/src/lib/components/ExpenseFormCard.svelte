<script>
	import Panel from './Panel.svelte';
	import { data, addExpense, deleteExpense } from '../data.svelte.js';
	import { nowTimeString, todayString } from '../format.js';

	// The "Add expense" card.
	//   editing:  an expense to edit (or null). The backend has no update route,
	//             so saving an edit adds the new version and then deletes the old one.
	//   focus:    focus the Amount box when the card shows up (?add=1)
	//   oncancel: called when the user cancels editing
	const { editing = null, focus = false, oncancel } = $props();

	// Suggestions for the category box: the user's own categories first.
	const defaults = ['Food', 'Rent', 'Transport', 'Fun', 'Gym', 'Bills', 'Health', 'Shopping'];
	const suggestions = $derived.by(() => {
		const own = data.expenses.map((expense) => expense.category);
		return [...new Set([...own, ...defaults])];
	});

	let amount = $state('');
	let category = $state('');
	let date = $state(todayString());
	let time = $state(nowTimeString());
	let note = $state('');
	let repeats = $state(false);

	let error = $state('');
	let warning = $state('');
	let saving = $state(false);
	let amountInput = $state(null);

	// Fill the form when the user clicks "edit" on an expense.
	$effect(() => {
		if (editing) {
			amount = String(editing.amount);
			category = editing.category;
			date = editing.date;
			time = editing.time || '';
			note = editing.note;
			repeats = editing.nature === 'fixed' || editing.recurring;
			error = '';
			warning = '';
			amountInput?.focus();
		}
	});

	$effect(() => {
		if (focus) amountInput?.focus();
	});

	function reset() {
		amount = '';
		note = '';
		time = nowTimeString();
		repeats = false;
	}

	async function handleSubmit(event) {
		event.preventDefault();
		error = '';
		warning = '';

		const amountNumber = Number(amount);
		if (!amount || Number.isNaN(amountNumber) || amountNumber <= 0) {
			error = 'Please enter an amount bigger than 0.';
			return;
		}
		if (!category.trim()) {
			error = 'Please enter a category, e.g. Food or Rent.';
			return;
		}
		if (!date) {
			error = 'Please pick a date.';
			return;
		}

		saving = true;
		try {
			warning = await addExpense({
				amount: amountNumber,
				date,
				time,
				category: category.trim(),
				note: note.trim(),
				repeats,
				// Editing something that already repeats must not create a second template.
				createTemplate: repeats && !editing?.recurring
			});

			if (editing) {
				await deleteExpense(editing.id);
				oncancel?.();
			}
			reset();
		} catch (backendError) {
			error = backendError.message;
		} finally {
			saving = false;
		}
	}
</script>

<Panel title={editing ? 'Edit expense' : 'Add expense'}>
	{#if editing}
		<p class="-mt-2 mb-3 text-sm text-muted">
			Editing —
			<button type="button" class="cursor-pointer underline hover:text-ink" onclick={oncancel}>
				Cancel
			</button>
		</p>
	{/if}

	<form onsubmit={handleSubmit} class="space-y-4">
		<div>
			<label for="amount" class="label">Amount</label>
			<input
				id="amount"
				type="number"
				step="0.01"
				min="0.01"
				bind:this={amountInput}
				bind:value={amount}
				placeholder="0.00"
				class="field"
				required
			/>
		</div>

		<div>
			<label for="category" class="label">Category</label>
			<input
				id="category"
				type="text"
				list="category-suggestions"
				bind:value={category}
				placeholder="Food, Rent..."
				maxlength="50"
				class="field"
				required
			/>
			<datalist id="category-suggestions">
				{#each suggestions as item (item)}
					<option value={item}></option>
				{/each}
			</datalist>
		</div>

		<div class="grid grid-cols-2 gap-4">
			<div>
				<label for="date" class="label">Date</label>
				<input id="date" type="date" bind:value={date} class="field" required />
			</div>
			<div>
				<label for="time" class="label">Time</label>
				<input id="time" type="time" bind:value={time} class="field" />
			</div>
		</div>

		<div>
			<label for="note" class="label">Note</label>
			<input id="note" type="text" bind:value={note} placeholder="Optional" class="field" />
		</div>

		<label class="flex cursor-pointer items-center gap-3 text-muted">
			<input
				type="checkbox"
				bind:checked={repeats}
				class="size-5 rounded-md border-stone-300 text-brand-600 focus:ring-brand-600/30"
			/>
			Repeats every month
		</label>

		{#if error}
			<p class="error-box">{error}</p>
		{/if}
		{#if warning}
			<p class="rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800">{warning}</p>
		{/if}

		<button type="submit" class="btn-primary w-full" disabled={saving}>
			{saving ? 'Saving…' : editing ? 'Save changes' : 'Add expense'}
		</button>
	</form>
</Panel>
