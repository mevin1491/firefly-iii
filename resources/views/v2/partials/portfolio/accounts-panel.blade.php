<div class="col-12">
    <div class="card mb-2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Platform Accounts</h3>
            <div>
                <button class="btn btn-sm btn-primary" @click="showAddForm = !showAddForm">
                    <i class="fa-solid fa-plus"></i> Add Account
                </button>
                <button class="btn btn-sm btn-success" @click="syncAll()" :disabled="syncing">
                    <template x-if="syncing">
                        <em class="fa-solid fa-spinner fa-spin"></em>
                    </template>
                    <template x-if="!syncing">
                        <i class="fa-solid fa-rotate"></i>
                    </template>
                    Sync All
                </button>
            </div>
        </div>
        <div class="card-body">
            <template x-if="showAddForm">
                <div class="row mb-3 p-3 bg-light rounded">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Account name" x-model="newAccount.name">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" x-model="newAccount.platform">
                            <option value="">Select platform...</option>
                            <option value="moomoo">Moomoo</option>
                            <option value="fsmone">FSMOne</option>
                            <option value="luno">Luno</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" @click="addAccount()">Save</button>
                        <button class="btn btn-secondary" @click="showAddForm = false">Cancel</button>
                    </div>
                </div>
            </template>

            <template x-if="accountList.length === 0 && !loading">
                <p class="text-muted text-center">No portfolio accounts configured. Click "Add Account" to get started.</p>
            </template>

            <template x-if="accountList.length > 0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Platform</th>
                                <th>Last Synced</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(acct, index) in accountList" :key="acct.id">
                                <tr>
                                    <td x-text="acct.attributes.name"></td>
                                    <td><span class="badge" :class="platformBadge(acct.attributes.platform)" x-text="acct.attributes.platform"></span></td>
                                    <td x-text="acct.attributes.last_synced_at ? new Date(acct.attributes.last_synced_at).toLocaleString() : 'Never'"></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" @click="syncOne(acct.id)" :disabled="syncing">
                                            <i class="fa-solid fa-rotate"></i>
                                        </button>
                                        <template x-if="acct.attributes.platform === 'fsmone'">
                                            <span>
                                                <input type="file" :id="'csv-' + acct.id" class="d-none" accept=".csv,.txt,.xls,.xlsx" @change="importCsv(acct.id, $event)">
                                                <button class="btn btn-sm btn-outline-success" @click="document.getElementById('csv-' + acct.id).click()">
                                                    <i class="fa-solid fa-upload"></i> CSV
                                                </button>
                                            </span>
                                        </template>
                                        <button class="btn btn-sm btn-outline-danger" @click="deleteAccount(acct.id)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>
</div>
