import { ref, onMounted } from 'vue';
import axios from 'axios';

export interface SiteSettings {
  site_name: string;
  site_logo: string | null;
}

const siteSettings = ref<SiteSettings>({
  site_name: 'Laravel Starter Kit',
  site_logo: null,
});

const isLoading = ref(false);
const isLoaded = ref(false);

export function useSiteSettings() {
  const fetchSiteSettings = async () => {
    if (isLoaded.value) {
      return siteSettings.value;
    }

    isLoading.value = true;
    try {
      const response = await axios.get('/api/site-settings');
      if (response.data.success && response.data.data) {
        siteSettings.value = {
          site_name: response.data.data.site_name || 'Laravel Starter Kit',
          site_logo: response.data.data.site_logo || null,
        };
        isLoaded.value = true;
      }
    } catch (error) {
      console.error('Failed to fetch site settings:', error);
      // Keep default values on error
      siteSettings.value = {
        site_name: 'Laravel Starter Kit',
        site_logo: null,
      };
    } finally {
      isLoading.value = false;
    }

    return siteSettings.value;
  };

  // Auto-fetch on mount if not already loaded
  onMounted(() => {
    if (!isLoaded.value) {
      fetchSiteSettings();
    }
  });

  return {
    siteSettings,
    isLoading,
    fetchSiteSettings,
  };
}
