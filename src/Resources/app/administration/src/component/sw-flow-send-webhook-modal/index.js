const { Component } = Shopware;

Component.register('sw-flow-send-webhook-modal', {
    template: `
        <sw-modal
            :title="$tc('sw-flow.actions.label')"
            @modal-close="onCloseModal"
        >
            <sw-container class="sw-flow-send-webhook-modal__content">
                <sw-alert variant="info">{{$tc('sw-flow.actions.description')}}</sw-alert>
            </sw-container>
            <template #modal-footer>
                <sw-button variant="primary" @click="onAddAction">{{$tc('global.default.confirm')}}</sw-button>
                <sw-button variant="default" @click="onCloseModal">{{$tc('global.default.cancel')}}</sw-button>
            </template>
        </sw-modal>
    `,

    props: {
        sequence: {
            type: Object,
            required: true
        }
    },

    methods: {
        onAddAction() {
            const sequence = {
                ...(this.sequence ?? {}),
                actionName: 'action.send_order_status_update',
                config: {},
                position: this.sequence?.position ?? 1
            };

            this.$emit('process-finish', sequence);
        },

        onCloseModal() {
            this.$emit('modal-close');
        }
    }
});