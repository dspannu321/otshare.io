import { useState, useRef } from 'react';
import { encryptFile } from '../crypto';
import { createShare, uploadShare } from '../api';

const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

export function UploadFlow({ apiBase }) {
  const [file, setFile] = useState(null);
  const [passcode, setPasscode] = useState('');
  const [step, setStep] = useState('select');
  const [pickupCode, setPickupCode] = useState('');
  const [error, setError] = useState('');
  const [copied, setCopied] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef(null);

  const handleFileChange = (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > MAX_FILE_SIZE) {
      setError('File is too large (max 100MB).');
      return;
    }
    setError('');
    setFile(f);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setIsDragging(false);
    const f = e.dataTransfer?.files?.[0];
    if (!f) return;
    if (f.size > MAX_FILE_SIZE) {
      setError('File is too large (max 100MB).');
      return;
    }
    setError('');
    setFile(f);
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => setIsDragging(false);

  const copyCode = async () => {
    try {
      await navigator.clipboard.writeText(pickupCode);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (_) {}
  };

  const submitHandler = async (e) => {
    e.preventDefault();
    if (!file || !passcode.trim()) {
      setError('Choose a file and enter a lock passcode.');
      return;
    }
    setError('');
    setStep('uploading');
    try {
      const fileBuffer = await file.arrayBuffer();
      const { ciphertext, crypto_meta, kdf } = await encryptFile(
        fileBuffer,
        passcode
      );
      const { pickup_code, upload_url } = await createShare(apiBase);
      await uploadShare(upload_url, {
        ciphertext: new Blob([ciphertext]),
        crypto_meta,
        kdf,
        original_name: file.name,
        mime: file.type || 'application/octet-stream',
      });
      setPickupCode(pickup_code);
      setStep('done');
    } catch (err) {
      const msg = err.message || '';
      if (msg.includes('Failed to fetch') || msg.includes('NetworkError') || msg.includes('Load failed')) {
        setError('Network error. Check your connection and try again.');
      } else if (msg.includes('crypto_meta') || msg.includes('ciphertext') || msg.includes('validation')) {
        setError(msg);
      } else if (msg) {
        setError(msg);
      } else {
        setError('Something went wrong. Please try again.');
      }
      setStep('select');
    }
  };

  const reset = () => {
    setStep('select');
    setFile(null);
    setPasscode('');
    setPickupCode('');
    setError('');
  };

  if (step === 'done') {
    return (
      <div className="otshare-card p-6 sm:p-8">
        <div className="flex items-start gap-4 mb-6">
          <div className="w-12 h-12 rounded-2xl bg-violet-500/20 flex items-center justify-center shrink-0">
            <svg className="w-6 h-6 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div>
            <h2 className="text-xl font-semibold text-white mb-1">Share ready</h2>
            <p className="text-zinc-500 text-sm">Send the pickup code and passcode to the recipient. They have 30 minutes and one download.</p>
          </div>
        </div>
        <div className="rounded-xl bg-white/[0.04] border border-white/[0.08] p-5 mb-6">
          <p className="text-zinc-500 text-xs font-medium uppercase tracking-wider mb-3">Pickup code</p>
          <div className="flex items-center gap-3">
            <code className="flex-1 font-mono text-xl text-violet-400 tracking-[0.25em] select-all break-all">
              {pickupCode}
            </code>
            <button
              type="button"
              onClick={copyCode}
              className="shrink-0 p-3 rounded-xl bg-white/[0.06] border border-white/[0.08] hover:bg-white/[0.1] text-zinc-400 hover:text-violet-400 transition-colors"
              title="Copy"
            >
              {copied ? (
                <svg className="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              ) : (
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              )}
            </button>
          </div>
        </div>
        <button type="button" onClick={reset} className="otshare-btn-secondary w-full">
          Share another file
        </button>
      </div>
    );
  }

  return (
    <form onSubmit={submitHandler} className="otshare-card p-6 sm:p-8">
      {/* ----- Upload area: clear EMPTY vs FILLED ----- */}
      <label className="block text-sm font-semibold text-zinc-400 mb-3">File</label>

      {!file ? (
        /* EMPTY: inviting drop zone */
        <div
          onClick={() => fileInputRef.current?.click()}
          onDrop={handleDrop}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
          className={`otshare-upload-empty mb-6 flex flex-col items-center justify-center min-h-[200px] py-10 px-6 cursor-pointer select-none ${
            isDragging ? 'border-violet-500/50 bg-violet-500/10' : ''
          }`}
        >
          <input
            ref={fileInputRef}
            type="file"
            onChange={handleFileChange}
            className="hidden"
          />
          <div className="w-16 h-16 rounded-2xl bg-zinc-700/40 flex items-center justify-center mb-4">
            <svg className="w-8 h-8 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
          </div>
          <p className="text-zinc-300 font-medium text-center mb-1">
            Drop your file here or click to browse
          </p>
          <p className="text-zinc-600 text-sm">
            Up to 100MB · Any file type
          </p>
        </div>
      ) : (
        /* FILLED: clear "file selected" card */
        <div className="otshare-upload-filled mb-6 p-4 flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-violet-500/20 flex items-center justify-center shrink-0">
            <svg className="w-6 h-6 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div className="min-w-0 flex-1">
            <p className="text-violet-400/90 text-xs font-semibold uppercase tracking-wider mb-0.5">File selected</p>
            <p className="text-white font-medium truncate">{file.name}</p>
            <p className="text-zinc-500 text-sm">{formatFileSize(file.size)}</p>
          </div>
          <button
            type="button"
            onClick={(e) => { e.preventDefault(); fileInputRef.current?.click(); }}
            className="shrink-0 text-sm font-medium text-violet-400 hover:text-violet-300 transition-colors"
          >
            Change
          </button>
          <input
            ref={fileInputRef}
            type="file"
            onChange={handleFileChange}
            className="hidden"
          />
        </div>
      )}

      <label className="block text-sm font-semibold text-zinc-400 mb-2 mt-2">Lock passcode</label>
      <input
        type="text"
        value={passcode}
        onChange={(e) => setPasscode(e.target.value)}
        placeholder="Recipient needs this to decrypt"
        className="otshare-input mb-2"
        autoComplete="off"
      />
      <p className="text-zinc-600 text-xs mb-5">Share the passcode with the recipient separately.</p>

      {error && (
        <div className="mb-5 flex items-center gap-2 rounded-xl bg-red-500/10 border border-red-500/20 px-4 py-3 text-red-400 text-sm">
          <svg className="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {error}
        </div>
      )}

      <button
        type="submit"
        disabled={step === 'uploading' || !file || !passcode.trim()}
        className="otshare-btn-primary flex items-center justify-center gap-2"
      >
        {step === 'uploading' ? (
          <>
            <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            Encrypting & uploading…
          </>
        ) : (
          'Encrypt and share'
        )}
      </button>
    </form>
  );
}
