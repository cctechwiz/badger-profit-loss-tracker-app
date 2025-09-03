<template>
    <div v-if="isVisible" class="fixed inset-0 bg-zinc bg-opacity-50 flex justify-center items-center">
        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-zinc-800 shadow-md overflow-hidden sm:rounded-lg"
            @click.stop>

            <Head title="New License" />

            <form @submit.prevent="submit" ref="newLicenseForm">
                <div class="mt-4">
                    <InputLabel for="name" value="Name" />

                    <TextInput
                        id="name"
                        ref="nameInput"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autocomplete="name"
                        autofocus
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="mt-4">
                    <InputLabel for="product" value="Product" />

                    <TextInput
                        id="product"
                        type="text"
                        :class="{
                            'mt-1 block w-full': true,
                            'bg-gray-200 text-gray-500 dark:bg-zinc-600 dark:text-gray-400': form.isBulk,
                        }"
                        v-model="form.product"
                        required
                        autocomplete="product"
                        :disabled="form.isBulk"
                    />

                    <InputError class="mt-2" :message="form.errors.product" />
                </div>

                <div class="mt-4">
                    <InputLabel for="years" value="Years" />

                    <TextInput
                        id="years"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.years"
                        required
                        autocomplete="years"
                    />

                    <InputError class="mt-2" :message="form.errors.years" />
                </div>


                <div class="flex items-center justify-start mt-4">
                    <input
                        id="isBulk"
                        type="checkbox"
                        class="mr-2"
                        v-model="form.isBulk"
                    />

                    <InputLabel for="isBulk" value="Bulk License" />
                </div>

                <div class="mt-4"
                    v-if="form.isBulk">
                    <InputLabel for="keyCount" value="Key Count" />

                    <TextInput
                        id="keyCount"
                        type="number"
                        class="mt-1 block w-full"
                        v-model="form.keyCount"
                        required
                        autocomplete="0"
                    />

                    <InputError class="mt-2" :message="form.errors.keyCount" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <PrimaryButton
                        @click="close"
                        type="button"
                        class="ms-4"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Cancel
                    </PrimaryButton>

                    <PrimaryButton
                        type="submit"
                        class="ms-4"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Register
                    </PrimaryButton>
                </div>
            </form>

        </div>
    </div>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps, defineEmits, ref, nextTick, watch } from 'vue';

const props = defineProps({
    isVisible: Boolean,
});

const newLicenseForm = ref();
const yearInput = ref();
const form = useForm({
    name: '',
    product: '',
    years: '',
    isBulk: false,
    keyCount: 0,
});

const emits = defineEmits(['update:isVisible']);

function close() {
    emits('update:isVisible', false);
}

watch(
  () => form.isBulk,
  (newValue, _oldValue) => {
    if (newValue === true) {
      form.product = 'bulk (auto-generated)';
    } else {
      form.product = '';
    }
  }
);

const submit = () => {
    const yearsArray = form.years.split(',').map(year => parseInt(year.trim(), 10));
    form.years = yearsArray;

    if (form.isBulk) {
        form.product = 'bulk__' + form.name + '__' + form.keyCount;
    }

    form.post(route('admin.createLicense'), {
        onFinish: async () => {
            newLicenseForm.value.reset();
            await nextTick();
            yearInput.value.focus();
        },
    });
};
</script>

