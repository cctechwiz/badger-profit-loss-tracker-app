<script setup>
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link, useForm } from "@inertiajs/vue3";

defineProps({
    wallets: {
        type: Object,
    },
});

const form = useForm({
    address: "",
});

function destroyWallet(address, id) {
    if (confirm("Are you sure you want to delete the wallet: " + address)) {
        form.delete(route("profile.deleteWallet", id));
    }
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                Wallets
            </h2>

            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Update your account's wallets.
            </p>
        </header>

        <form
            @submit.prevent="
                form.post(route('profile.addWallet'), {
                    onSuccess: () => form.reset(),
                })
            "
            class="max-w-xl mt-6 space-x-6"
        >
            <div class="flex items-center gap-4">
                <TextInput
                    id="address"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.address"
                    required
                    autocomplete="address"
                />

                <InputError class="mt-2" :message="form.errors.address" />

                <PrimaryButton :disabled="form.processing">Add</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-zinc-600 dark:text-zinc-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>

    <section class="pt-4">
        <ul class="list-none">
            <li v-for="wallet in wallets" class="space-y-4">
                <div class="flex items-center gap-4">
                    <p class="text-lg text-zinc-600 dark:text-zinc-400">
                        {{ wallet.address }}
                    </p>
                    <Link
                        @click="destroyWallet(wallet.address, wallet.id)"
                        method="delete"
                        as="button"
                        type="button"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="w-4 h-4 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-zinc-800"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                            />
                        </svg>
                    </Link>
                </div>
            </li>
        </ul>
    </section>
</template>
