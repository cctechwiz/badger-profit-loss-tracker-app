<template>
    <AuthenticatedLayout class="pb-20">
        <title title="PL">PL</title>
        <div class="page-background"></div>
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-zinc-900 bg-opacity-50 px-6 pb-4 position-sticky">
                <div class="flex h-16 shrink-0 items-center"></div>

                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-2" v-if="!isLoading">
                        <li clas="mt-auto">
                            <div class="mx-6 text-white p-5 w-48 text-center whitespace-nowrap">
                                <div>
                                    <label for="year-select" class="font-bold block mb-2">Report Year</label>
                                    <select id="year-select" v-model="selectedYear" class="text-md mb-2 text-black">
                                        <option value="2023">2023</option>
                                        <option value="2024">2024</option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <div class="mx-6 text-white text-center">
                            <label for="tradeCount" class="font-bold block"># Trades</label>
                            <div id="tradeCount" class="text-4xl">
                                {{ props.transactionCountsByYear[ selectedYear ] ?? 0 }}
                            </div>
                        </div>

                        <li class="mt-auto mb-6">
                            <div class="mx-6 text-white p-5 flex items-center">
                                <SecondaryButton @click="downloadTSV">
                                    Download Data
                                </SecondaryButton>
                            </div>
                        </li>

                        <li v-if="props.isFreeUser" class="mt-auto mb-6">
                            <div class="mx-6 text-white w-48 text-center">
                                <p class="font-bold">Trial User:</p>
                                <ul class="text-xs">
                                    <li>10 tickers per year (5 brc / 5 ord)</li>
                                    <li>3 transactions per ticker</li>
                                </ul>
                                <a href="https://buy.badgers.pl/"
                                    target="_blank"
                                    class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-zinc-400 hover:bg-zinc-800 hover:text-white"
                                >
                                    <div class="mx-auto">Purchase License</div>
                                </a>
                            </div>
                        </li>

                        <li class="mt-auto">
                            <a href="https://discord.com/channels/1206766190886780959/1211128993580064820"
                                target="_blank"
                                class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-zinc-400 hover:bg-zinc-800 hover:text-white">
                                <Cog6ToothIcon class="h-6 w-6 shrink-0" aria-hidden="true" />
                                Terms & Conditions
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <div class="lg:pl-72">
            <div v-if="isLoading" class="flex flex-col items-center justify-center py-5 pt-20">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-500"></div>
                <span class="mt-12 text-white text-4xl">Loading...</span>
            </div>
            <div v-if="!isLoading">
                <div class="justify-start px-12 py-5 pt-20">
                    <div class="inline-block">
                        <label for="realizedPL" class="font-bold block mb-2 dark:text-white">Realized P/L</label>
                        <span id="realizedPL" :class="props.realizedPLByYear[selectedYear] < 0
                            ? 'text-red-500 dark:text-red-500 text-3xl font-bold block mb-2'
                            : 'text-white dark:text-white text-5xl font-bold block mb-2'
                        ">
                            ₿
                            {{
                            parseFloat(
                                props.realizedPLByYear[selectedYear] ?? 0
                            ).toFixed(3)
                            }}
                        </span>
                    </div>
                    <div class="inline-block pl-20">
                        <label for="unrealizedPL" class="font-bold block mb-2 dark:text-white">Unrealized Value</label>
                        <span id="unrealizedPL" class="text-white dark:text-white text-5xl font-bold block mb-2">
                            ₿
                            {{
                            parseFloat(
                                props.unrealizedPL?? 0
                            ).toFixed(3)
                            }}
                        </span>
                    </div>
                </div>

                <div v-if="brcTransactionInSelectedYear > 0">
                    <h1 class="text-white text-5xl font-bold text-center my-12">
                        BRC-20
                    </h1>
                    <div>
                        <Transactions
                            :transactions="props.brcTransactions"
                            :assets="props.brcAssets"
                            :selected-year="selectedYear"
                        />
                    </div>
                </div>

                <div v-if="ordTransactionInSelectedYear > 0">
                    <h1 class="text-white text-5xl font-bold text-center my-24">
                        Ordinals
                    </h1>
                    <div>
                        <Transactions
                            :transactions="props.ordTransactions"
                            :assets="props.ordAssets"
                            :selected-year="selectedYear"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Transactions from "@/Components/Transactions.vue";
import { computed, reactive, ref, toRaw } from "vue";
import { saveAs } from "file-saver";
import SecondaryButton from "../../Components/SecondaryButton.vue";

const isLoading = ref(true);

const selectedYear = ref("2024");

const props = reactive({
    brcTransactions: [],
    ordTransactions: [],
    realizedPL: 0,
    realizedPLByYear: 0,
    transactionCount: 0,
    transactionCountsByYear: 0,

    ordAssets: [],
    brcAssets: [],
    unrealizedPL: 0,

    isFreeUser: true,
});

async function fetchData() {
    try {
        const response = await axios.get(route("pl.data"), {
            timeout: 900000, // 15 minutes
        });
        const data = response.data;
        console.log(data);

        props.brcTransactions = data.brcTransactions;
        props.ordTransactions = data.ordTransactions;
        props.realizedPL = data.realizedPL;
        props.realizedPLByYear = data.realizedPLByYear;
        props.transactionCount = data.transactionCount;
        props.transactionCountsByYear = data.transactionCountsByYear;

        props.ordAssets = data.ordAssets;
        props.brcAssets = data.brcAssets;
        props.unrealizedPL = data.unrealizedPL;

        props.isFreeUser = data.isFreeUser;
    } catch (error) {
        //This is squashing reload/abort errors
    } finally {
        isLoading.value = false;
    }
}

const brcTransactionInSelectedYear = computed(() => {
    console.log("brc");
    console.log(selectedYear.value);
    return props.brcTransactions.filter((txn) => {
        const events = txn.eventsByYear[selectedYear.value] || [];
        return events.length > 0;
    }).length;
});
const ordTransactionInSelectedYear = computed(() => {
    console.log("ord");
    console.log(selectedYear.value);
    return props.ordTransactions.filter((txn) => {
        const events = txn.eventsByYear[selectedYear.value] || [];
        return events.length > 0;
    }).length;
});

const downloadTSV = async () => {
    const brcArray = toRaw(props.brcTransactions);
    const brcTransactions = brcArray.flatMap(({ ticker, eventsByYear }) =>
        (eventsByYear[selectedYear.value] || []).map(
            ({
                type,
                date,
                basisDate,
                amount,
                priceBTC,
                priceUSD,
                costBasisBTC,
                costBasisUSD,
                profitBTC,
                profitUSD,
                bitcoinPriceAtPurchase,
                bitcoinPriceAtSale,
                transactionWebURL,
                inscriptionId,
            }) => ({
                ticker,
                type,
                date,
                basisDate,
                amount,
                priceBTC,
                priceUSD,
                bitcoinPriceAtPurchase,
                bitcoinPriceAtSale,
                costBasisBTC,
                profitBTC,
                costBasisUSD,
                profitUSD,
                transactionWebURL,
                inscriptionId,
            })
        )
    );

    const ordArray = toRaw(props.ordTransactions);
    const ordTransactions = ordArray.flatMap(({ ticker, eventsByYear }) =>
        (eventsByYear[selectedYear.value] || []).map(
            ({
                type,
                date,
                basisDate,
                amount,
                priceBTC,
                priceUSD,
                costBasisBTC,
                costBasisUSD,
                profitBTC,
                profitUSD,
                bitcoinPriceAtPurchase,
                bitcoinPriceAtSale,
                transactionWebURL,
                inscriptionId,
            }) => ({
                ticker,
                type,
                date,
                basisDate,
                amount,
                priceBTC,
                priceUSD,
                bitcoinPriceAtPurchase,
                bitcoinPriceAtSale,
                costBasisBTC,
                profitBTC,
                costBasisUSD,
                profitUSD,
                transactionWebURL,
                inscriptionId,
            })
        )
    );

    const transactions = brcTransactions.concat(ordTransactions);

    try {
        const response = await axios.post(
            route("pl.tsv"),
            {
                transactions: transactions,
            },
            { responseType: "blob" }
        );

        const blobData = new Blob([response.data], {
            type: "text/tab-separated-values",
        });
        saveAs(blobData, selectedYear.value + "_ProfitLoss.tsv");
    } catch (error) {
        //console.log(error);
    }
};

fetchData();
</script>

<style scoped>
.page-background {
    position: fixed;
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, #494949 100%, #494949 6%) !important;
    z-index: -10;
}

.summary-cards {
    display: flex;
    justify-content: space-around;
    padding: 20px 0;
}

.card {
    background-color: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.15);
    width: 200px;
    text-align: center;
}

.card-label {
    font-weight: bold;
    display: block;
    margin-bottom: 10px;
}
</style>
