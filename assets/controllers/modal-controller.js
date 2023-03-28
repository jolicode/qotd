import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static values = {
        options: Object,
    }

    connect() {
        this.modal = new Modal(this.element, this.optionsValue);
        this.modal.show();
    }

    disconnect() {
        this.modal.hide();
        this.element.remove();
    }
}
