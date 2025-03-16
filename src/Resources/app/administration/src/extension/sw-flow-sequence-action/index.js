import {WEBHOOK_ACTION} from '../../constant/flow-action.constants';

const {Component} = Shopware;

Component.override('sw-flow-sequence-action', {
    computed: {

        groups() {
            this.actionGroups.unshift('WebHooks');
            return this.$super('groups');
        },

        modalName() {
            switch (this.selectedAction) {
                case WEBHOOK_ACTION.HANDLE:
                    return WEBHOOK_ACTION.COMPONENT_NAME;
                default:
                    return this.$super('modalName');
            }
        },

        actionDescription() {
            const actionDescriptionList = this.$super('actionDescription');

            return {
                ...actionDescriptionList,
                [WEBHOOK_ACTION.HANDLE]: (config) => this.callWebhookSendOrderDataService(config),

            };
        },
    },

    methods: {

        getActionDescriptions(sequence) {
            const actionDescriptionList = this.$super('getActionDescriptions', sequence);
            switch (sequence.actionName) {
                case WEBHOOK_ACTION.HANDLE:
                    return this.callWebhookSendOrderDataService(sequence.config);
                default:
                    return actionDescriptionList;
            }
        },


        callWebhookSendOrderDataService(config) {

        },

        getActionTitle(actionName) {
            switch (actionName) {
                case WEBHOOK_ACTION.HANDLE:
                    return {
                        group: 'WebHooks',
                        value: actionName,
                        icon: WEBHOOK_ACTION.ICON,
                        label: this.$tc('sw-flow.actions.label'),
                    };
                default:
                    return this.$super('getActionTitle', actionName);
            }
        },
    },
});
