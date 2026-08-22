import { defineStore } from 'pinia';
import * as adminAuthApi from '../api/adminAuth';
import { ApiError } from '../api/client';

export const useAdminAuthStore = defineStore('adminAuth', {
  state: () => ({
    admin: null,
    initialized: false,
  }),
  getters: {
    isAuthenticated: (state) => state.admin !== null,
  },
  actions: {
    async fetchCurrentAdmin() {
      try {
        this.admin = await adminAuthApi.me();
      } catch (e) {
        if (!(e instanceof ApiError) || e.status !== 401) {
          throw e;
        }
        this.admin = null;
      } finally {
        this.initialized = true;
      }
    },
    async login(email, password) {
      this.admin = await adminAuthApi.login(email, password);
    },
    async logout() {
      await adminAuthApi.logout();
      this.admin = null;
    },
  },
});
