/**
 * Central API client for otshare.io v1 (free tier — plaintext storage).
 */

/**
 * @param {string} apiBase - e.g. /api/v1
 * @param {{
 *   file: File | Blob,
 *   expiresAtIso: string,
 *   maxDownloads: number,
 *   fileName?: string,
 * }} params
 */
export async function createShareWithFile(apiBase, params) {
  const form = new FormData();
  const name = params.fileName ?? (params.file instanceof File ? params.file.name : 'upload.bin');
  form.append('file', params.file, name);
  form.append('expires_at', params.expiresAtIso);
  form.append('max_downloads', String(params.maxDownloads));

  const res = await fetch(`${apiBase}/share`, {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: form,
  });
  if (res.status === 413) {
    throw new Error('Upload rejected: file exceeds server upload limit. Try a smaller file.');
  }
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
    throw new Error(msg || `Failed to create share (${res.status}).`);
  }
  return data;
}

/**
 * @param {string} apiBase - e.g. /api/v1
 * @param {{
 *   text: string,
 *   expiresAtIso: string,
 *   maxDownloads: number,
 * }} params
 */
export async function createShareWithText(apiBase, params) {
  const res = await fetch(`${apiBase}/share-text`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({
      text: params.text,
      expires_at: params.expiresAtIso,
      max_downloads: params.maxDownloads,
    }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
    throw new Error(msg || `Failed to create text share (${res.status}).`);
  }
  return data;
}

/** @param {string} combined - 10 chars: 4 alnum + 6 digits (no dash) */
export function formatPickupCodeForApi(combined) {
  const c = (combined || '').replace(/\s/g, '').replace(/-/g, '').toUpperCase();
  if (c.length < 10) return '';
  return `${c.slice(0, 4)}-${c.slice(4, 10)}`;
}

/**
 * @param {string} apiBase - e.g. /api/v1
 * @param {string} pickupCode - formatted XXXX-XXXXXX or 10-char combined
 */
export async function redeemPickupCode(apiBase, pickupCode) {
  const formatted =
    pickupCode.includes('-') ? pickupCode.replace(/\s/g, '') : formatPickupCodeForApi(pickupCode);
  const res = await fetch(`${apiBase}/redeem`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ pickup_code: formatted }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
    throw new Error(msg || `Could not redeem code (${res.status}).`);
  }
  return data;
}

/**
 * @param {string} apiBase
 * @param {string} token - plain download_token from redeem
 */
export async function downloadShareBlob(apiBase, token) {
  const url = `${apiBase}/download?token=${encodeURIComponent(token)}`;
  const res = await fetch(url, { headers: { Accept: '*/*' } });
  const ct = res.headers.get('Content-Type') || '';
  if (!res.ok) {
    const data = ct.includes('json') ? await res.json().catch(() => ({})) : {};
    const msg = data.message || `Download failed (${res.status}).`;
    throw new Error(msg);
  }
  return res.blob();
}

/**
 * @param {string} apiBase
 * @param {string} token
 * @param {boolean} success - true after plaintext file saved; false only for legacy decrypt failure paths
 */
export async function confirmDownload(apiBase, token, success) {
  const res = await fetch(`${apiBase}/download/confirm`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ token, success }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || `Confirm failed (${res.status}).`);
  }
  return data;
}
