import './bootstrap';
import '../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './components/App.jsx';

const el = document.getElementById('otshare-root');
if (el) {
  createRoot(el).render(React.createElement(App, null));
}
