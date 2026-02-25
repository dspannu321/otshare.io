/**
 * Central API client for otshare.io v1.
 * All requests use JSON where applicable and consistent error handling.
 */

const JSON_HEADERS = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

/**
 * @param {string} apiBase - e.g. /api/v1
 * @returns {Promise<{ id: string, pickup_code: string, expires_at: string, upload_url: string }>}
 */
export async function createShare(apiBase) {
  const res = await fetch(`${apiBase}/shares`, {
    method: 'POST',
    headers: JSON_HEADERS,
    body: JSON.stringify({}),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
    throw new Error(msg || `Failed to create share (${res.status}).`);
  }
  return data;
}

/**
 * @param {string} uploadUrl - Full URL from createShare
 * @param {{ ciphertext: Blob, crypto_meta: object, kdf: object, original_name: string, mime: string }} payload
 */
export async function uploadShare(uploadUrl, payload) {
  const form = new FormData();
  form.append('crypto_meta', JSON.stringify(payload.crypto_meta));
  form.append('kdf', JSON.stringify(payload.kdf));
  form.append('original_name', payload.original_name ?? '');
  form.append('mime', payload.mime ?? 'application/octet-stream');
  form.append('ciphertext', payload.ciphertext, 'ciphertext.bin');
  const res = await fetch(uploadUrl, { method: 'POST', body: form });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
    throw new Error(msg || 'Upload failed.');
  }
  return data;
}

/**
 * @param {string} apiBase
 * @param {string} pickupCode - e.g. K7P4-839217
 * @returns {Promise<{ download_token: string, expires_at: string, crypto_meta: object, kdf: object, original_name: string, mime: string, size_bytes: number }>}
 */
export async function redeem(apiBase, pickupCode) {
  const res = await fetch(`${apiBase}/redeem`, {
    method: 'POST',
    headers: JSON_HEADERS,
    body: JSON.stringify({ pickup_code: pickupCode.replace(/\s/g, '').trim() }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors?.pickup_code?.[0] ?? data.message ?? 'Invalid or expired code.';
    throw new Error(msg);
  }
  return data;
}

/**
 * @param {string} apiBase
 * @param {string} token - download_token from redeem
 * @returns {Promise<ArrayBuffer>} ciphertext
 */
export async function download(apiBase, token) {
  const res = await fetch(`${apiBase}/download?token=${encodeURIComponent(token)}`);
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.message ?? 'Download failed.');
  }
  return res.arrayBuffer();
}

/**
 * @param {string} apiBase
 * @param {string} token
 * @param {boolean} success
 * @returns {Promise<{ message: string, attempts_left?: number, expired?: boolean }>}
 */
export async function confirmDownload(apiBase, token, success) {
  const res = await fetch(`${apiBase}/download/confirm`, {
    method: 'POST',
    headers: JSON_HEADERS,
    body: JSON.stringify({ token, success }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message ?? 'Confirm failed.');
  return data;
}
