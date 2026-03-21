import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App.jsx';

const el = document.getElementById('otshare-root');
if (el) {
    createRoot(el).render(React.createElement(App, null));
}
