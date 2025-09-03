<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                Licenses
            </h2>

            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Register new license
            </p>
        </header>

        <form @submit.prevent="submit" ref="licenseForm">
            <div class="mt-4">
                <InputLabel for="name" value="License Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="key" value="License Key" />
                <TextInput
                    id="key"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.key"
                    required
                    autocomplete="key"
                />
                <InputError class="mt-2" :message="form.errors.key" />
            </div>

            <div class="mt-4">
                <PrimaryButton :disabled="form.processing">Activate</PrimaryButton>
            </div>
        </form>
    </section>

    <section class="pt-8">
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Registered licenses
        </p>
        <table class="table-auto w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-center dark:text-white">
                        License
                    </th>
                    <th class="px-4 py-2 text-center dark:text-white">
                        Years
                    </th>
                </tr>
            </thead>
            <tr v-for="license in props.licenses">
                <td class="border px-4 py-2 text-center dark:text-white" >
                    {{ license.name }}
                </td>
                <td class="border px-4 py-2 text-center dark:text-white" >
                    {{ license.years }}
                </td>
            </tr>
        </table>
    </section>
</template>

<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { reactive, ref } from "vue";
import { useForm } from '@inertiajs/vue3'


const props = defineProps([
    'licenses',
]);

const licenseForm = ref()
const form = useForm({
    name: "",
    key: "",
});

const submit = () => {
    console.log("submit");
    form.post(route('profile.addLicense'), {
        onSuccess: async () => {
            licenseForm.value.reset();
        },
    });
};
</script>
