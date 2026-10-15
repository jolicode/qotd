import { Controller } from '@hotwired/stimulus';
import { visit } from '@hotwired/turbo';
import { path } from '../router.js';

const CHORD_TIMEOUT = 1000;

// The shortcuts come from App\KeyboardShortcut\KeyboardShortcut::toStimulus()
export default class extends Controller {
    static values = {
        shortcuts: Array,
    };

    pendingKeys = null;
    pendingKeysTimeout = null;
    selectedQotdId = null;
    selectedQotdIndex = null;
    selectionStyle = null;

    connect() {
        // A rule rather than a class on the card: LiveComponent re-renders and
        // Turbo Streams replace the cards, the selection must survive both
        this.selectionStyle = document.createElement('style');
        this.element.append(this.selectionStyle);
    }

    disconnect() {
        clearTimeout(this.pendingKeysTimeout);
        this.selectionStyle.remove();
    }

    handle(event) {
        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey || this.#isEditable(event.target)) {
            return;
        }

        const shortcut = this.#resolve(event.key);
        if (!shortcut) {
            return;
        }

        event.preventDefault();

        if (shortcut.method) {
            this[shortcut.method]();

            return;
        }

        let parameters = {};
        if (shortcut.withSelectedQotd) {
            if (!this.#selectedQotd()) {
                return;
            }
            parameters = { id: this.selectedQotdId };
        }

        visit(path(shortcut.route, parameters), { frame: shortcut.frame });
    }

    selectNext() {
        this.#select(1);
    }

    selectPrevious() {
        this.#select(-1);
    }

    voteUp() {
        this.#selectedQotd()?.querySelector('.qotd-vote-up').click();
    }

    voteDown() {
        this.#selectedQotd()?.querySelector('.qotd-vote-down').click();
    }

    #resolve(key) {
        clearTimeout(this.pendingKeysTimeout);
        const keys = this.pendingKeys ? `${this.pendingKeys} ${key}` : key;
        this.pendingKeys = null;

        const shortcut = this.shortcutsValue.find((shortcut) => shortcut.keys === keys);
        if (shortcut) {
            return shortcut;
        }

        // The first key of a chord, like "g" for "g t"
        if (this.shortcutsValue.some((shortcut) => shortcut.keys.startsWith(`${keys} `))) {
            this.pendingKeys = keys;
            this.pendingKeysTimeout = setTimeout(() => this.pendingKeys = null, CHORD_TIMEOUT);

            return null;
        }

        // An unknown chord: the key may still be a shortcut on its own
        return keys !== key ? this.#resolve(key) : null;
    }

    #cards() {
        return [...this.element.querySelectorAll('.qotd-card:not([data-live-removing])')];
    }

    #selectedQotd() {
        return this.#cards().find((card) => card.dataset.qotdId === this.selectedQotdId);
    }

    #select(offset) {
        const cards = this.#cards();
        if (0 === cards.length) {
            return;
        }

        let index = cards.indexOf(this.#selectedQotd());
        if (-1 !== index) {
            index += offset;
        } else if (null === this.selectedQotdIndex) {
            index = 0;
        } else {
            // The selected QOTD left the page (voted in "Not voted yet"): the next one took its place
            index = offset > 0 ? this.selectedQotdIndex : this.selectedQotdIndex - 1;
        }
        index = Math.min(Math.max(index, 0), cards.length - 1);

        const card = cards[index];
        this.selectedQotdId = card.dataset.qotdId;
        this.selectedQotdIndex = index;
        this.selectionStyle.textContent = `.qotd-card[data-qotd-id="${CSS.escape(this.selectedQotdId)}"] {
            outline: 2px solid var(--bs-primary);
            outline-offset: 2px;
        }`;
        card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    #isEditable(element) {
        return element instanceof Element && null !== element.closest('input, textarea, select, [contenteditable]');
    }
}
