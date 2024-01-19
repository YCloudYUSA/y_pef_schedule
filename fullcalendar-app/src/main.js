import { createApp } from 'vue';
import App from './App.vue';
import { createRouter, createWebHistory } from 'vue-router';

// import HomePage from './components/HomePage.vue';

import ClickOutsideDirective from './directives/clickOutsideDirective';
import GlobalMixins from './mixins/globalMixins';

const routes = [
  // { path: '/', component: HomePage },
];

const router = createRouter({
  history: createWebHistory(),
  routes: routes
});

const app = createApp(App);

app.directive('click-outside', ClickOutsideDirective);
app.mixin(GlobalMixins);

app.use(router);

app.mount('#fullcalendar-app');
