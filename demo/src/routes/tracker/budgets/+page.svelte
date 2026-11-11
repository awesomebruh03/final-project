<script>
	import Panel from '#lib/components/Panel.svelte';
	import Badge from '#lib/components/Badge.svelte';
	import ProgressRow from '#lib/components/ProgressRow.svelte';
	import { data, addBudget, deleteBudget, categoryColor } from '#lib/data.svelte.js';
	import { view } from '#lib/view.svelte.js';
	import { budgetRows } from '#lib/analytics.js';
	import { formatMoney, monthName } from '#lib/format.js';

	const TEAL = '#0f766e';

	const rows = $derived(budgetRows(data.budgets, data.expenses, view.month));
	const categories = $derived([...new Set(data.expenses.map((expense) => expense.category))]);

	// ----- Add budget form -----
	let category = $state(''); // empty = overall budget
	let amount = $state('');
	let period = $state('monthly');
	let error = $state('');
	let saving = $state(false);

	async function handleSubmit(event) {
		event.preventDefault();
		error = '';

		const amountNumber = Number(amount);
		if (!amount || Number.isNaN(amountNumber) || amountNumber <= 0) {
			error = 'Please enter an amount bigger than 0.';
			return;
		}

		saving = true;
		try {
			await addBudget({ category: category.trim(), amount: amountNumber, period });
			category = '';
			amount = '';
		} catch (backendError) {
			error = backendError.message;
		} finally {
			saving = false;
		}
	}

	// ----- Delete -----
	let deletingId = $state(null);

	async function handleDelete(id) {
		deletingId = id;
		try {
			await deleteBudget(id);
		} catch {
			// The list is unchanged if it fails, so the row stays.
		} finally {
			deletingId = null;
		}
	}
</script>

<div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_23.5rem]">
	<Panel title="Your budgets" hint={monthName(view.month)}>
		{#if rows.length === 0}
			<p class="text-sm text-muted">No budgets yet. Add one with the form.</p>
		{:else}
			<ul class="space-y-5">
				{#each rows as budget (budget.id)}
					<li class="flex items-start gap-3">
						<div class="min-w-0 flex-1">
							<ProgressRow
								label={budget.name}
								sub={budget.period}
								value="{formatMoney(budget.spent)} / {formatMoney(budget.amount)}"
								percent={budget.percent}
								color={budget.category ? categoryColor(budget.category) : TEAL}
								barColor={budget.over ? '#b91c1c' : TEAL}
							>
								{#snippet badge()}
									{#if budget.over}
										<Badge tone="red">Over</Badge>
									{:else}
										<Badge>{Math.round(budget.percent)}%</Badge>
									{/if}
								{/snippet}
							</ProgressRow>
						</div>
						<button
							type="button"
							onclick={() => handleDelete(budget.id)}
							disabled={deletingId === budget.id}
							aria-label="Delete budget"
							class="grid size-9 shrink-0 cursor-pointer place-items-center rounded-xl border border-red-200 text-red-600 transition hover:bg-red-50 disabled:opacity-50"
						>
							<svg viewBox="0 0 24 24" class="size-4" fill="none" aria-hidden="true">
								<path
									d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"
									stroke="currentColor"
									stroke-width="1.8"
									stroke-linecap="round"
									stroke-linejoin="round"
								/>
							</svg>
						</button>
					</li>
				{/each}
			</ul>
		{/if}
	</Panel>

	<Panel title="Add budget">
		<form onsubmit={handleSubmit} class="space-y-4">
			<div>
				<label for="budget-category" class="label">Category</label>
				<input
					id="budget-category"
					type="text"
					list="budget-categories"
					bind:value={category}
					placeholder="Leave empty for an overall budget"
					maxlength="50"
					class="field"
				/>
				<datalist id="budget-categories">
					{#each categories as item (item)}
						<option value={item}></option>
					{/each}
				</datalist>
			</div>

			<div>
				<label for="budget-amount" class="label">Amount</label>
				<input
					id="budget-amount"
					type="number"
					step="0.01"
					min="0.01"
					bind:value={amount}
					placeholder="0.00"
					class="field"
					required
				/>
			</div>

			<div>
				<label for="budget-period" class="label">Period</label>
				<select id="budget-period" bind:value={period} class="field">
					<option value="weekly">Weekly</option>
					<option value="monthly">Monthly</option>
					<option value="yearly">Yearly</option>
				</select>
			</div>

			{#if error}
				<p class="error-box">{error}</p>
			{/if}

			<button type="submit" class="btn-primary w-full" disabled={saving}>
				{saving ? 'Saving…' : 'Add budget'}
			</button>
		</form>
	</Panel>
</div>
