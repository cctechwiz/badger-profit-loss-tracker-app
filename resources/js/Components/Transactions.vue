<template>
    <div class="container mx-auto px-4">
        <table class="table-auto w-full border-separate border-spacing-y-1">
            <colgroup>
                <col style="width: 40%;" />
                <col style="width: 15%;" />
                <col style="width: 15%;" />
                <col style="width: 15%;" />
                <col style="width: 15%;" />
            </colgroup>
            <thead>
                <tr>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Ticker
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Transactions
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Current Balance
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Unrealized Profit
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Realized Profit
                    </th>
                </tr>
            </thead>
            <tbody
                v-for="transaction in transactionInSelectedYear"
                :key="transaction.id"
            >
                <tr
                    v-on:click="transaction.isOpen = !transaction.isOpen"
                    :class="transaction.isOpen ? 'bg-zinc-800' : 'bg-zinc-900'"
                >
                    <td class="px-4 py-3 text-center dark:text-white">
                        {{ transaction.ticker }}
                    </td>
                    <td class="px-4 py-2 text-center dark:text-white"
                        :class="
                        (transaction.eventsByYear[selectedYear]?.length ?? 0) <= 0
                            ? 'text-red-500 dark:text-zinc-500'
                            : 'text-green-500 dark:text-green-500'
                        "
                    >
                        {{ transaction.eventsByYear[selectedYear]?.length ?? 0 }}
                    </td>
                    <td class="px-4 py-2 text-center dark:text-white"
                        :class="
                        assetCurrentBalance(transaction.ticker) <= 0
                            ? 'text-red-500 dark:text-zinc-500'
                            : 'text-green-500 dark:text-green-500'
                        "
                    >
                        <div :title="assetCurrentBalanceByWallet(transaction.ticker)">
                            {{ assetCurrentBalance(transaction.ticker) }}
                        </div>
                    </td>
                    <td class="px-4 py-2 text-center"
                        :class="
                        assetUnrealizedProfitBTC(transaction.ticker) <= 0
                            ? 'text-red-500 dark:text-zinc-500'
                            : 'text-green-500 dark:text-green-500'
                        "
                    >
                        {{
                        assetUnrealizedProfitBTC(transaction.ticker)
                            ? parseFloat(
                                assetUnrealizedProfitBTC(transaction.ticker)
                            ).toFixed(4) + " BTC"
                            : "-"
                        }}
                    </td>
                    <td
                        class="px-4 py-2 text-center"
                        :class="
                            (transaction.totalProfitBTCByYear[selectedYear] ?? 0) < 0
                                ? 'text-red-500 dark:text-red-500'
                                : 'text-green-500 dark:text-green-500'
                        "
                    >
                        {{ totalYearlyProfitBTC(transaction, selectedYear) }}
                    </td>
                </tr>

                <tr
                    v-if="transaction?.isOpen"
                    v-on:click="transaction.isOpen = !transaction.isOpen"
                >
                    <th class="px-4 py-2 text-center dark:text-white">
                        Type
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Cost Basis
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Amount
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Date
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Profit
                    </th>
                </tr>

                <tr
                    v-if="transaction?.isOpen"
                    v-for="event in transaction.eventsByYear[selectedYear]"
                    class="bg-zinc-800"
                >
                    <td class="px-4 py-2 text-center dark:text-white">
                        <div v-if="event.transactionWebURL !== ''">
                            <a
                                :href="event.transactionWebURL"
                                target="_blank"
                                class="flex justify-center items-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="w-4 h-4 mr-1"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"
                                    />
                                </svg>
                                {{ event.type }}
                            </a>
                        </div>
                        <div v-else>
                            {{ event.type }}
                        </div>
                    </td>
                    <td class="px-4 py-2 text-center dark:text-white">
                        {{ event.costBasisBTC }}
                    </td>
                    <td class="px-4 py-2 text-center dark:text-white">
                        {{ event.amount }}
                    </td>
                    <td class="px-4 py-2 text-center dark:text-white">
                        {{ event.date.split(' ')[0] ?? 'unknown' }}
                    </td>
                    <td
                        class="px-4 py-2 text-center"
                        :class="
                        event.type === 'buy'
                            ? 'text-zinc-500 dark:text-zinc-500'
                            : event.profitBTC != null
                                ? event.profitBTC < 0
                                    ? 'text-red-500 dark:text-red-500'
                                    : 'text-green-500 dark:text-green-500'
                                : 'dark:text-white'
                        "
                    >
                        {{
                        event.type === 'buy'
                            ? "-"
                            : event.profitBTC != null
                                ? parseFloat(event.profitBTC).toFixed(4) + " BTC"
                                : "N/A"
                        }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    transactions: {
        type: Array,
        required: true,
    },
    assets: {
        type: Array,
        required: true,
    },
    selectedYear: {
        type: String,
        required: true,
    },
});

//console.log(JSON.stringify(props));
props?.transactions?.forEach((transaction) => {
    transaction.isOpen = false;
});

//Return only the transactions that have events this year
const transactionInSelectedYear = computed(() => {
    return props.transactions.filter((txn) => {
        const events = txn.eventsByYear[props?.selectedYear] || [];
        return events.length > 0;
    });
});

const totalYearlyProfitBTC = (transaction, selectedYear) => {
    if (!transaction.totalProfitBTCByYear[selectedYear]) {
        return "-";
    }

    const profit = parseFloat(
        transaction.totalProfitBTCByYear[selectedYear] ?? 0
    ).toFixed(4);

    return profit + "BTC";
};

const assetCurrentBalance = (ticker) => {
    const asset = props.assets.find(asset => asset.ticker.toLowerCase() === ticker.toLowerCase());
    return asset ? asset.heldCount : 0;
};

const assetUnrealizedProfitBTC = (ticker) => {
    const asset = props.assets.find(asset => asset.ticker.toLowerCase() === ticker.toLowerCase());
    return asset ? asset.unrealizedValueBTC : 0;
}

const assetCurrentBalanceByWallet = (ticker) => {
    const asset = props.assets.find(asset => asset.ticker.toLowerCase() === ticker.toLowerCase());
    if (asset) {
        //TODO: Concat all wallets and balances together
        console.log(asset.balanceByWallets);
        return asset.balanceByWallets;
    }

    return "";
}
</script>
