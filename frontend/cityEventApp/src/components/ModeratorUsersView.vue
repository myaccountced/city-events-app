<script setup lang="ts">
import {
  AutoComplete,
  Button,
  ButtonGroup,
  Column,
  ConfirmDialog,
  DataTable,
  Dialog,
  RadioButton,
  Textarea,
  Toast,
  useConfirm,
  useToast
} from "primevue";
import {useAuth} from "@/useAuth";
import {onBeforeUnmount, onMounted, reactive, ref} from 'vue'
import {useRouter} from 'vue-router'
import 'primeicons/primeicons.css'
import 'bootstrap-icons/font/bootstrap-icons.css'

const backendUrl = import.meta.env.VITE_BACKEND_URL;
const router = useRouter();
const confirm = useConfirm();
const toast = useToast();
const { token } = useAuth();

// Sends the user to the reported tab
const goToReported = () => {
  router.push("/moderator/reported")
}

// Sends the user to the pending tab
const goToPending = () => {
  router.push("/moderator/pending")
}

// Variables for the status text
const loadingText = "Loading users...";
const networkErrorText = "Unable to connect to the network";
let bigHeadText = ref(loadingText);

// Variable true if there are more users that COULD be loaded
let canLoadMore = true;

// Default limit on users pulled
const limit = 20;

// Variable storing the current loading offset
let offset = 0;

// Variables modeling the search bar and what search text has been loaded to the table
let searchInput = ref("");
let currentSearchInput = ref("");
let tempSearchVar = "";

// Variables modelling the datatable's sort categories
let sortCategory = ref("username");
let sortDirection = ref(1);

// Variables holding the users loaded to autocomplete, and users loaded to the table
let autocompleteUsers = ref([]);
let users = ref([]);

const expandedRows = ref([]);

const banDialog = reactive({
  visible: false,
  userId: null as number | null,
  username: '',
  selectedReason: null as string | null,
  customReason: ''
});

const banReasons = [
  {
    id: 'inappropriate',
    label: 'Inappropriate Content',
    description: 'Sharing explicit, pornographic, or violent content in your profile or messages.'
  },
  {
    id: 'harassment',
    label: 'Abuse of System',
    description: 'Too many attempts to report or similar issues.'
  },
  {
    id: 'other',
    label: 'Other',
    description: 'A reason not listed above. 1-255 characters.'
  }
];


onMounted(() => {
  fetchUsers(true);

  const handleScroll = () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 50 && canLoadMore) {
      offset += limit;
      fetchUsers(true);
    }
  };

  window.addEventListener('scroll', handleScroll);

  onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll); // Clean up event listener
  });
});


/**
 * Loads users to the autocomplete and potentially to the table, depending on changeTable.
 *
 * @param changeTable true if this search is updating the table, false otherwise
 * @param clear true if the table must be cleared before it is updated, false by default
 */
async function fetchUsers(changeTable: boolean, clear: boolean = false)
{
  try {
    // Changing the heading text to Loading
    bigHeadText.value = changeTable ? loadingText : bigHeadText.value;

    // determining whether to load based on autocomplete or search
    let loading = changeTable ? ( clear ? 0 : offset ) : 0;
    let like = changeTable ? currentSearchInput.value : searchInput.value;
    let sort = sortCategory.value ? sortCategory.value : 'username' ;

    const queryParams = new URLSearchParams({
      limit: limit.toString(),
      offset: loading.toString(),
      sort: sort.toString(),
    })

    if (sortDirection.value !== 1) {
      queryParams.set('reverse', true);
    }

    queryParams.set('like', like);


    // Prevents function from being called while already fetching
    canLoadMore = false;

    // Fetching users from the backend
    const res = await fetch(`${backendUrl}/users?${queryParams.toString()}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token.value
      }
    });

    if (res.ok) {
      // JSON array of users
      let data = await res.json();
      autocompleteUsers.value = [];

      if (Array.isArray(data)) {
        // There are users in the JSON array
        if (data.length) {
          // Checks if the maximum amount of users have been returned
          if (changeTable) {
            canLoadMore = data.length >= limit;
          } else {
            canLoadMore = true;
          }

          // Mapping data to user array
          autocompleteUsers.value = data.map((user: any) => {
            return {
              id: user.userId || user.id,
              username: user.username,
              email: user.email,
              creationDate: new Date(user.creationDate.date),
              bannedAt: user.bannedAt || null,
              banned: user.isBanned || false,
              reason: user.reason || '',

              type: user.mod ? 'Moderator' : (user.subscriptions ? 'Premium' : '')
            }
          });

        } else {
          // No users were returned
          canLoadMore = !changeTable;

          // Making the table's search input its old value
          if (loading === 0 && changeTable) {
            currentSearchInput.value = tempSearchVar;
            canLoadMore = true;
          }
        }
      } else {
        console.error('Unexpected response format: expected an array.');
      }

    }
    else {
      // Response was not ok
      bigHeadText.value = changeTable ? networkErrorText : bigHeadText.value;
      throw new Error(`Error fetching users: ${res.statusText}`)
    }

  } catch (e) {
    // Fetch error
    console.log(e);
    bigHeadText.value = changeTable ? networkErrorText : bigHeadText.value;
    toast.add({
      severity: 'error',
      summary: 'Server Error',
      detail: 'Unable to load users. Try again later.',
      life: 5000
    })
  }

  // Checking if the table will be updated with the new users
  if (changeTable)
  {
    // Checking if the old users will be removed
    if (clear && autocompleteUsers.value.length > 0) {
      offset = 0;
      users.value = [];
    }

    // Adding the new users to the old users
    users.value = [ ...users.value, ...autocompleteUsers.value]
  }

}


/**
 * Updates the table to display the search bar's results.
 *  Executed when the user types the enter key while in the search bar.
 */
function searchEnter() {
  // Checking if the search input is a string or a user object (from autocomplete)
  if (searchInput.value && searchInput.value.username) {
    searchInput.value = searchInput.value.username;
  }

  // Updating the current search to be the searchInput
  tempSearchVar = currentSearchInput.value;
  currentSearchInput.value = searchInput.value;
  canLoadMore = true;
  fetchUsers(true, true)
}


const banUser = async () => {
  try {
    const reason =
        banDialog.selectedReason === 'other'
            ? banDialog.customReason
            : banReasons.find((r) => r.id === banDialog.selectedReason)?.label
    const response = await fetch(`${backendUrl}/banuser`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId: banDialog.userId, reason })
    })
    if (!response.ok) {
      throw new Error('Error banning user')
    }
    // Update the banned status locally
    const userIndex = users.value.findIndex((u) => u.id === banDialog.userId)
    if (userIndex !== -1) {
      users.value[userIndex].banned = true
      users.value[userIndex].bannedAt = new Date().toDateString()
      users.value[userIndex].reason = reason?.toString()
    }
    toast.add({
      severity: 'success',
      summary: 'User Banned',
      detail: `${banDialog.username} has been banned`,
      life: 5000
    })
  } catch (error) {
    console.error('Error banning user:', error)
    toast.add({
      severity: 'error',
      summary: 'Server Error',
      detail: 'Unable to ban the user. Try again later.',
      life: 5000
    })
  } finally {
    // Close and reset the dialog
    banDialog.visible = false
    banDialog.selectedReason = null
    banDialog.customReason = ''
  }
}

const confirmUnbanUser = (userId: number, username: string) => {
  confirm.require({
    message: `Are you sure you want to unban ${username}?`,
    header: 'Unban User Confirmation',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
    acceptProps: { label: 'Proceed' },
    accept: () => unbanUser(userId, username),
    reject: () => console.log('Unban operation cancelled.')
  })
}

const unbanUser = async (userId: number, username: string) => {
  try {
    const response = await fetch(`${backendUrl}/unbanuser`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId })
    })
    if (!response.ok) {
      throw new Error('Error unbanning user')
    }
    // Update local user data
    const userIndex = users.value.findIndex((u) => u.id === userId)
    if (userIndex !== -1) {
      users.value[userIndex].banned = false
      users.value[userIndex].bannedAt = null
      users.value[userIndex].reason = ''
      // Force reactivity update if needed
      users.value = [...users.value]
    }
    toast.add({
      severity: 'success',
      summary: 'User Unbanned',
      detail: `${username} has been unbanned`,
      life: 5000
    })
  } catch (error) {
    console.error('Error unbanning user:', error)
    toast.add({
      severity: 'error',
      summary: 'Server Error',
      detail: 'Unable to unban the user. Try again later.',
      life: 5000
    })
  }
}

const rowClass = (data: any) => data.banned ? 'banned-row' : ''
const rowStyle = (data: any) => data.banned ? { backgroundColor: 'maroon', color: 'white' } : {}

const onRowExpand = (event: any) => {
  const expandedUser = event.data
  console.log('Expanded row for user ID:', expandedUser.id)
}

const showBanDialog = (userId: number, username: string) => {
  banDialog.userId = userId
  banDialog.username = username
  banDialog.visible = true
}

</script>

<template>

  <ButtonGroup>
    <Button id="pendingTab" label="Pending" severity="secondary" @click="goToPending" />
    <Button id="reportedTab" label="Reported" severity="secondary" @click="goToReported"/>
    <Button id="usersTab" label="Users" severity="warn" disabled />
  </ButtonGroup>

  <h1 v-if="users.length === 0" id="no-users" v-text="bigHeadText"></h1>

  <DataTable
    id="usersTable"
    :value="users"
    class="p-datatable-striped"
    :responsiveLayout="'scroll'"
    :rowHover="true"
    :emptyMessage="'No users found.'"
    data-key="id"
    :rowClass="rowClass"
    :rowStyle="rowStyle"
    v-model:expandedRows="expandedRows"
    @rowExpand="onRowExpand"
    sortField="username"
    :sortOrder="1"
    v-model:sort-field="sortCategory"
    v-model:sort-order="sortDirection"
    @sort="fetchUsers(true, true)"
    lazy
  >

    <template #header>
      <!-- User Search Bar -->
      <AutoComplete id="searchUsers" v-model="searchInput" :suggestions="autocompleteUsers" optionLabel="username"
                    placeholder="Search..." @complete="fetchUsers(false)" v-if="users.length !== 0"
                    v-on:keyup.enter="searchEnter" :delay="500" ></AutoComplete>
    </template>

    <Column expander id="expander" style="width: 5rem" />

    <!-- User Type Column -->
    <Column field="type" header="Type"  bodyClass="user-type">
      <template #body="{ data }">
        <i v-if="data.type === 'Moderator'" class="pi pi-user moderatorAccount" v-tooltip="data.type"></i>
        <i v-if="data.type === 'Premium'" class="pi pi-trophy premiumAccount" v-tooltip="data.type"></i>
        <span v-if="data.type === ''" class="regularAccount"></span>
      </template>
    </Column>

    <!-- Username Column -->
    <Column field="username" header="Username" bodyClass="user-username" :sortable="true" ></Column>

    <!-- Email -->
    <Column field="email" header="Email"  bodyClass="user-email" :sortable="true"></Column>

    <!-- Account Creation Date -->
    <Column field="creationDate" header="Creation Date" bodyClass="user-date" :sortable="true">
      <template #body="{ data }">
        {{ data.creationDate.toLocaleDateString() + " at " + data.creationDate.toLocaleTimeString() }}
      </template>
    </Column>

      <Column field="bannedAt" header="Banned At"></Column>
      <!-- Updated Reason Column with truncation and expansion -->
      <Column header="Reason">
        <template #body="{ data }">
          <span v-if="data.reason && data.reason.length > 20">
            {{ data.reason.substring(0, 20) }}...
          </span>
          <span v-else>
            {{ data.reason }}
          </span>
        </template>
      </Column>
      <Column header="Actions">
        <template #body="{ data }">
          <span
            v-if="!data.banned"
            class="pi pi-thumbs-down"
            style="font-size: 2rem; cursor: pointer"
            title="Ban this user"
            @click="showBanDialog(data.id, data.username)"
          ></span>
          <span
            v-else
            class="pi pi-thumbs-up"
            style="font-size: 2rem; cursor: pointer"
            title="Unban this user"
            @click="confirmUnbanUser(data.id, data.username)"
          ></span>
        </template>
      </Column>

      <Toast></Toast>
      <!-- Expansion template shows full reason -->
      <template #expansion="{ data }">
        <div class="p-ml-4">
          <h3>Full banned reason is:</h3>
          <p>{{ data.reason }}</p>
        </div>
      </template>
    </DataTable>

  <!-- Ban Dialog -->
  <Dialog v-model:visible="banDialog.visible" header="Ban User" :style="{ width: '25rem' }">
    <p>Select the reason for banning {{ banDialog.username }}:</p>
    <div>
      <div v-for="reason in banReasons" :key="reason.id" class="flex items-center mb-3">
        <RadioButton
          :inputId="reason.id"
          name="banReason"
          :value="reason.id"
          v-model="banDialog.selectedReason"
          class="mr-3"
        />
        <label :for="reason.id">
          <strong>{{ reason.label }}</strong>: {{ reason.description }}
        </label>
      </div>
      <div v-if="banDialog.selectedReason === 'other'" class="mt-4">
        <Textarea
          v-model="banDialog.customReason"
          rows="4"
          class="w-full"
          placeholder="Please provide details..."
        />
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" severity="secondary" @click="banDialog.visible = false" />
      <Button
        label="Submit"
        severity="danger"
        :disabled="
          !banDialog.selectedReason ||
          (banDialog.selectedReason === 'other' &&
            (!banDialog.customReason.trim() || banDialog.customReason.trim().length > 255))
        "
        @click="banUser"
      />
    </template>
  </Dialog>
</template>

<style scoped>
.custom-button-active {
  background-color: #0d5aa7;
}

.custom-button-secondary {
  background-color: gray;
}

td {
  padding-left: 2em;
  padding-right: 2em;
  text-wrap: nowrap;
}

strong {
  font-weight: bold;
}

/* Target the <tr> element */
.p-datatable .p-datatable-tbody > tr.banned-row {
  background-color: maroon !important;
  color: white !important;
}


/* Target the <td> elements within the row */
.p-datatable .p-datatable-tbody > tr.banned-row > td {
  background-color: maroon !important;
  color: white !important;
}

#searchUsers {
  display: flex;
  justify-content: center;
}
</style>