<script>
	import { data, categoryColor } from '#lib/data.svelte.js';
	import { view } from '#lib/view.svelte.js';
	import { categoryTotals, inMonth, sum } from '#lib/analytics.js';
	import { formatMoney, monthName } from '#lib/format.js';
	import { resolve } from '$app/paths';

	// One card per category: this month vs all time.
	const allTime = $derived(categoryTotals(data.expenses));
	const allTimeSum = $derived(sum(data.expenses));
	const monthTotals = $derived(categoryTotals(inMonth(data.expenses, view.month)));

	const cards = $derived(
		allTime.map((row) => {
			const month = monthTotals.find((m) => m.key === row.key);
			return {
				...row,
				month: month ? month.total : 0,
				share: allTimeSum > 0 ? (row.total / allTimeSum) * 100 : 0
			};
		})
	);
</script>

{#if cards.length === 0}
	<div class="card p-8 text-center">
		<p class="font-semibold text-ink">No categories yet</p>
		<p class="mt-1 text-sm text-muted">
			Categories are created when you add an expense.
			<a
				href={resolve('/tracker/expenses?add=1')}
				class="font-semibold text-brand-600 hover:underline"
			>
				Add one
			</a>
		</p>
	</div>
{:else}
	<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
		{#each cards as card (card.key)}
			<div class="card p-6">
				<div class="flex items-center gap-4">
					<span
						class="grid size-12 shrink-0 place-items-center rounded-xl text-lg font-bold text-white"
						style="background: {categoryColor(card.name)}"
					>
						{card.name.charAt(0).toUpperCase()}
					</span>
					<div class="min-w-0">
						<p class="truncate text-lg font-bold text-ink">{card.name}</p>
						<p class="text-sm text-muted">
							{card.count}
							{card.count === 1 ? 'expense' : 'expenses'}
						</p>
					</div>
				</div>

				<dl class="mt-5 grid grid-cols-2 gap-3">
					<div>
						<dt class="text-sm text-muted">{monthName(view.month)}</dt>
						<dd class="text-xl font-extrabold tracking-tight text-ink">
							{formatMoney(card.month)}
						</dd>
					</div>
					<div>
						<dt class="text-sm text-muted">All time</dt>
						<dd class="text-xl font-extrabold tracking-tight text-ink">
							{formatMoney(card.total)}
						</dd>
					</div>
				</dl>

				<div class="mt-4 h-2 rounded-full bg-stone-200/70">
					<div
						class="h-2 rounded-full"
						style="width: {card.share}%; background: {categoryColor(card.name)}"
					></div>
				</div>
				<p class="mt-1.5 text-xs text-muted">{Math.round(card.share)}% of all spending</p>
			</div>
		{/each}
	</div>
{/if}
