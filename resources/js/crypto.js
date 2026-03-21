/**
 * Client-side crypto for otshare.io (SOC2 / security-hardened)
 * - AES-256-GCM for file encryption and key wrapping
 * - Argon2id for key derivation (new shares); PBKDF2-SHA256 fallback (legacy)
 * - Constant-time comparisons where applicable; no sensitive data in errors
 */

if (typeof window !== 'undefined') {
  window.Module = window.Module || {};
  window.Module.locateFile = () => '/argon2.wasm';
}
import argon2 from 'argon2-browser';

const KDF_PBKDF2 = {
  type: 'pbkdf2',
  iterations: 600_000,
  hash: 'SHA-256',
  saltLen: 16,
};

const KDF_ARGON2ID = {
  type: 'argon2id',
  memory: 65536,   // 64 MiB (in KiB)
  timeCost: 3,
  parallelism: 4,
  hashLen: 32,
  saltLen: 16,
};

const FILE_KEY_LEN = 32;
const NONCE_LEN = 12;
const TAG_LEN = 16;

/**
 * Derive wrap key using PBKDF2-SHA256 (legacy / backward compatibility).
 * @param {string} passcode
 * @param {Uint8Array} salt
 * @param {{ iterations?: number, hash?: string }} [opts]
 * @returns {Promise<CryptoKey>}
 */
export async function deriveWrapKeyPBKDF2(passcode, salt, opts = {}) {
  const iterations = opts.iterations ?? KDF_PBKDF2.iterations;
  const hash = opts.hash ?? KDF_PBKDF2.hash;
  const enc = new TextEncoder();
  const keyMaterial = await crypto.subtle.importKey(
    'raw',
    enc.encode(passcode),
    'PBKDF2',
    false,
    ['deriveBits', 'deriveKey']
  );
  return crypto.subtle.deriveKey(
    {
      name: 'PBKDF2',
      salt,
      iterations,
      hash,
    },
    keyMaterial,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt']
  );
}

/**
 * Derive wrap key using Argon2id (recommended for new shares).
 * @param {string} passcode
 * @param {Uint8Array} salt
 * @param {{ memory?: number, timeCost?: number, parallelism?: number, hashLen?: number }} [opts]
 * @returns {Promise<CryptoKey>}
 */
export async function deriveWrapKeyArgon2id(passcode, salt, opts = {}) {
  const memory = opts.memory ?? KDF_ARGON2ID.memory;
  const timeCost = opts.timeCost ?? KDF_ARGON2ID.timeCost;
  const parallelism = opts.parallelism ?? KDF_ARGON2ID.parallelism;
  const hashLen = opts.hashLen ?? KDF_ARGON2ID.hashLen;

  const result = await argon2.hash({
    pass: passcode,
    salt: salt,
    time: timeCost,
    mem: memory,
    parallelism,
    hashLen,
    type: argon2.ArgonType.Argon2id,
  });

  return crypto.subtle.importKey(
    'raw',
    result.hash,
    { name: 'AES-GCM' },
    false,
    ['encrypt', 'decrypt']
  );
}

/**
 * Derive wrap key based on kdf descriptor (argon2id or pbkdf2).
 * @param {string} passcode
 * @param {Uint8Array} salt
 * @param {{ type?: string, memory?: number, timeCost?: number, parallelism?: number, iterations?: number, hash?: string }} [kdf]
 * @returns {Promise<CryptoKey>}
 */
export async function deriveWrapKey(passcode, salt, kdf = null) {
  const type = (kdf && kdf.type) ? String(kdf.type).toLowerCase() : 'pbkdf2';
  if (type === 'argon2id') {
    return deriveWrapKeyArgon2id(passcode, salt, {
      memory: kdf.memory,
      timeCost: kdf.timeCost,
      parallelism: kdf.parallelism,
      hashLen: kdf.hashLen ?? KDF_ARGON2ID.hashLen,
    });
  }
  return deriveWrapKeyPBKDF2(passcode, salt, {
    iterations: kdf?.iterations ?? KDF_PBKDF2.iterations,
    hash: kdf?.hash ?? KDF_PBKDF2.hash,
  });
}

function randomBytes(length) {
  const arr = new Uint8Array(length);
  crypto.getRandomValues(arr);
  return arr;
}

function uint8ToBase64(u8) {
  return btoa(String.fromCharCode.apply(null, u8));
}

function base64ToUint8(base64) {
  const binary = atob(base64);
  const u8 = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) u8[i] = binary.charCodeAt(i);
  return u8;
}

function arrayBufferToBase64(buffer) {
  return btoa(String.fromCharCode.apply(null, new Uint8Array(buffer)));
}

function base64ToArrayBuffer(base64) {
  const binary = atob(base64);
  const buffer = new ArrayBuffer(binary.length);
  const view = new Uint8Array(buffer);
  for (let i = 0; i < binary.length; i++) view[i] = binary.charCodeAt(i);
  return buffer;
}

/**
 * Encrypt file with AES-256-GCM; wrap file key with key derived from passcode (Argon2id).
 * @param {ArrayBuffer} fileData
 * @param {string} passcode
 * @returns {Promise<{ ciphertext: ArrayBuffer, crypto_meta: object, kdf: object }>}
 */
export async function encryptFile(fileData, passcode) {
  const fileKey = randomBytes(FILE_KEY_LEN);
  const fileNonce = randomBytes(NONCE_LEN);
  const salt = randomBytes(KDF_ARGON2ID.saltLen);

  const fileKeyCrypto = await crypto.subtle.importKey(
    'raw',
    fileKey,
    { name: 'AES-GCM' },
    false,
    ['encrypt']
  );

  const ciphertext = await crypto.subtle.encrypt(
    {
      name: 'AES-GCM',
      iv: fileNonce,
      tagLength: TAG_LEN * 8,
    },
    fileKeyCrypto,
    fileData
  );

  const wrapKey = await deriveWrapKeyArgon2id(passcode, salt);
  const wrapNonce = randomBytes(NONCE_LEN);
  const wrappedFileKey = await crypto.subtle.encrypt(
    {
      name: 'AES-GCM',
      iv: wrapNonce,
      tagLength: TAG_LEN * 8,
    },
    wrapKey,
    fileKey
  );

  const crypto_meta = {
    wrapped_file_key: arrayBufferToBase64(wrappedFileKey),
    file_nonce: uint8ToBase64(fileNonce),
    wrap_nonce: uint8ToBase64(wrapNonce),
    salt: uint8ToBase64(salt),
  };

  const kdf = {
    type: KDF_ARGON2ID.type,
    memory: KDF_ARGON2ID.memory,
    timeCost: KDF_ARGON2ID.timeCost,
    parallelism: KDF_ARGON2ID.parallelism,
    hashLen: KDF_ARGON2ID.hashLen,
  };

  return {
    ciphertext,
    crypto_meta,
    kdf,
  };
}

/**
 * Decrypt ciphertext using passcode and crypto_meta. Supports Argon2id and PBKDF2 (kdf).
 * @param {ArrayBuffer} ciphertext
 * @param {string} passcode
 * @param {object} crypto_meta - { wrapped_file_key, file_nonce, wrap_nonce, salt }
 * @param {object} [kdf] - { type: 'argon2id'|'pbkdf2', ... }
 * @returns {Promise<ArrayBuffer>}
 */
export async function decryptFile(ciphertext, passcode, crypto_meta, kdf = null) {
  const salt = base64ToUint8(crypto_meta.salt);
  const wrapKey = await deriveWrapKey(passcode, salt, kdf || { type: 'pbkdf2' });
  const wrappedFileKey = base64ToArrayBuffer(crypto_meta.wrapped_file_key);
  const wrapNonce = base64ToUint8(crypto_meta.wrap_nonce);

  const fileKeyBuf = await crypto.subtle.decrypt(
    {
      name: 'AES-GCM',
      iv: wrapNonce,
      tagLength: TAG_LEN * 8,
    },
    wrapKey,
    wrappedFileKey
  );

  const fileKey = await crypto.subtle.importKey(
    'raw',
    fileKeyBuf,
    { name: 'AES-GCM' },
    false,
    ['decrypt']
  );

  const fileNonce = base64ToUint8(crypto_meta.file_nonce);
  const plaintext = await crypto.subtle.decrypt(
    {
      name: 'AES-GCM',
      iv: fileNonce,
      tagLength: TAG_LEN * 8,
    },
    fileKey,
    ciphertext
  );

  return plaintext;
}

/**
 * Trigger browser download of a blob with given filename.
 * Filename is sanitized to prevent path traversal.
 */
export function downloadBlob(blob, filename) {
  const safe = (filename || 'download').replace(/[^\w\s.-]/gi, '_').slice(0, 255) || 'download';
  const a = document.createElement('a');
  const url = URL.createObjectURL(blob);
  a.href = url;
  a.download = safe;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 2500);
}
