<template>
    <AuthenticatedLayout>
        <title title="Admin">PL</title>
        <div class="page-background"></div>

        <div v-if="isLoading" class="flex flex-col items-center justify-center py-5 pt-20">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-500"></div>
            <span class="mt-12 text-white text-4xl">Loading...</span>
        </div>

        <div v-if="!isLoading" class="container mx-auto px-4">
            <h1 class="text-4xl dark:text-white mt-20">BRC-20 Assets</h1>
            <Assets :assets="props.brcAssets" />

            <h1 class="text-4xl dark:text-white mt-20">Ord Assets</h1>
            <Assets :assets="props.ordAssets" />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Assets from "@/Components/Assets.vue";
import { reactive, ref } from "vue";

const isLoading = ref(true);

const props = reactive({
    brcAssets: [],
    ordAssets: [],
});

async function fetchData() {
    try {
        const response = await axios.get(route("assets.data"), {
            timeout: 900000, // 15 minutes
        });
        const data = response.data;
        console.log(data);

        props.brcAssets = data.brcAssets;
        props.ordAssets = data.ordAssets;
    } catch (error) {
        //This is squashing reload/abort errors
    } finally {
        isLoading.value = false;
    }
}

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

