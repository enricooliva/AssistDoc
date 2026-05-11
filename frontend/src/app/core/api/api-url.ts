import { environment } from '../../../environments/environment';

export function apiUrl(path: string): string {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${environment.apiBaseUrl}${normalizedPath}`;
}
