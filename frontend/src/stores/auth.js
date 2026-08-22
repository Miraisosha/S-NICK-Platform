import { defineStore } from 'pinia';
import * as authApi from '../api/auth';
import { ApiError } from '../api/client';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    initialized: false,
  }),
  getters: {
    isAuthenticated: (state) => state.user !== null,
  },
  actions: {
    async fetchCurrentUser() {
      try {
        this.user = await authApi.me();
      } catch (e) {
        if (!(e instanceof ApiError) || e.status !== 401) {
          throw e;
        }
        this.user = null;
      } finally {
        this.initialized = true;
      }
    },
    async login(email, password) {
      this.user = await authApi.login(email, password);
    },
    async logout() {
      await authApi.logout();
      this.user = null;
    },
  },
});
