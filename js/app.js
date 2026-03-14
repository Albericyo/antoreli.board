import { bindEvents } from './controller.js';
import { loadFromStorage } from './model.js';
import { renderCats, renderReels } from './view.js';

function init() {
  loadFromStorage();
  bindEvents();
  renderCats();
  renderReels();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
