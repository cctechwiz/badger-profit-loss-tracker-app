<template>
    <AuthenticatedLayout>
        <title title="Admin">PL</title>
        <div class="page-background"></div>

        <div class="container mx-auto px-4">
            <div class="flex justify-center py-5 pt-20">
                <div class="bg-zinc-800 mx-6 text-white p-5 rounded-xl shadow-md w-48 text-center whitespace-nowrap" >
                    <span class="font-bold block mb-2">Registered Users</span>
                    <span class="text-3xl"> {{ users.length }} </span>
                </div>

                <div class="bg-zinc-800 mx-6 text-white p-5 rounded-xl shadow-md w-48 text-center whitespace-nowrap" >
                    <span class="font-bold block mb-2">Licenses</span>
                    <span class="text-3xl"> {{ props.licenses.length }} </span>
                </div>

                <div class="bg-zinc-800 mx-6 text-white p-5 rounded-xl shadow-md w-48 text-center whitespace-nowrap flex justify-center items-center" >
                    <SecondaryButton @click="toggleLicenseModal">
                       New License
                    </SecondaryButton>
                    <NewLicense :isVisible="isLicenseModalVisible" @update:isVisible="isLicenseModalVisible = $event" />
                </div>
            </div>

            <div class="flex justify-center py-5 pt-20">
                <div class="bg-zinc-800 mx-6 text-white p-5 rounded-xl shadow-md w-48 text-center whitespace-nowrap" >
                    <span class="font-bold block mb-2">Free Users</span>
                    <span class="text-3xl"> {{ freeUsersCount }} </span>
                </div>

                <div class="bg-zinc-800 mx-6 text-white p-5 rounded-xl shadow-md w-48 text-center whitespace-nowrap" >
                    <span class="font-bold block mb-2">Licensed Users</span>
                    <span class="text-3xl"> {{ licensedUsersCount }} </span>
                </div>

                <div class="bg-zinc-800 mx-6 text-white p-5 rounded-xl shadow-md w-48 text-center whitespace-nowrap" >
                    <span class="font-bold block mb-2">Users w/ Wallets</span>
                    <span class="text-3xl"> {{ usersWithWalletsCount }} </span>
                </div>

                <div class="bg-zinc-800 mx-6 text-white p-5 rounded-xl shadow-md w-48 text-center whitespace-nowrap" >
                    <span class="font-bold block mb-2">Users w/o Wallets</span>
                    <span class="text-3xl"> {{ usersWithoutWalletsCount }} </span>
                </div>
            </div>

            <h1 class="text-4xl dark:text-white">Licenses</h1>
            <table class="table-auto w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Name
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Product
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Years
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            ID
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Keys CSV
                        </th>
                    </tr>
                </thead>

                <tbody v-for="license in props.licenses" :key="license.id">
                    <tr>
                        <td class="border px-4 py-2 text-center dark:text-white" >
                            {{ license.name }}
                        </td>
                        <td class="border px-4 py-2 text-center dark:text-white" >
                            {{ license.product }}
                        </td>
                        <td class="border px-4 py-2 text-center dark:text-white" >
                            {{ license.years }}
                        </td>
                        <td class="border px-4 py-2 text-center dark:text-white" >
                            {{ license.id }}
                        </td>
                        <td class="border px-4 py-2 text-center dark:text-white">
                            <div class="flex justify-center">
                                <svg v-if="license.product.startsWith('bulk__')" @click="downloadLicenseKeys(license)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-arrow-down-fill" viewBox="0 0 16 16">
                                    <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1m-1 4v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 11.293V7.5a.5.5 0 0 1 1 0"/>
                                </svg>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h1 class="text-4xl dark:text-white mt-20">Users</h1>
            <table class="table-auto w-full">
                <colgroup>
                    <col style="width: 100px" />
                    <col style="width: 500px" />
                    <col style="width: 50px" />
                    <col style="width: 150px" />
                    <col style="width: 100px" />
                    <col style="width: 5px" />
                </colgroup>
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Username
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Wallets
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            licenses
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Is Admin
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            ID
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Created At
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Last Activity
                        </th>
                        <th class="px-4 py-2 text-center dark:text-white">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody v-for="user in localUsers" :key="user.id">
                    <tr>
                        <td class="border px-4 py-2 text-center dark:text-white" >
                            {{ user.username }}
                        </td>

                        <td
                            v-on:click="user.walletsOpen = !user.walletsOpen"
                            :class="user.walletsOpen ? 'bg-zinc-800' : ''"
                            class="border px-4 py-2 text-center dark:text-white cursor-pointer"
                        >
                            <!-- Right Arrow SVG (Shown when user.walletsOpen is false) -->
                            <svg
                                v-if="!user.walletsOpen"
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="inline-block mr-2"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M6.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L12.293 8 6.646 2.354a.5.5 0 0 1 0-.708z"
                                />
                            </svg>
                            <!-- Down Arrow SVG (Shown when user.walletsOpen is true) -->
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="inline-block mr-2"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M1.646 6.646a.5.5 0 0 1 .708 0L8 12.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"
                                />
                            </svg>
                            <span>{{ user.wallets.length }}</span>
                        </td>

                        <td
                            v-on:click="user.licensesOpen = !user.licensesOpen"
                            :class="user.licensesOpen ? 'bg-gray-800' : ''"
                            class="border px-4 py-2 text-center dark:text-white cursor-pointer">
                            <!-- Right Arrow SVG (Shown when user.licensesOpen is false) -->
                            <svg v-if="!user.licensesOpen"
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="inline-block mr-2"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M6.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L12.293 8 6.646 2.354a.5.5 0 0 1 0-.708z"
                                />
                            </svg>
                            <!-- Down Arrow SVG (Shown when user.licensesOpen is true) -->
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="inline-block mr-2"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M1.646 6.646a.5.5 0 0 1 .708 0L8 12.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"
                                />
                            </svg>
                            <span>{{ user.licenses?.length }}</span>
                        </td>

                        <td class="border px-4 py-2 text-center dark:text-white" >
                            <input
                                type="checkbox"
                                v-model="user.isAdmin"
                                @change="updateAdminStatus(user)"
                                :disabled="currentUser.id === user.id"
                            />
                        </td>

                        <td class="border px-4 py-2 text-center dark:text-white" >
                            {{ user.id }}
                        </td>

                        <td class="border px-4 py-2 text-center dark:text-white" >
                            {{
                                new Date(user.created_at).toLocaleDateString("en-US")
                            }}
                        </td>

                        <td class="border px-4 py-2 text-center dark:text-white" >
                            {{
                                convertUTCToLocalTime(user.last_activity)
                            }}
                        </td>

                        <td class="border px-4 py-2 text-center dark:text-white">
                            <div class="flex">
                                <div title="delete user">
                                    <svg @click="deleteUser(user)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                    </svg>
                                </div>

                                <div title="reset password">
                                    <svg @click="updateUserPassword(user)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="ml-3 bi bi-lock" viewBox="0 0 16 16">
                                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2M5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1"/>
                                    </svg>
                                </div>

                                <div title="impersonate user">
                                    <svg @click="impersonateUser(user)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="ml-3 bi bi-person-up" viewBox="0 0 16 16">
                                        <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.354-5.854 1.5 1.5a.5.5 0 0 1-.708.708L13 11.707V14.5a.5.5 0 0 1-1 0v-2.793l-.646.647a.5.5 0 0 1-.708-.708l1.5-1.5a.5.5 0 0 1 .708 0M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                                        <path d="M8.256 14a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z"/>
                                    </svg>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="user?.walletsOpen" v-for="wallet in user.wallets">
                        <td></td>
                        <td class="py-2 text-center dark:text-white">
                            {{ wallet.address }}
                        </td>
                    </tr>

                    <tr
                        v-if="user?.licensesOpen"
                        v-for="license in user.licenses"
                    >
                        <td></td>
                        <td></td>
                        <td class="py-2 text-center dark:text-white">
                            {{ license.name }}
                            <svg
                                @click="removeLicense(user, license)"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3 inline cursor-pointer"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </td>
                    </tr>
                    <tr v-if="user?.licensesOpen" >
                        <td></td>
                        <td></td>
                        <td>
                            <select v-model="selectedLicense">
                                <option
                                    v-for="license in props.licenses"
                                    :key="license.id"
                                    :value="license"
                                >
                                    {{ license.name }}
                                </option>
                            </select>
                        </td>

                        <td>
                            <button
                                class="dark:text-white"
                                @click="addLicense(user, selectedLicense)"
                            >
                                Add
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import SecondaryButton from "../../Components/SecondaryButton.vue";
import NewLicense from "@/Components/NewLicense.vue";
import { computed, ref } from 'vue'
import { saveAs } from "file-saver";
import { usePage } from '@inertiajs/vue3'

const page = usePage()
// Inertia page.props defined in app/Http/Middleware/HandleInertiaRequests.php
const currentUser = computed(() => page.props.auth.user)

const props = defineProps([
    'users',
    'licenses',
]);

const localUsers = ref(props.users);

const freeUsersCount = computed(() => {
    return localUsers.value.filter(user => {
        if (!user.licenses) return true
        return user.licenses.length == 0
    }).length
})

const licensedUsersCount = computed(() => {
    return localUsers.value.filter(user => {
        if (!user.licenses) return false
        return user.licenses.length > 0
    }).length
})

const usersWithWalletsCount = computed(() => {
    return localUsers.value.filter(user => {
        if (!user.wallets) return true
        return user.wallets.length == 0
    }).length
})

const usersWithoutWalletsCount = computed(() => {
    return localUsers.value.filter(user => {
        if (!user.wallets) return false
        return user.wallets.length > 0
    }).length
})

const isLicenseModalVisible = ref(false);

function toggleLicenseModal() {
  isLicenseModalVisible.value = !isLicenseModalVisible.value;
}

const selectedLicense = ref(props?.licenses?.at(-1));

localUsers.value.forEach((user) => {
    user.walletsOpen = false;
    user.licensesOpen = false;
});

const updateAdminStatus = async (user) => {
    if (currentUser.value.id === user.id) {
        console.error("Cannot change your own admin status");
        return;
    }

    try {
        await axios.post(route("admin.updateAdminStatus", user.id), {
            isAdmin: user.isAdmin,
        });
    } catch (error) {
        console.log(error);
    }
};

const addLicense = async (user, license) => {
    console.log("add license", user.username, license);

    const licenseIndex = user.licenses.findIndex(l => l.id === license.id);
    if (licenseIndex === -1) {
        try {
            await axios.post(
                route("admin.addUserLicense", user.id),
                {
                    licenseId: license.id,
                }
            );

            user.licenses.push(license);
        } catch (error) {
            console.log(error);
        }
    }
};

const removeLicense = async (user, license) => {
    console.log("remove license", user.username, license);

    try {
        await axios.post(
            route("admin.removeUserLicense", user.id),
            {
                licenseId: license.id,
            }
        );
        user.licenses = user.licenses.filter(l => l.id !== license.id);
    } catch (error) {
        console.log(error);
    }
};

const deleteUser = async (user) => {
    console.log("delete user", user.username);

    if (currentUser.value.id === user.id) {
        console.error("Cannot delete your own user");
        return;
    }

    if (confirm(`Are you sure you want to delete the user ${user.username}?`)) {
        try {
            await axios.delete(
                route("admin.deleteUser", user.id),
            );
            localUsers.value = localUsers.value.filter(u => u.id !== user.id);
        } catch (error) {
            console.log(error);
        }
    }
}

const updateUserPassword = async (user) => {
    console.log("update user password", user.username);

    const password = prompt(`New Password for ${user.username}`);
    if (password !== null) {
        try {
            await axios.post(
                route("admin.updateUserPassword", user.id),
                {
                    password: password,
                }
            );
        } catch (error) {
            console.log(error);
            alert(error.response?.data?.message || "Failed to reset password");
        }
    }
}

const downloadLicenseKeys = async (license) => {
    console.log("Downloading License Keys for: ", license.name);

    try {
        const response = await axios.get(
            route("admin.downloadLicenseKeys", license.id),
            { responseType: "blob" }
        );

        const blobData = new Blob([response.data], {
            type: "text/csv",
        });

        const today = new Date();
        const dateString = today.toISOString().slice(0,10);
        const filename = `${dateString}_${license.name}_keys.csv`;

        saveAs(blobData, filename);
    } catch (error) {
        console.log(error);
    }
}

const impersonateUser = async (user) => {
    console.log("impersonate user", user.username);

    if (currentUser.value.id === user.id) {
        console.error("Cannot impersonate your own user");
        return;
    }

    if (confirm(`Are you sure you want to impersonate ${user.username}?`)) {
        try {
            await axios.post(
                route("admin.impersonateUser", user.id),
            );
            location.reload() // controller returns a redirect, this is a hack to follow it
        } catch (error) {
            console.log(error);
        }
    }
}


function convertUTCToLocalTime(utcString) {
  if (!utcString) return ''; // Handle null or undefined last_activity
  const isoString = `${utcString.replace(' ', 'T')}Z`; // Convert to ISO format
  const date = new Date(isoString);
  return date.toLocaleString(); // Convert to local time string
}
</script>
