const API_BASE = '/api/v1';

const ENDPOINTS = [
  {
    method: 'POST',
    path: '/api/v1/shares',
    summary: 'Create a new share',
    body: {},
    response: {
      id: 'uuid',
      pickup_code: 'XXXX-XXXXXX',
      expires_at: 'ISO8601',
      upload_url: 'https://.../api/v1/shares/{id}/upload',
    },
  },
  {
    method: 'POST',
    path: '/api/v1/shares/{id}/upload',
    summary: 'Upload encrypted file and metadata',
    contentType: 'multipart/form-data',
    body: {
      ciphertext: 'file or base64 string',
      crypto_meta: 'JSON object (wrapped_file_key, file_nonce, wrap_nonce, salt)',
      kdf: 'JSON object (type, memory, timeCost, parallelism for argon2id)',
      original_name: 'string (optional)',
      mime: 'string (optional)',
    },
    response: { id: 'uuid', expires_at: 'ISO8601' },
  },
  {
    method: 'POST',
    path: '/api/v1/redeem',
    summary: 'Redeem pickup code; get one-time download token',
    body: { pickup_code: 'XXXX-XXXXXX' },
    response: {
      download_token: 'string',
      expires_at: 'ISO8601',
      crypto_meta: 'object',
      kdf: 'object',
      original_name: 'string',
      mime: 'string',
      size_bytes: 'number',
    },
  },
  {
    method: 'GET',
    path: '/api/v1/download',
    summary: 'Download ciphertext (does not consume token)',
    query: { token: 'download_token from redeem' },
    response: 'binary (ciphertext)',
  },
  {
    method: 'POST',
    path: '/api/v1/download/confirm',
    summary: 'Confirm decrypt success or failure; on success consumes token',
    body: { token: 'string', success: true | false },
    response: {
      message: 'string',
      attempts_left: 'number (on wrong passcode)',
      expired: 'boolean (true when no attempts left)',
    },
  },
];

function MethodBadge({ method }) {
  const green = method === 'GET';
  return (
    <span
      className={`inline-block px-2 py-0.5 rounded text-xs font-mono font-semibold ${
        green ? 'bg-emerald-500/20 text-emerald-400' : 'bg-violet-500/20 text-violet-400'
      }`}
    >
      {method}
    </span>
  );
}

export function ApiDocs() {
  return (
    <div className="otshare-card p-6 sm:p-8">
      <div className="text-center mb-6">
        <h2 className="text-xl font-semibold text-white mb-2">API reference</h2>
        <p className="text-zinc-500 text-sm">
          Use these endpoints to build your own client. Base URL: <code className="text-violet-400">{API_BASE}</code>
        </p>
      </div>

      <div className="space-y-6">
        {ENDPOINTS.map((ep) => (
          <section
            key={ep.path}
            className="rounded-xl bg-white/[0.03] border border-white/[0.06] p-4 sm:p-5"
          >
            <div className="flex flex-wrap items-center gap-2 mb-2">
              <MethodBadge method={ep.method} />
              <code className="text-sm text-zinc-300 font-mono break-all">{ep.path}</code>
            </div>
            <p className="text-zinc-500 text-sm mb-3">{ep.summary}</p>
            {ep.query && (
              <div className="mb-2">
                <span className="text-zinc-500 text-xs uppercase tracking-wider">Query</span>
                <pre className="mt-1 text-xs text-zinc-400 font-mono bg-black/20 rounded p-2 overflow-x-auto">
                  {JSON.stringify(ep.query, null, 2)}
                </pre>
              </div>
            )}
            {(ep.body || ep.contentType) && (
              <div className="mb-2">
                {ep.contentType && (
                  <span className="text-zinc-500 text-xs uppercase tracking-wider">Content-Type: {ep.contentType}</span>
                )}
                {ep.body && typeof ep.body === 'object' && !ep.contentType && (
                  <>
                    <span className="text-zinc-500 text-xs uppercase tracking-wider block mt-1">Body (JSON)</span>
                    <pre className="mt-1 text-xs text-zinc-400 font-mono bg-black/20 rounded p-2 overflow-x-auto">
                      {JSON.stringify(ep.body, null, 2)}
                    </pre>
                  </>
                )}
                {ep.body && ep.contentType && (
                  <>
                    <span className="text-zinc-500 text-xs uppercase tracking-wider block mt-1">Fields</span>
                    <pre className="mt-1 text-xs text-zinc-400 font-mono bg-black/20 rounded p-2 overflow-x-auto">
                      {JSON.stringify(ep.body, null, 2)}
                    </pre>
                  </>
                )}
              </div>
            )}
            <div>
              <span className="text-zinc-500 text-xs uppercase tracking-wider">Response</span>
              <pre className="mt-1 text-xs text-zinc-400 font-mono bg-black/20 rounded p-2 overflow-x-auto">
                {typeof ep.response === 'string'
                  ? ep.response
                  : JSON.stringify(ep.response, null, 2)}
              </pre>
            </div>
          </section>
        ))}
      </div>

      <p className="mt-6 text-zinc-600 text-xs text-center">
        All endpoints are rate-limited. Use <code className="text-zinc-500">Accept: application/json</code> for JSON errors.
      </p>
    </div>
  );
}
