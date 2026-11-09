import { Controller } from '@hotwired/stimulus';
import { visit } from '@hotwired/turbo';

export default class extends Controller {
    static targets = ['card'];
    static values = { url: String };

    // A voted card leaves the list: without any card left, the page has nothing to offer but stale links
    cardTargetDisconnected() {
        if (!this.hasCardTarget) {
            visit(this.urlValue, { action: 'replace' });
        }
    }
}
