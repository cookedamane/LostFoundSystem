import { HttpClient, HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { of, catchError, tap, throwError, timeout } from 'rxjs';
import { environment } from '../../environments/environment';

export interface User { id: number; name: string; email: string; student_id: string | null; phone: string | null; }
export interface Category { id: number; name: string; }
export interface Item {
  id: number; type: 'lost' | 'found'; name: string; description: string; category_id: number; category: string;
  location: string; date_event: string; status: string; photo_url: string | null; mine: boolean; admin_note: string | null; created_at: string;
}
export interface MyClaim { id: number; status: string; admin_remarks: string | null; }
export interface Claim extends MyClaim {
  proof_of_ownership: string; created_at: string; reviewed_at: string | null;
  item_id: number; item_name: string; item_status: string; item_location: string;
}

const TOKEN_KEY = 'lf_token';
const USER_KEY = 'lf_user';
export const getToken = () => localStorage.getItem(TOKEN_KEY);
export const clearSession = () => { localStorage.removeItem(TOKEN_KEY); localStorage.removeItem(USER_KEY); };

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);
  private base = environment.apiUrl;

  get user(): User | null { return JSON.parse(localStorage.getItem(USER_KEY) || 'null'); }
  setUser(u: User) { localStorage.setItem(USER_KEY, JSON.stringify(u)); }
  private save(r: { token: string; user: User }) { localStorage.setItem(TOKEN_KEY, r.token); this.setUser(r.user); }

  login(email: string, password: string) {
    return this.http.post<{ token: string; user: User }>(`${this.base}/login`, { email, password }).pipe(timeout({ first: 12000 }), tap(r => this.save(r)));
  }
  register(body: Record<string, string>) {
    return this.http.post<{ token: string; user: User }>(`${this.base}/register`, body).pipe(timeout({ first: 12000 }), tap(r => this.save(r)));
  }
  logout() {
    return this.http.post(`${this.base}/logout`, {}).pipe(catchError(() => of(null)), tap(() => clearSession()));
  }
  categories() { return this.http.get<{ categories: Category[] }>(`${this.base}/categories`); }
  items(params: Record<string, string>) { return this.http.get<{ items: Item[] }>(`${this.base}/items`, { params }); }
  myItems() { return this.http.get<{ items: Item[] }>(`${this.base}/my/items`); }
  item(id: number) { return this.http.get<{ item: Item; my_claim: MyClaim | null }>(`${this.base}/items/${id}`); }
  reportLost(body: Record<string, unknown>) { return this.http.post<{ message: string }>(`${this.base}/items`, body); }
  claim(body: Record<string, unknown>) { return this.http.post<{ message: string }>(`${this.base}/claims`, body); }
  myClaims() { return this.http.get<{ claims: Claim[] }>(`${this.base}/my/claims`); }
  cancelClaim(id: number) { return this.http.delete(`${this.base}/claims/${id}`); }
  updateProfile(body: Record<string, string>) {
    return this.http.put<{ user: User }>(`${this.base}/me`, body).pipe(tap(r => this.setUser(r.user)));
  }
  changePassword(body: Record<string, string>) { return this.http.put(`${this.base}/me/password`, body); }
}

// Adds the sign-in token to API calls and returns the user to the login page when it expires.
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const token = getToken();
  const call = token && req.url.startsWith(environment.apiUrl) ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : req;
  return next(call).pipe(
    catchError((e: HttpErrorResponse) => {
      if (e.status === 401 && token && !req.url.endsWith('/login')) { clearSession(); router.navigateByUrl('/login'); }
      return throwError(() => e);
    }),
  );
};

export const authGuard: CanActivateFn = () => (getToken() ? true : inject(Router).parseUrl('/login'));
export const guestGuard: CanActivateFn = () => (getToken() ? inject(Router).parseUrl('/tabs') : true);
