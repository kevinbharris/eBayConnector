<x-admin::layouts>
    <x-slot:title>
        @lang('ebayconnector::app.configuration.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('ebayconnector::app.configuration.title')
        </p>
    </div>

    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
            <div class="p-4 bg-white dark:bg-gray-900 rounded box-shadow">
                <form method="POST" action="{{ route('ebayconnector.admin.configuration.store') }}" @submit.prevent="onSubmit">
                    @csrf

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('Enable eBay Connector')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="checkbox"
                            name="enabled"
                            id="enabled"
                            value="1"
                            :checked="old('enabled', $configurations['enabled'] ?? false)"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('Environment')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="environment"
                            id="environment"
                            rules="required"
                        >
                            <option value="sandbox" {{ old('environment', $configurations['environment'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ old('environment', $configurations['environment'] ?? 'sandbox') == 'production' ? 'selected' : '' }}>Production</option>
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('API Key (Client ID)')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="api_key"
                            id="api_key"
                            :value="old('api_key', $configurations['api_key'] ?? '')"
                            rules="required"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('API Secret (Client Secret)')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="api_secret"
                            id="api_secret"
                            :value="old('api_secret', $configurations['api_secret'] ?? '')"
                            rules="required"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('Developer ID')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="dev_id"
                            id="dev_id"
                            :value="old('dev_id', $configurations['dev_id'] ?? '')"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('Certificate ID')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="cert_id"
                            id="cert_id"
                            :value="old('cert_id', $configurations['cert_id'] ?? '')"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('Auto Sync Products')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="checkbox"
                            name="auto_sync_products"
                            id="auto_sync_products"
                            value="1"
                            :checked="old('auto_sync_products', $configurations['auto_sync_products'] ?? false)"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('Auto Sync Orders')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="checkbox"
                            name="auto_sync_orders"
                            id="auto_sync_orders"
                            value="1"
                            :checked="old('auto_sync_orders', $configurations['auto_sync_orders'] ?? false)"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('Sync Interval (minutes)')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="sync_interval"
                            id="sync_interval"
                            :value="old('sync_interval', $configurations['sync_interval'] ?? 15)"
                        />
                    </x-admin::form.control-group>

                    <div class="flex gap-2 mt-4">
                        <button type="submit" class="primary-button">
                            @lang('ebayconnector::app.configuration.save')
                        </button>
                        <button type="button" class="secondary-button" @click="testConnection">
                            @lang('ebayconnector::app.configuration.test_connection')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin::layouts>

@push('scripts')
    <script type="text/x-template" id="ebay-config-template">
        <div>
        </div>
    </script>

    <script type="module">
        app.component('ebay-config', {
            template: '#ebay-config-template',
            
            methods: {
                onSubmit(params, { resetForm, setErrors }) {
                    this.$axios.post("{{ route('ebayconnector.admin.configuration.store') }}", params)
                        .then(response => {
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch(error => {
                            if (error.response.status == 422) {
                                setErrors(error.response.data.errors);
                            }
                        });
                },

                testConnection() {
                    const formData = {
                        api_key: document.getElementById('api_key').value,
                        api_secret: document.getElementById('api_secret').value,
                        environment: document.getElementById('environment').value
                    };

                    this.$axios.post("{{ route('ebayconnector.admin.configuration.test') }}", formData)
                        .then(response => {
                            this.$emitter.emit('add-flash', { 
                                type: response.data.success ? 'success' : 'error', 
                                message: response.data.message 
                            });
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', { type: 'error', message: error.message });
                        });
                }
            }
        });
    </script>
@endpush
