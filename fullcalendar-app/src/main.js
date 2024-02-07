import { createApp } from 'vue';
import App from './App.vue';
import { createRouter, createWebHistory } from 'vue-router';

// import SessionForm from './components/SessionForm.vue';

import ClickOutsideDirective from './directives/clickOutsideDirective';
import GlobalMixins from './mixins/globalMixins';

const routes = [
  // {
  //   path: '/session/:id?', // :id? means id is optional (for create or edit)
  //   name: 'SessionForm',
  //   component: SessionForm,
  //   props: true // Allows us to pass the id as a prop to the component
  // }
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes: routes
});

const app = createApp(App);

app.directive('click-outside', ClickOutsideDirective);
app.mixin(GlobalMixins);
app.use(router);

app.mount('#fullcalendar-app');
