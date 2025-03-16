(function(){var e={224:function(){let{Component:e}=Shopware;e.register("sw-flow-send-webhook-modal",{template:`
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
    `,props:{sequence:{type:Object,required:!0}},methods:{onAddAction(){let e={...this.sequence??{},actionName:"action.send_order_status_update",config:{},position:this.sequence?.position??1};this.$emit("process-finish",e)},onCloseModal(){this.$emit("modal-close")}}})}},t={};function o(s){var n=t[s];if(void 0!==n)return n.exports;var i=t[s]={exports:{}};return e[s](i,i.exports,o),i.exports}o.p="bundles/syncorderdata/",window?.__sw__?.assetPath&&(o.p=window.__sw__.assetPath+"/bundles/syncorderdata/"),function(){"use strict";let e=Object.freeze({HANDLE:"action.send_order_status_update",COMPONENT_NAME:"sw-flow-send-webhook-modal",LABEL:"Webhook Send Order ",ICON:"regular-envelope"}),{Component:t}=Shopware;t.override("sw-flow-sequence-action",{computed:{groups(){return this.actionGroups.unshift("WebHooks"),this.$super("groups")},modalName(){return this.selectedAction===e.HANDLE?e.COMPONENT_NAME:this.$super("modalName")},actionDescription(){return{...this.$super("actionDescription"),[e.HANDLE]:e=>this.callWebhookSendOrderDataService(e)}}},methods:{getActionDescriptions(t){let o=this.$super("getActionDescriptions",t);return t.actionName===e.HANDLE?this.callWebhookSendOrderDataService(t.config):o},callWebhookSendOrderDataService(e){},getActionTitle(t){return t===e.HANDLE?{group:"WebHooks",value:t,icon:e.ICON,label:this.$tc("WEBHOOK_ACTION.LABEL")}:this.$super("getActionTitle",t)}}}),o(224)}()})();