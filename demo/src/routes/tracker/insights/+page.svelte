<script>
	import Panel from '#lib/components/Panel.svelte';
	import Badge from '#lib/components/Badge.svelte';
	import ChangeBadge from '#lib/components/ChangeBadge.svelte';
	import BarChart from '#lib/components/BarChart.svelte';
	import ProgressRow from '#lib/components/ProgressRow.svelte';
	import { data, categoryColor } from '#lib/data.svelte.js';
	import { view } from '#lib/view.svelte.js';
	import {
		inMonth,
		categoryShare,
		categoryTrend,
		categoryTotals,
		noteWords,
		timeOfDay,
		budgetRows,
		incomeSavings,
		fixedVsVariable
	} from '#lib/analytics.js';
	import { addMonths, formatMoney, formatMoneyRound, monthName } from '#lib/format.js';
	import { resolve } from '$app/paths';

	const TEAL = '#0f766e';
	const DEEP = '#0b2b29';
	const SAVINGS_GOAL = 20; // percent of income we compare the savings rate with

	const prevKey = $derived(addMonths(view.month, -1));
	const hasPrevMonth = $derived(inMonth(data.expenses, prevKey).length > 0);

	// ----- Category share + trend -----
	const share = $derived(categoryShare(data.expenses, view.month));
	const shareBiggest = $derived(share.rows.length > 0 ? share.rows[0].total : 1);

	const trend = $derived(categoryTrend(data.expenses, view.month, view.range));
	const trendMax = $derived(Math.max(1, ...trend.months.map((month) => month.total)));

	// ----- All-time -----
	const allTime = $derived(categoryTotals(data.expenses));
	const allTimeBiggest = $derived(allTime.length > 0 ? allTime[0].total : 1);

	// ----- Notes -----
	const notes = $derived(noteWords(data.expenses));
	const noteMax = $derived(notes.words.length > 0 ? notes.words[0].count : 1);

	// ----- Time of day -----
	const tod = $derived(timeOfDay(inMonth(data.expenses, view.month)));
	const todBars = $derived(
		tod.buckets.map((bucket, i) => ({
			label: bucket.label,
			value: bucket.total,
			color: i === tod.top ? DEEP : TEAL,
			title: `${bucket.label}: ${formatMoney(bucket.total)}`
		}))
	);

	// ----- Budgets, income, fixed vs variable -----
	const budgets = $derived(budgetRows(data.budgets, data.expenses, view.month));
	const money = $derived(incomeSavings(data.incomes, data.expenses, view.month));
	const fixedVar = $derived(fixedVsVariable(data.expenses, view.month));

	const savingsBar = $derived(
		money.rate === null ? 0 : Math.max(0, Math.min(100, (money.rate / SAVINGS_GOAL) * 100))
	);
</script>

<div class="space-y-5">
	<!-- Category share + trend -->
	<div class="grid gap-5 lg:grid-cols-2">
		<Panel title="Category share" hint="vs {monthName(prevKey)}">
			{#if share.rows.length === 0}
				<p class="text-sm text-muted">No expenses this month yet.</p>
			{:else}
				<div class="space-y-4">
					{#each share.rows as row (row.key)}
						<ProgressRow
							label={row.name}
							sub="{row.percent}%"
							value={formatMoney(row.total)}
							percent={(row.total / shareBiggest) * 100}
							color={categoryColor(row.name)}
						>
							{#snippet badge()}
								{#if row.isNew}
									{#if hasPrevMonth}<Badge tone="blue">New</Badge>{/if}
								{:else}
									<ChangeBadge delta={row.delta} goodWhen="down" />
								{/if}
							{/snippet}
						</ProgressRow>
					{/each}
				</div>

				{#if hasPrevMonth && (share.newNames.length > 0 || share.droppedNames.length > 0)}
					<div class="mt-5 flex flex-wrap gap-2">
						{#each share.newNames as name (name)}
							<Badge tone="blue">New: {name}</Badge>
						{/each}
						{#each share.droppedNames as name (name)}
							<Badge>Dropped: {name}</Badge>
						{/each}
					</div>
				{/if}
			{/if}
		</Panel>

		<Panel title="Category trend" hint="Last {view.range} months">
			<div class="flex items-end" style="height: 190px; gap: {view.range > 6 ? 6 : 12}px">
				{#each trend.months as month (month.key)}
					<div
						class="flex h-full min-w-0 flex-1 items-end"
						title="{month.label}: {formatMoney(month.total)}"
					>
						{#if month.total > 0}
							<!-- First category at the bottom -->
							<div
								class="flex w-full flex-col-reverse overflow-hidden rounded-t-md"
								style="height: {(month.total / trendMax) * 100}%"
							>
								{#each month.segments as segment (segment.name)}
									<div
										style="height: {(segment.value / month.total) *
											100}%; background: {categoryColor(segment.name)}"
									></div>
								{/each}
							</div>
						{:else}
							<div class="h-[3px] w-full rounded bg-stone-300"></div>
						{/if}
					</div>
				{/each}
			</div>
			<div class="mt-2 flex" style="gap: {view.range > 6 ? 6 : 12}px">
				{#each trend.months as month (month.key)}
					<span class="min-w-0 flex-1 text-center text-xs text-muted">{month.label}</span>
				{/each}
			</div>

			<div class="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 text-sm text-muted">
				{#each trend.names as name (name)}
					<span class="flex items-center gap-1.5">
						<span class="size-2.5 rounded-[3px]" style="background: {categoryColor(name)}"></span>
						{name}
					</span>
				{/each}
			</div>
		</Panel>
	</div>

	<!-- All-time, notes, time of day -->
	<div class="grid gap-5 lg:grid-cols-3">
		<Panel title="All-time totals">
			{#if allTime.length === 0}
				<p class="text-sm text-muted">Nothing here yet.</p>
			{:else}
				<div class="space-y-4">
					{#each allTime as row (row.key)}
						<ProgressRow
							label={row.name}
							value={formatMoneyRound(row.total)}
							percent={(row.total / allTimeBiggest) * 100}
							color={categoryColor(row.name)}
						/>
					{/each}
				</div>
			{/if}
		</Panel>

		<Panel title="Notes" hint="Most frequent words">
			{#if notes.words.length === 0}
				<p class="text-sm text-muted">Add notes to your expenses to see your most used words.</p>
			{:else}
				<div class="flex flex-wrap gap-2">
					{#each notes.words as item (item.word)}
						<span
							class="rounded-full bg-stone-100 px-4 py-2 font-bold text-ink"
							style="font-size: {0.9 + (item.count / noteMax) * 0.7}rem"
						>
							{item.word}
							<span class="text-xs font-semibold text-muted">{item.count}</span>
						</span>
					{/each}
				</div>
			{/if}

			{#if notes.blank > 0}
				<p class="mt-4 flex flex-wrap items-center gap-2 text-sm text-ink">
					<Badge>{notes.blank} {notes.blank === 1 ? 'expense' : 'expenses'}</Badge>
					{notes.blank === 1 ? 'has' : 'have'} a blank note
				</p>
			{/if}
		</Panel>

		<Panel title="Time of day">
			{#if tod.withTime === 0}
				<p class="text-sm text-muted">
					None of this month's expenses have a time. Add one when logging an expense to see when you
					spend.
				</p>
			{:else}
				<BarChart bars={todBars} height={150} gap={8} />
				<p class="mt-4 text-sm text-ink">
					Most spending happens around <strong>{tod.buckets[tod.top].name}</strong>.
				</p>
			{/if}
		</Panel>
	</div>

	<!-- Budgets + income and savings -->
	<div class="grid gap-5 lg:grid-cols-[1.25fr_1fr]">
		<Panel title="Budgets" hint={monthName(view.month)}>
			{#if budgets.length === 0}
				<p class="text-sm text-muted">
					No budgets yet.
					<a
						href={resolve('/tracker/budgets')}
						class="font-semibold text-brand-600 hover:underline"
					>
						Set one up
					</a>
				</p>
			{:else}
				<div class="space-y-4">
					{#each budgets as budget (budget.id)}
						<ProgressRow
							label={budget.name}
							value="{formatMoneyRound(budget.spent)} / {formatMoneyRound(budget.amount)}"
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
					{/each}
				</div>
			{/if}
		</Panel>

		<Panel title="Income and savings">
			<div class="grid grid-cols-2 gap-3">
				<div class="rounded-2xl border border-line p-4">
					<p class="text-sm text-muted">Income</p>
					<p class="mt-1 text-2xl font-extrabold tracking-tight text-ink">
						{money.income > 0 ? formatMoneyRound(money.income) : '—'}
					</p>
				</div>
				<div class="rounded-2xl border border-line p-4">
					<p class="text-sm text-muted">Spent</p>
					<p class="mt-1 text-2xl font-extrabold tracking-tight text-ink">
						{formatMoneyRound(money.spent)}
					</p>
				</div>
			</div>
			<div class="mt-3 rounded-2xl border border-line p-4">
				<p class="text-sm text-muted">Net saved</p>
				<p class="mt-1 text-2xl font-extrabold tracking-tight text-ink">
					{money.income > 0 ? formatMoneyRound(money.net) : '—'}
				</p>
			</div>

			<div class="mt-5">
				<ProgressRow
					label="Savings rate vs {SAVINGS_GOAL}% goal"
					value={money.rate === null ? '—' : Math.round(money.rate) + '%'}
					percent={savingsBar}
					barColor={TEAL}
				/>
			</div>
		</Panel>
	</div>

	<!-- Fixed vs variable -->
	<Panel title="Fixed vs variable" hint="Recurring detected">
		{#if fixedVar.fixed + fixedVar.variable === 0}
			<p class="text-sm text-muted">No expenses this month yet.</p>
		{:else}
			<div class="flex h-3.5 overflow-hidden rounded-full bg-brand-100">
				<div class="bg-brand-600" style="width: {fixedVar.fixedPercent}%"></div>
			</div>
			<div class="mt-3 flex justify-between text-sm text-ink">
				<span
					><strong>Fixed</strong>
					{formatMoneyRound(fixedVar.fixed)} ({fixedVar.fixedPercent}%)</span
				>
				<span>
					<strong>Variable</strong>
					{formatMoneyRound(fixedVar.variable)} ({fixedVar.variablePercent}%)
				</span>
			</div>

			{#if fixedVar.rows.length > 0}
				<ul class="mt-4 divide-y divide-line">
					{#each fixedVar.rows as row (row.name)}
						<li class="flex items-center justify-between py-3.5">
							<span class="flex items-center gap-2 font-semibold text-ink">
								{row.name}
								<Badge tone="blue">{row.label}</Badge>
							</span>
							<span class="text-ink">{formatMoneyRound(row.total)}</span>
						</li>
					{/each}
				</ul>
			{/if}
		{/if}
	</Panel>
</div>
