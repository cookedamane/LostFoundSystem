import { Injectable, inject } from '@angular/core';
import { ToastController } from '@ionic/angular/standalone';

export function errorMessage(e: any): string {
  if (e?.status === 0 || e?.name === 'TimeoutError') { return 'The server did not respond. Keep the API window open, confirm the phone is on the laptop hotspot, and try again.'; }
  const errs = e?.error?.errors;
  if (errs && Object.keys(errs).length) { return String(Object.values(errs)[0]); }
  return e?.error?.message || 'Something went wrong. Please try again.';
}
export const fieldErrors = (e: any): Record<string, string> => e?.error?.errors || {};
export const label = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);
export const niceDate = (d: string) => new Date(d.replace(' ', 'T')).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

@Injectable({ providedIn: 'root' })
export class Notice {
  private toasts = inject(ToastController);
  async show(message: string, color: 'success' | 'danger' | 'dark' = 'dark') {
    const t = await this.toasts.create({ message, color, duration: 3000, position: 'top' });
    await t.present();
  }
}
