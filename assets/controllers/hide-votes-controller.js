import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['switch', 'vote', 'icon'];

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
        this.voteTargets.forEach(vote => {
            vote.classList.toggle('d-none', this.switchTarget.checked);
        });
        this.iconTargets.forEach(icon => {
            icon.classList.toggle('d-none', !this.switchTarget.checked);
        });
    }
}
