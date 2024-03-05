import { createApp } from 'vue';
import App from './App.vue';
import ClickOutsideDirective from './directives/clickOutsideDirective';
import GlobalMixins from './mixins/globalMixins';

const app = createApp(App);

app.directive('click-outside', ClickOutsideDirective);
app.mixin(GlobalMixins);

app.mount('#fullcalendar-app');
