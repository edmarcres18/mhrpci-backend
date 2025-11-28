<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { reactive, ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Toast from '@/pages/SiteSettings/Toast.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSiteSettings } from '@/composables/useSiteSettings';
import { LoaderCircle } from 'lucide-vue-next';

type ToastType = 'success' | 'error';

const form = reactive({
  department: '',
  phone_number: '',
  person_in_charge: '',
  position: '',
  extension: '',
  website: '',
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });
const { siteSettings } = useSiteSettings();
const showPurposeModal = ref(false);
function closeModal() { showPurposeModal.value = false; }
function handleKeydown(e: KeyboardEvent) { if (e.key === 'Escape') showPurposeModal.value = false; }
onMounted(() => { window.addEventListener('keydown', handleKeydown); });
onBeforeUnmount(() => { window.removeEventListener('keydown', handleKeydown); });

const modalRef = ref<HTMLElement | null>(null);
const closeBtnRef = ref<HTMLButtonElement | null>(null);
function handleModalKeydown(e: KeyboardEvent) {
  if (e.key !== 'Tab') return;
  const root = modalRef.value; if (!root) return;
  const focusables = Array.from(root.querySelectorAll<HTMLElement>('[href],button,textarea,input,select,[tabindex]:not([tabindex="-1"])'))
    .filter(el => !el.hasAttribute('disabled') && el.tabIndex !== -1);
  if (focusables.length === 0) return;
  const first = focusables[0]; const last = focusables[focusables.length - 1];
  const active = document.activeElement as HTMLElement | null;
  if (e.shiftKey) {
    if (active === first || !root.contains(active)) { e.preventDefault(); last.focus(); }
  } else {
    if (active === last || !root.contains(active)) { e.preventDefault(); first.focus(); }
  }
}
watch(showPurposeModal, async (v) => { if (v) { await nextTick(); closeBtnRef.value?.focus(); } });

function validateLocal(): boolean {
  const map: Record<string, string> = {};
  if (!form.department?.trim()) map.department = 'Department is required';
  if (!form.phone_number?.trim()) map.phone_number = 'Phone number is required';
  else if (!/^09\d{9}$/.test(form.phone_number)) map.phone_number = 'Phone must start with 09 and be exactly 11 digits';
  if (!form.person_in_charge?.trim()) map.person_in_charge = 'Person in charge is required';
  if (!form.position?.trim()) map.position = 'Position is required';
  errors.value = map;
  return Object.keys(map).length === 0;
}

async function submit() {
  try {
    processing.value = true;
    errors.value = {};
    if (!validateLocal()) {
      toast.type = 'error'; toast.message = 'Please fix validation errors'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      return;
    }
    const res = await axios.post('/public/company-phones', { ...form });
    if (res.data?.success) {
      toast.type = 'success'; toast.message = 'Phone submitted successfully'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      Object.assign(form, { department: '', phone_number: '', person_in_charge: '', position: '', extension: '', website: '' });
    }
  } catch (e: any) {
    const errs = e?.response?.data?.errors;
    if (Array.isArray(errs)) {
      errors.value.general = errs[0] || 'Submission failed';
    } else if (typeof e?.message === 'string') {
      errors.value.general = e.message;
    } else {
      errors.value.general = 'Submission failed';
    }
    toast.type = 'error'; toast.message = errors.value.general; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
  } finally { processing.value = false; }
}


</script>

<template>
  <Head title="🎄 Submit Company Phone" />
  <div class="public-form">
    <div class="christmas-bg" aria-hidden="true">
      <ul class="lights">
        <li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li>
      </ul>
      <div class="snow">
        <i v-for="i in 60" :key="i" :style="{ left: (i * 1.5) % 100 + '%', animationDuration: (4 + (i % 6)) + 's', animationDelay: (i % 5) + 's' }"></i>
      </div>
    </div>
    <transition name="fade">
      <div v-if="showPurposeModal" class="modal-overlay" @click="closeModal">
        <transition name="zoom">
          <div v-if="showPurposeModal" ref="modalRef" class="modal" role="dialog" aria-modal="true" aria-labelledby="purpose-title" aria-describedby="purpose-desc" @keydown="handleModalKeydown" @click.stop>
            <button ref="closeBtnRef" type="button" class="modal-close" aria-label="Close" @click="closeModal">×</button>
            <h3 id="purpose-title" class="modal-title">Purpose of This Form</h3>
            <p id="purpose-desc" class="modal-text">This form helps keep our inventory of company phone numbers current and secure. Share your department’s details to support better resource management and communication.</p>
          </div>
        </transition>
      </div>
    </transition>
    <div class="wave">
      <svg class="wave-svg" viewBox="0 0 1440 150" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <linearGradient id="g1" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#DC2626" />
            <stop offset="100%" stop-color="#059669" />
          </linearGradient>
        </defs>
        <path d="M0,40 C240,140 480,0 720,60 C960,120 1200,40 1440,90 L1440,0 L0,0 Z" fill="url(#g1)" />
      </svg>
    </div>

    <div class="container">
      <div class="brand">
        <div class="logo">
          <img v-if="siteSettings.site_logo" :src="siteSettings.site_logo" :alt="siteSettings.site_name" />
          <AppLogoIcon v-else className="logo-icon" />
        </div>
        <div class="brand-name">MHRPCI FORM</div>
      </div>

      <h1 class="title">Enter Company Phone Number Used</h1>
      <p class="subtitle">Provide your department’s official company phone number currently in use</p>

      

      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <form class="form" @submit.prevent="submit" novalidate>
        <div class="form-grid">
        <div class="field">
          <Label class="label" for="department">Department <span class="req">*</span></Label>
          <Input v-model="form.department" class="input" id="department" name="department" placeholder="e.g. IT Department" maxlength="255" required autocomplete="organization" :aria-invalid="!!errors.department" :aria-describedby="errors.department ? 'error-department' : undefined" />
          <p v-if="errors.department" class="error" id="error-department" role="alert" aria-live="polite">{{ errors.department }}</p>
        </div>
        <div class="field">
          <Label class="label" for="phone_number">Phone Number <span class="req">*</span></Label>
          <Input v-model="form.phone_number" class="input" id="phone_number" name="phone_number" placeholder="e.g. 09123456789" maxlength="11" required inputmode="numeric" autocomplete="tel" pattern="^09\\d{9}$" :aria-invalid="!!errors.phone_number" :aria-describedby="errors.phone_number ? 'error-phone_number' : undefined" />
          <p v-if="errors.phone_number" class="error" id="error-phone_number" role="alert" aria-live="polite">{{ errors.phone_number }}</p>
        </div>
        <div class="field">
          <Label class="label" for="person_in_charge">Person In Charge <span class="req">*</span></Label>
          <Input v-model="form.person_in_charge" class="input" id="person_in_charge" name="person_in_charge" placeholder="e.g. John Doe" maxlength="255" required autocomplete="name" :aria-invalid="!!errors.person_in_charge" :aria-describedby="errors.person_in_charge ? 'error-person_in_charge' : undefined" />
          <p v-if="errors.person_in_charge" class="error" id="error-person_in_charge" role="alert" aria-live="polite">{{ errors.person_in_charge }}</p>
        </div>
        <div class="field">
          <Label class="label" for="position">Position <span class="req">*</span></Label>
          <Input v-model="form.position" class="input" id="position" name="position" placeholder="e.g. IT Manager" maxlength="255" required autocomplete="organization-title" :aria-invalid="!!errors.position" :aria-describedby="errors.position ? 'error-position' : undefined" />
          <p v-if="errors.position" class="error" id="error-position" role="alert" aria-live="polite">{{ errors.position }}</p>
        </div>
        <div class="field">
          <Label class="label" for="extension">Extension</Label>
          <Input v-model="form.extension" class="input" id="extension" name="extension" placeholder="e.g. 123" maxlength="20" inputmode="numeric" pattern="^\\d+$" />
        </div>
        </div>

        <input v-model="form.website" name="website" type="text" class="honeypot" aria-hidden="true" tabindex="-1" autocomplete="off" />

        <div class="actions">
          <Button class="cta" :disabled="processing" type="submit"><LoaderCircle v-if="processing" class="mr-2 h-4 w-4 animate-spin" />{{ processing ? 'Submitting' : 'Submit' }}</Button>
        </div>
        <div v-if="errors.general" class="error">{{ errors.general }}</div>
        <div class="email-link">
          <Link href="/public/emails" class="link" prefetch>Submit a Company Email? Click here</Link>
        </div>
      </form>
    </div>
    <Button class="fab" type="button" aria-label="Show purpose" @click="showPurposeModal = true">i</Button>
  </div>
  </template>

<style scoped>
.public-form { min-height: 100svh; background: radial-gradient(120% 80% at 50% -10%, rgba(220,38,38,0.25) 0%, rgba(5,150,105,0.25) 100%), linear-gradient(180deg, #0b1d32 0%, #0e233d 60%, #0b1d32 100%); color: #f8fafc; position: relative; overflow: hidden; --accent: #dc2626; --accent2: #059669; }
.christmas-bg { position: absolute; inset: 0; pointer-events: none; }
.lights { position: absolute; top: 0; left: 0; right: 0; height: 64px; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; list-style: none; }
.lights li { width: 16px; height: 16px; border-radius: 50%; box-shadow: 0 0 12px currentColor; animation: glow 2.4s ease-in-out infinite; will-change: opacity, transform; }
.lights li:nth-child(4n+1) { color: #ef4444; }
.lights li:nth-child(4n+2) { color: #22c55e; }
.lights li:nth-child(4n+3) { color: #f59e0b; }
.lights li:nth-child(4n+4) { color: #0ea5e9; }
@keyframes glow { 0%,100% { opacity: 0.5; transform: translateY(0); } 50% { opacity: 1; transform: translateY(2px); } }
.snow { position: absolute; inset: 0; }
.snow i { position: absolute; top: -10px; width: 6px; height: 6px; background: #fff; border-radius: 50%; filter: drop-shadow(0 0 4px rgba(255,255,255,0.6)); animation-name: fall, drift; animation-timing-function: linear; animation-iteration-count: infinite; will-change: transform; }
@keyframes fall { 0% { transform: translateY(-10px); } 100% { transform: translateY(110vh); } }
@keyframes drift { 0% { margin-left: 0; } 50% { margin-left: 8px; } 100% { margin-left: 0; } }
.wave { height: 64px; background: transparent; }
.wave-svg { width: 100%; height: 100%; display: block; }
.container { max-width: 900px; margin: 0 auto; padding: 20px; min-height: calc(100svh - 64px); display: grid; align-content: center; gap: 16px; position: relative; z-index: 1; }
.brand { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 10px; }
.logo img { max-height: 84px; }
.logo-icon { height: 84px; width: auto; }
.brand-name { font-family: var(--font-sans, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Inter); font-size: clamp(20px, 4vw, 28px); font-weight: 800; color: #fff; letter-spacing: 0.03em; text-shadow: 0 2px 12px rgba(255,255,255,0.15); }
.title { font-family: var(--font-sans, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Inter); font-size: clamp(22px, 4vw, 32px); font-weight: 800; color: #fff; margin-top: 6px; margin-bottom: 12px; text-shadow: 0 2px 12px rgba(255,255,255,0.15); }
.subtitle { font-size: clamp(12px, 2.4vw, 14px); color: #d1d5db; margin-top: -4px; margin-bottom: 16px; }
.form { display: flex; flex-direction: column; gap: 16px; background: rgba(255,255,255,0.95); border: 1px solid rgba(255,255,255,0.75); border-radius: 22px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.30); backdrop-filter: saturate(160%) blur(6px); }
.form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
@media (min-width: 680px) { .form-grid { grid-template-columns: 1fr 1fr; } }
.label { font-size: clamp(13px, 1.6vw, 15px); color: #1F2937; font-weight: 700; }
.field { display: flex; flex-direction: column; gap: 6px; }
.req { color: #ef4444; }
.input { height: 46px; border-radius: 14px; border: 1px solid #E5E7EB; background: #FFFFFF; box-shadow: inset 0 0 0 1px #E5E7EB; font-size: 15px; padding: 10px 12px; }
.input { color: #000000; caret-color: var(--accent); }
.input::placeholder { color: #6B7280; }
.input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.25), inset 0 0 0 1px var(--accent); }
.actions { margin-top: 12px; text-align: center; }
.cta { width: 240px; height: 50px; border-radius: 14px; background-image: linear-gradient(90deg, var(--accent), var(--accent2)); color: #fff; font-weight: 800; font-size: 15px; letter-spacing: 0.08em; text-transform: uppercase; box-shadow: 0 14px 28px rgba(220,38,38,0.35); }
.cta:hover { filter: brightness(1.05); transform: translateY(-1px); }
.cta:focus { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.3); }
.cta:disabled { opacity: .65; cursor: not-allowed; }
.alt-cta { margin-left: 12px; height: 48px; border-radius: 10px; background: #F5F3FF; color: #4C1D95; font-weight: 600; }
.alt-cta:hover { background: #EEE8FF; }
.icon { width: 16px; height: 16px; margin-right: 8px; }
.error { font-size: 13px; color: #DC2626; margin-top: 4px; }
.honeypot { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; }
.info-box { background: linear-gradient(135deg, rgba(220,38,38,0.12) 0%, rgba(5,150,105,0.12) 100%); border: 1px solid rgba(220,38,38,0.35); border-radius: 16px; padding: 20px; margin-bottom: 24px; }
.info-title { font-size: 16px; font-weight: 700; color: #fff; margin: 0 0 12px 0; }
.info-text { font-size: 14px; color: #e5e7eb; line-height: 1.6; margin: 0; }
.email-link { margin-top: 20px; text-align: center; }
.link { color: #fca5a5; font-size: 14px; text-decoration: none; font-weight: 600; transition: color 0.2s; }
.link:hover { color: #fecaca; text-decoration: underline; }
@media (max-width: 768px) { .container { padding: 12px; } .cta { width: 100%; } }
@media (max-width: 420px), (max-height: 680px) { .info-box { display: none; } .wave { height: 48px; } .title { margin-bottom: 8px; } .subtitle { margin-bottom: 12px; } .input { height: 38px; } }
@media (prefers-reduced-motion: reduce) { .cta { transition: none; } }

.wave { height: 64px; }
.container { padding: 20px; min-height: calc(100svh - 64px); display: grid; align-content: center; gap: 18px; }
.title { font-size: clamp(26px, 4.5vw, 36px); margin-top: 6px; margin-bottom: 12px; }
.subtitle { font-size: clamp(13px, 2.6vw, 16px); margin-top: -4px; margin-bottom: 18px; }
.form { gap: 16px; padding: 24px; }
.form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
@media (min-width: 680px) { .form-grid { grid-template-columns: 1fr 1fr; } }
.input { height: 46px; }
.cta { height: 50px; }

.fab { position: fixed; bottom: 20px; right: 20px; width: 56px; height: 56px; border-radius: 9999px; background-image: linear-gradient(90deg, var(--accent), var(--accent2)); color: #fff; font-weight: 800; box-shadow: 0 12px 24px rgba(0,0,0,0.35); display: inline-flex; align-items: center; justify-content: center; z-index: 1001; }
.fab:hover { filter: brightness(1.08); transform: translateY(-1px); }
.fab:focus { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.3); }

.modal-overlay { position: fixed; inset: 0; background: rgba(17, 24, 39, 0.55); backdrop-filter: blur(2px); z-index: 1000; display: grid; place-items: center; padding: 16px; }
.modal { width: clamp(300px, 92vw, 600px); max-width: 600px; border-radius: 16px; background: #ffffff; color: #111827; border: 1px solid rgba(255,255,255,0.6); box-shadow: 0 20px 40px rgba(0,0,0,0.35); position: relative; padding: 20px; }
.modal:focus { outline: none; }
.modal-title { font-size: clamp(18px, 3.6vw, 22px); font-weight: 800; color: #111827; }
.modal-text { margin-top: 8px; font-size: clamp(13px, 2.8vw, 14px); color: #374151; line-height: 1.6; }
.modal-close { position: absolute; top: 10px; right: 10px; width: 36px; height: 36px; border-radius: 9999px; background: transparent; color: #111827; border: 1px solid rgba(17,24,39,0.12); display: inline-flex; align-items: center; justify-content: center; }
.modal-close:hover { background: rgba(17,24,39,0.06); }
.modal-close:focus { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.3); }

.fade-enter-active, .fade-leave-active { transition: opacity .2s ease-in-out; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.zoom-enter-active, .zoom-leave-active { transition: opacity .2s ease-in-out, transform .2s ease-in-out, filter .2s ease-in-out; }
.zoom-enter-from, .zoom-leave-to { opacity: 0; transform: translateY(12px) scale(.98); filter: blur(.5px); }
@media (prefers-reduced-motion: reduce) { .lights li, .snow i { animation: none !important; } }
</style>
