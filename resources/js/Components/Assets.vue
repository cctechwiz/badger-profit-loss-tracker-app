
<template>
    <div class="container mx-auto px-4">
        <table class="table-auto w-full">
            <colgroup>
                <col style="width: 10%;" />
                <col style="width: 50%;" />
                <col style="width: 20%;" />
                <col style="width: 20%;" />
            </colgroup>
            <thead>
                <tr>
                    <th class="px-4 py-2 text-center dark:text-white">

                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Ticker
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Current Balance
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Unrealized Profits
                    </th>
                </tr>
            </thead>

            <tbody v-for="asset in props.assets" :key="asset.ticker">
                <tr
                    v-on:click="asset.isOpen = !asset.isOpen"
                    :class="asset.isOpen ? 'bg-zinc-800' : 'bg-zinc-900'"
                >
                    <td class="px-4 py-3 text-center dark:text-white">
                        <img
                            :src="asset.iconURL"
                            style="width: 64px; height: 64px;"
                        />
                    </td>
                    <td class="px-4 py-3 text-center dark:text-white">
                        {{ asset.ticker }}
                    </td>
                    <td class="px-4 py-3 text-center dark:text-white">
                        {{ asset.heldCount }}
                    </td>
                    <td class="px-4 py-3 text-center dark:text-white">
                        {{ parseFloat(asset.unrealizedValueBTC).toFixed(4) + " BTC" }}
                    </td>
                </tr>

                <tr
                    v-if="asset?.isOpen"
                    v-for="walletBalance in asset.balanceByWallets"
                    class="bg-zinc-800"
                >
                    <td></td>
                    <td class="px-4 py-2 text-center dark:text-white">
                        {{ walletBalance.address }}
                    </td>
                    <td class="px-4 py-2 text-center dark:text-white">
                        {{ walletBalance.balance }}
                    </td>
                    <td class="px-4 py-2 text-center dark:text-white">
                        {{ parseFloat(walletBalance.value).toFixed(4) + " BTC" }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
const props = defineProps({
    assets: {
        type: Array,
        required: true,
    },
});

console.log(props.assets);

props?.assets?.forEach((asset) => {
    asset.isOpen = false;
});
</script>
