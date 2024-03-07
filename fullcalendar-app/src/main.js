import { createApp } from 'vue';
import App from './App.vue';
import ClickOutsideDirective from './directives/clickOutsideDirective';
import GlobalMixins from './mixins/globalMixins';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-icons/font/bootstrap-icons.css';

const app = createApp(App);

app.directive('click-outside', ClickOutsideDirective);
app.mixin(GlobalMixins);

app.mount('#fullcalendar-app');
