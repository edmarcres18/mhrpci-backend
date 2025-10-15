import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface SiteSettings {
  site_name: string;
  site_logo: string | null;
}

export function useSiteSettings() {
  const page = usePage();
  
  const siteSettings = computed<SiteSettings>(() => {
    const settings = page.props.siteSettings as SiteSettings | undefined;
    return {
      site_name: settings?.site_name || 'Laravel Starter Kit',
      site_logo: settings?.site_logo || null,
    };
  });

  return {
    siteSettings,
  };
}
