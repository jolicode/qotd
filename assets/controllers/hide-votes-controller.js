import { Controller } from '@hotwired/stimulus';

// The votes are hidden with CSS (see .votes-hidden in app.css), so a card
// re-rendered by LiveComponent or Turbo stays hidden without any JS
export default class extends Controller {
    static targets = ['switch'];

    connect() {
        const savedState = localStorage.getItem('hiddenModeActive');
        this.switchTarget.checked = savedState ? JSON.parse(savedState) : false;
        this.updateUI();
    }

    toggle() {
        localStorage.setItem('hiddenModeActive', JSON.stringify(this.switchTarget.checked));
        this.updateUI();
    }

    updateUI() {
        this.element.classList.toggle('votes-hidden', this.switchTarget.checked);
    }
}
