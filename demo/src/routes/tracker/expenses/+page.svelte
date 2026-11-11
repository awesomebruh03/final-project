<script>
	import ExpenseRow from '#lib/components/ExpenseRow.svelte';
	import ExpenseFormCard from '#lib/components/ExpenseFormCard.svelte';
	import Panel from '#lib/components/Panel.svelte';
	import ProgressRow from '#lib/components/ProgressRow.svelte';
	import { data, deleteExpense, categoryColor } from '#lib/data.svelte.js';
	import { view } from '#lib/view.svelte.js';
	import { categoryTotals, sum } from '#lib/analytics.js';
	import { addMonths, formatDayHeading, formatMoney, monthShort } from '#lib/format.js';
	import { page } from '$app/state';

	// ----- Filters -----
	let search = $state('');
	let sort = $state('newest'); // 'newest' | 'oldest' | 'high' | 'low'
	let categoryFilter = $state(''); // '' = all categories
	let extraMonths = $state(0); // "Load older expenses" adds one month each click

	let editing = $state(null);
	let deletingId = $state(null);

	const focusForm = $derived(page.url.searchParams.get('add') === '1');

	// Start over when the user picks another month.
	$effect(() => {
		view.month;
		extraMonths = 0;
		categoryFilter = '';
	});

	// ----- The expenses in view -----
	// The window is the selected month plus `extraMonths` months before it.
	const windowStart = $derived(addMonths(view.month, -extraMonths));
	const windowItems = $derived(
		data.expenses.filter((item) => {
			const month = item.date.slice(0, 7);
			return month >= windowStart && month <= view.month;
		})
	);
	const hasOlder = $derived(data.expenses.some((item) => item.date.slice(0, 7) < windowStart));

	const categories = $derived(categoryTotals(windowItems));

	const filtered = $derived.by(() => {
		const text = search.trim().toLowerCase();
		const list = windowItems.filter((item) => {
			if (categoryFilter && item.category.toLowerCase() !== categoryFilter.toLowerCase()) {
				return false;
			}
			if (!text) return true;
			return item.note.toLowerCase().includes(text) || item.category.toLowerCase().includes(text);
		});

		// data.expenses is already newest first.
		if (sort === 'oldest') return list.toReversed();
		if (sort === 'high') return list.toSorted((a, b) => b.amount - a.amount);
		if (sort === 'low') return list.toSorted((a, b) => a.amount - b.amount);
		return list;
	});

	// Grouped by day when sorting by date, one flat list when sorting by amount.
	const groups = $derived.by(() => {
		if (sort === 'high' || sort === 'low') {
			return [{ date: '', total: 0, items: filtered }];
		}
		const result = [];
		for (const item of filtered) {
			let group = result[result.length - 1];
			if (!group || group.date !== item.date) {
				group = { date: item.date, total: 0, items: [] };
				result.push(group);
			}
			group.items.push(item);
			group.total += item.amount;
		}
		return result;
	});

	// ----- "This view" card -----
	const viewTotals = $derived(categoryTotals(windowItems));
	const biggest = $derived(viewTotals.length > 0 ? viewTotals[0].total : 1);
	const viewHint = $derived(
		extraMonths === 0
			? `${monthShort(view.month)} ${view.month.slice(0, 4)}`
			: `${monthShort(windowStart)} – ${monthShort(view.month)} ${view.month.slice(0, 4)}`
	);

	async function handleDelete(id) {
		deletingId = id;
		try {
			await deleteExpense(id);
			if (editing?.id === id) editing = null;
		} catch {
			// If it fails, the list is unchanged so the row is still there.
		} finally {
			deletingId = null;
		}
	}

	const pillBase =
		'cursor-pointer rounded-full border px-5 py-2.5 text-sm font-semibold transition';
	const pillActive = 'border-ink bg-ink text-white';
	const pillIdle = 'border-line bg-white text-ink hover:bg-stone-50';
</script>

<div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_23.5rem]">
	<!-- Left: the list -->
	<div class="min-w-0 space-y-4">
		<div class="flex flex-wrap gap-3">
			<label class="relative min-w-52 flex-1">
				<span class="sr-only">Search notes</span>
				<svg
					viewBox="0 0 24 24"
					class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted"
					fill="none"
					aria-hidden="true"
				>
					<circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8" />
					<path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
				</svg>
				<input
					type="search"
					bind:value={search}
					placeholder="Search notes"
					class="w-full rounded-xl border border-line bg-white py-3 pr-4 pl-12 text-ink shadow-sm placeholder:text-muted focus:border-brand-600 focus:ring-2 focus:ring-brand-600/25 focus:outline-none"
				/>
			</label>

			<select
				bind:value={sort}
				aria-label="Sort expenses"
				class="cursor-pointer rounded-xl border border-line bg-white py-3 pr-10 pl-4 font-semibold text-ink shadow-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/25 focus:outline-none"
			>
				<option value="newest">Newest first</option>
				<option value="oldest">Oldest first</option>
				<option value="high">Highest amount</option>
				<option value="low">Lowest amount</option>
			</select>
		</div>

		<!-- Category pills -->
		<div class="flex flex-wrap gap-2">
			<button
				type="button"
				class="{pillBase} {categoryFilter === '' ? pillActive : pillIdle}"
				onclick={() => (categoryFilter = '')}
			>
				All
			</button>
			{#each categories as category (category.key)}
				<button
					type="button"
					class="{pillBase} {categoryFilter.toLowerCase() === category.key ? pillActive : pillIdle}"
					onclick={() => (categoryFilter = category.name)}
				>
					{category.name}
				</button>
			{/each}
		</div>

		<p class="text-muted">
			{filtered.length}
			{filtered.length === 1 ? 'expense' : 'expenses'} · {formatMoney(sum(filtered))} · showing {categoryFilter ||
				'all categories'}
		</p>

		{#if filtered.length === 0}
			<div class="rounded-2xl border border-dashed border-stone-300 px-4 py-12 text-center">
				<p class="font-semibold text-ink">No expenses found</p>
				<p class="mt-1 text-sm text-muted">
					{search || categoryFilter
						? 'Try a different search or category.'
						: 'Nothing logged for this period yet.'}
				</p>
			</div>
		{:else}
			{#each groups as group (group.date || 'all')}
				<div>
					{#if group.date}
						<div
							class="mb-2 flex items-center justify-between px-1 text-sm font-semibold text-muted"
						>
							<span>{formatDayHeading(group.date)}</span>
							<span>{formatMoney(group.total)}</span>
						</div>
					{/if}
					<ul class="space-y-2.5">
						{#each group.items as expense (expense.id)}
							<ExpenseRow
								{expense}
								color={categoryColor(expense.category)}
								deleting={deletingId === expense.id}
								onedit={() => (editing = expense)}
								ondelete={() => handleDelete(expense.id)}
							/>
						{/each}
					</ul>
				</div>
			{/each}
		{/if}

		{#if hasOlder}
			<button
				type="button"
				onclick={() => (extraMonths += 1)}
				class="w-full cursor-pointer rounded-2xl border border-line bg-white py-3.5 font-semibold text-ink transition hover:bg-stone-50"
			>
				Load older expenses
			</button>
		{/if}
	</div>

	<!-- Right: add form + category totals -->
	<div class="space-y-5 lg:sticky lg:top-6">
		<ExpenseFormCard {editing} focus={focusForm} oncancel={() => (editing = null)} />

		<Panel title="This view" hint={viewHint}>
			{#if viewTotals.length === 0}
				<p class="text-sm text-muted">Nothing to show yet.</p>
			{:else}
				<div class="space-y-3.5">
					{#each viewTotals as row (row.key)}
						<ProgressRow
							label={row.name}
							value={formatMoney(row.total)}
							percent={(row.total / biggest) * 100}
							color={categoryColor(row.name)}
						/>
					{/each}
				</div>
			{/if}
		</Panel>
	</div>
</div>
