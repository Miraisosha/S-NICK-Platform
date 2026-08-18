import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import { createApp } from 'vue';
import App from './App.vue';

const mountElement = document.querySelector('[data-vue-app]');

if (mountElement) {
  createApp(App).mount(mountElement);
}
