import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['bsModal', 'frame', 'loadingTemplate'];

    modal = null;

    connect() {
        this.element.addEventListener('turbo:before-fetch-request', (e) => {
            // If the current modal disable loader, we don't show the loader
            // This is useful for modals that are used to display a form
            if (this.modal && this.hasBsModalTarget && this.bsModalTarget.hasAttribute('disable-loader')) {
                return;
            }

            this.frameTarget.innerHTML = this.loadingTemplateTarget.innerHTML;
        });
    }

    disconnect() {
        this.close();
    }

    bsModalTargetDisconnected() {
        this.close();
    }
    bsModalTargetConnected() {
        this.open();
    }

    /** internal stuff */

    open() {
        if (this.modal) {
            return;
        }
        this.modal = new Modal(this.bsModalTarget);
        this.modal.show();
        this.bsModalTarget.addEventListener('hide.bs.modal', () => {
            this.modal = null;
        });
    }

    close() {
        if (!this.modal) {
            return;
        }
        this.modal.hide();
        this.modal = null;
    }
}
